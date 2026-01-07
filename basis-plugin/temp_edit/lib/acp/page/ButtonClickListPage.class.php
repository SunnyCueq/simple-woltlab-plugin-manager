<?php

namespace urlshort\acp\page;

use urlshort\data\buttonclick\ButtonClickList;
use urlshort\data\visit\VisitList;
use wcf\page\MultipleLinkPage;
use wcf\system\WCF;

/**
 * ACP page for listing all button clicks.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage acp.page
 */
class ButtonClickListPage extends MultipleLinkPage
{
    /**
     * @inheritDoc
     */
    public $objectListClassName = ButtonClickList::class;

    /**
     * @inheritDoc
     */
    public $sortField = 'clickTime';

    /**
     * @inheritDoc
     */
    public $sortOrder = 'DESC';

    /**
     * @inheritDoc
     */
    public $activeMenuItem = 'urlshort.acp.menu.link.statistics.list';

    /**
     * @inheritDoc
     */
    public $neededPermissions = ['admin.urlshort.canManageButtonClicks'];

    /**
     * @inheritDoc
     */
    public $validSortFields = ['clickID', 'urlID', 'buttonType', 'clickTime'];

    /**
     * Filter: URL ID
     */
    public $urlID;

    /**
     * Filter: Button Type
     */
    public $buttonType;

    /**
     * Filter: Date from
     */
    public $dateFrom;

    /**
     * Filter: Date to
     */
    public $dateTo;

    /**
     * Statistics data
     */
    public array $statistics = [];

    /**
     * @inheritDoc
     */
    public function readParameters()
    {
        parent::readParameters();

        // Read sort parameters
        if (isset($_REQUEST['sortField']) && in_array($_REQUEST['sortField'], $this->validSortFields)) {
            $this->sortField = $_REQUEST['sortField'];
        }
        if (isset($_REQUEST['sortOrder']) && in_array($_REQUEST['sortOrder'], ['ASC', 'DESC'])) {
            $this->sortOrder = $_REQUEST['sortOrder'];
        }

        if (isset($_REQUEST['urlID']) && $_REQUEST['urlID']) {
            $this->urlID = (int) $_REQUEST['urlID'];
        }
        if (isset($_REQUEST['buttonType']) && $_REQUEST['buttonType']) {
            $this->buttonType = $_REQUEST['buttonType'];
        }
        if (isset($_REQUEST['dateFrom']) && $_REQUEST['dateFrom']) {
            $timestamp = strtotime($_REQUEST['dateFrom']);
            if ($timestamp !== false) {
                $this->dateFrom = $timestamp;
            }
        }
        if (isset($_REQUEST['dateTo']) && $_REQUEST['dateTo']) {
            $timestamp = strtotime($_REQUEST['dateTo']);
            if ($timestamp !== false) {
                // Set to end of day (23:59:59)
                $this->dateTo = $timestamp + 86399;
            }
        }
    }

    /**
     * @inheritDoc
     */
    protected function initObjectList()
    {
        parent::initObjectList();

        // Apply sorting
        if (in_array($this->sortField, $this->validSortFields)) {
            $this->objectList->sqlOrderBy = $this->sortField . ' ' . $this->sortOrder;
        }

        $conditions = [];
        $parameters = [];

        if ($this->urlID) {
            $conditions[] = 'urlID = ?';
            $parameters[] = $this->urlID;
        }

        if ($this->buttonType) {
            $conditions[] = 'buttonType = ?';
            $parameters[] = $this->buttonType;
        }

        if ($this->dateFrom) {
            $conditions[] = 'clickTime >= ?';
            $parameters[] = $this->dateFrom;
        }

        if ($this->dateTo) {
            $conditions[] = 'clickTime <= ?';
            $parameters[] = $this->dateTo;
        }

        if (!empty($conditions)) {
            $this->objectList->getConditionBuilder()->add(
                '(' . implode(' AND ', $conditions) . ')',
                $parameters
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function assignVariables()
    {
        parent::assignVariables();

        // Load URL hashes for all clicks
        $urlHashes = [];
        if (isset($this->objects) && is_array($this->objects) && !empty($this->objects)) {
            $urlIDs = [];
            foreach ($this->objects as $click) {
                if (isset($click->urlID)) {
                    $urlIDs[] = $click->urlID;
                }
            }
            
            if (!empty($urlIDs)) {
                $urlIDs = array_unique($urlIDs);
                $placeholders = str_repeat('?,', count($urlIDs) - 1) . '?';
                $sql = "SELECT urlID, hash FROM urlshort1_url WHERE urlID IN ({$placeholders})";
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute($urlIDs);
                while ($row = $statement->fetchArray()) {
                    $urlHashes[$row['urlID']] = $row['hash'];
                }
            }
        }

        // Calculate statistics
        $this->calculateStatistics();

        WCF::getTPL()->assign([
            'urlID' => $this->urlID ?? 0,
            'buttonType' => $this->buttonType ?? '',
            'dateFrom' => $this->dateFrom ? date('Y-m-d', $this->dateFrom) : '',
            'dateTo' => $this->dateTo ? date('Y-m-d', $this->dateTo) : '',
            'urlHashes' => $urlHashes,
            'statistics' => $this->statistics,
            'sortField' => $this->sortField,
            'sortOrder' => $this->sortOrder,
        ]);
    }

    /**
     * Calculates statistics for button clicks.
     */
    private function calculateStatistics(): void
    {
        $conditions = [];
        $parameters = [];

        if ($this->urlID) {
            $conditions[] = 'urlID = ?';
            $parameters[] = $this->urlID;
        }

        if ($this->buttonType) {
            $conditions[] = 'buttonType = ?';
            $parameters[] = $this->buttonType;
        }

        if ($this->dateFrom) {
            $conditions[] = 'clickTime >= ?';
            $parameters[] = $this->dateFrom;
        }

        if ($this->dateTo) {
            $conditions[] = 'clickTime <= ?';
            $parameters[] = $this->dateTo;
        }

        $whereClause = '';
        if (!empty($conditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $conditions);
        }

        // Total clicks
        $sql = "SELECT COUNT(*) as total FROM urlshort1_button_click {$whereClause}";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute($parameters);
        $this->statistics['total'] = $statement->fetchSingleColumn();

        // Clicks by button type
        $sql = "SELECT buttonType, COUNT(*) as count 
                FROM urlshort1_button_click 
                {$whereClause}
                GROUP BY buttonType
                ORDER BY count DESC";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute($parameters);
        $this->statistics['byType'] = [];
        while ($row = $statement->fetchArray()) {
            $this->statistics['byType'][$row['buttonType']] = $row['count'];
        }

        // Zeiträume: heute, gestern, letzte 7 Tage
        $todayStart = strtotime('today');
        $yesterdayStart = $todayStart - 86400;
        $sevenDaysStart = $todayStart - (6 * 86400); // inkl. heute = 7 Tage

        // Helper: count with extra conditions
        $countWithRange = function (array $baseConditions, array $baseParams, ?int $fromTs, ?int $toTs): int {
            $conds = $baseConditions;
            $params = $baseParams;
            if ($fromTs !== null) {
                $conds[] = 'clickTime >= ?';
                $params[] = $fromTs;
            }
            if ($toTs !== null) {
                $conds[] = 'clickTime <= ?';
                $params[] = $toTs;
            }
            $where = '';
            if (!empty($conds)) {
                $where = 'WHERE ' . implode(' AND ', $conds);
            }
            $sql = "SELECT COUNT(*) AS cnt FROM urlshort1_button_click {$where}";
            $stmt = WCF::getDB()->prepareStatement($sql);
            $stmt->execute($params);
            return (int) $stmt->fetchSingleColumn();
        };

        $this->statistics['today'] = $countWithRange($conditions, $parameters, $todayStart, null);
        $this->statistics['yesterday'] = $countWithRange($conditions, $parameters, $yesterdayStart, $todayStart - 1);
        $this->statistics['last7'] = $countWithRange($conditions, $parameters, $sevenDaysStart, null);

        // Top URLs (Top 5)
        $sql = "SELECT urlID, COUNT(*) as count 
                FROM urlshort1_button_click 
                {$whereClause}
                GROUP BY urlID
                ORDER BY count DESC
                LIMIT 5";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute($parameters);
        $topUrlIDs = [];
        $this->statistics['topUrls'] = [];
        while ($row = $statement->fetchArray()) {
            $this->statistics['topUrls'][$row['urlID']] = $row['count'];
            $topUrlIDs[] = $row['urlID'];
        }
        
        // Load URL hashes for top URLs
        $topUrlHashes = [];
        if (!empty($topUrlIDs)) {
            $placeholders = str_repeat('?,', count($topUrlIDs) - 1) . '?';
            $sql = "SELECT urlID, hash FROM urlshort1_url WHERE urlID IN ({$placeholders})";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute($topUrlIDs);
            while ($row = $statement->fetchArray()) {
                $topUrlHashes[$row['urlID']] = $row['hash'];
            }
        }
        $this->statistics['topUrlHashes'] = $topUrlHashes;

        // Visit statistics (Besuche)
        $this->calculateVisitStatistics($conditions, $parameters);

        // Referrer statistics
        $this->calculateReferrerStatistics($conditions, $parameters);

        // Combined statistics (Button-Klicks + Besuche)
        $this->calculateCombinedStatistics();
    }

    /**
     * Calculates visit statistics.
     *
     * @param array $conditions Base conditions for filtering
     * @param array $parameters Base parameters for filtering
     * @return void
     */
    private function calculateVisitStatistics(array $conditions, array $parameters): void
    {
        // Build WHERE clause for visits
        $visitConditions = [];
        $visitParameters = [];

        // Map urlID filter to visits
        if ($this->urlID) {
            $visitConditions[] = 'urlID = ?';
            $visitParameters[] = $this->urlID;
        }

        // Map date filters to visits
        if ($this->dateFrom) {
            $visitConditions[] = 'visitTime >= ?';
            $visitParameters[] = $this->dateFrom;
        }

        if ($this->dateTo) {
            $visitConditions[] = 'visitTime <= ?';
            $visitParameters[] = $this->dateTo;
        }

        $whereClause = '';
        if (!empty($visitConditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $visitConditions);
        }

        // Total visits
        $sql = "SELECT COUNT(*) FROM urlshort1_visit {$whereClause}";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute($visitParameters);
        $this->statistics['visits']['total'] = $statement->fetchSingleColumn();

        // Zeiträume: heute, gestern, letzte 7 Tage
        $todayStart = strtotime('today');
        $yesterdayStart = $todayStart - 86400;
        $sevenDaysStart = $todayStart - (6 * 86400);

        $countWithRange = function (array $baseConditions, array $baseParams, ?int $fromTs, ?int $toTs): int {
            $conds = $baseConditions;
            $params = $baseParams;
            if ($fromTs !== null) {
                $conds[] = 'visitTime >= ?';
                $params[] = $fromTs;
            }
            if ($toTs !== null) {
                $conds[] = 'visitTime <= ?';
                $params[] = $toTs;
            }
            $where = '';
            if (!empty($conds)) {
                $where = 'WHERE ' . implode(' AND ', $conds);
            }
            $sql = "SELECT COUNT(*) FROM urlshort1_visit {$where}";
            $stmt = WCF::getDB()->prepareStatement($sql);
            $stmt->execute($params);
            return (int) $stmt->fetchSingleColumn();
        };

        $this->statistics['visits']['today'] = $countWithRange($visitConditions, $visitParameters, $todayStart, null);
        $this->statistics['visits']['yesterday'] = $countWithRange($visitConditions, $visitParameters, $yesterdayStart, $todayStart - 1);
        $this->statistics['visits']['last7'] = $countWithRange($visitConditions, $visitParameters, $sevenDaysStart, null);

        // Top URLs by visits (Top 5)
        $sql = "SELECT urlID, COUNT(*) as count 
                FROM urlshort1_visit 
                {$whereClause}
                GROUP BY urlID
                ORDER BY count DESC
                LIMIT 5";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute($visitParameters);
        $this->statistics['visits']['topUrls'] = [];
        $topVisitUrlIDs = [];
        while ($row = $statement->fetchArray()) {
            $this->statistics['visits']['topUrls'][$row['urlID']] = $row['count'];
            $topVisitUrlIDs[] = $row['urlID'];
        }

        // Load URL hashes for top visit URLs
        if (!empty($topVisitUrlIDs)) {
            $placeholders = str_repeat('?,', count($topVisitUrlIDs) - 1) . '?';
            $sql = "SELECT urlID, hash FROM urlshort1_url WHERE urlID IN ({$placeholders})";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute($topVisitUrlIDs);
            $this->statistics['visits']['topUrlHashes'] = [];
            while ($row = $statement->fetchArray()) {
                $this->statistics['visits']['topUrlHashes'][$row['urlID']] = $row['hash'];
            }
        }
    }

    /**
     * Calculates referrer statistics.
     *
     * @param array $conditions Base conditions for filtering
     * @param array $parameters Base parameters for filtering
     * @return void
     */
    private function calculateReferrerStatistics(array $conditions, array $parameters): void
    {
        // Build WHERE clause for visits with referrer
        $visitConditions = ['referrer IS NOT NULL AND referrer != ?'];
        $visitParameters = [''];

        if ($this->urlID) {
            $visitConditions[] = 'urlID = ?';
            $visitParameters[] = $this->urlID;
        }

        if ($this->dateFrom) {
            $visitConditions[] = 'visitTime >= ?';
            $visitParameters[] = $this->dateFrom;
        }

        if ($this->dateTo) {
            $visitConditions[] = 'visitTime <= ?';
            $visitParameters[] = $this->dateTo;
        }

        $whereClause = 'WHERE ' . implode(' AND ', $visitConditions);

        // Top Referrers (Top 10, grouped by full URL)
        $sql = "SELECT referrer, COUNT(*) as count 
                FROM urlshort1_visit 
                {$whereClause}
                GROUP BY referrer
                ORDER BY count DESC
                LIMIT 10";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute($visitParameters);
        $this->statistics['referrers']['top'] = [];
        while ($row = $statement->fetchArray()) {
            $this->statistics['referrers']['top'][$row['referrer']] = $row['count'];
        }

        // Top Referrer Domains (extract domain from referrer)
        $sql = "SELECT referrer, COUNT(*) as count 
                FROM urlshort1_visit 
                {$whereClause}
                GROUP BY referrer
                ORDER BY count DESC";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute($visitParameters);
        $domainCounts = [];
        while ($row = $statement->fetchArray()) {
            $referrer = $row['referrer'];
            $parsedUrl = parse_url($referrer);
            if (isset($parsedUrl['host'])) {
                $domain = $parsedUrl['host'];
                if (!isset($domainCounts[$domain])) {
                    $domainCounts[$domain] = 0;
                }
                $domainCounts[$domain] += $row['count'];
            }
        }
        arsort($domainCounts);
        $this->statistics['referrers']['topDomains'] = array_slice($domainCounts, 0, 10, true);
    }

    /**
     * Calculates combined statistics (Button-Klicks + Besuche).
     *
     * @return void
     */
    private function calculateCombinedStatistics(): void
    {
        $this->statistics['combined'] = [
            'total' => ($this->statistics['total'] ?? 0) + ($this->statistics['visits']['total'] ?? 0),
            'today' => ($this->statistics['today'] ?? 0) + ($this->statistics['visits']['today'] ?? 0),
            'yesterday' => ($this->statistics['yesterday'] ?? 0) + ($this->statistics['visits']['yesterday'] ?? 0),
            'last7' => ($this->statistics['last7'] ?? 0) + ($this->statistics['visits']['last7'] ?? 0),
        ];
    }
}

