<?php

namespace shrinkr\acp\page;

use shrinkr\data\buttonclick\ButtonClickList;
use shrinkr\data\visit\VisitList;
use wcf\page\MultipleLinkPage;
use wcf\system\WCF;

/**
 * ACP page for listing all button clicks.
 * 
 * Provides a sortable list of button clicks with filtering capabilities and
 * statistics. Supports filtering by link ID, button type, and date range.
 * Displays analytics including time series charts and aggregations.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  acp.page
 */
class ButtonClickListPage extends MultipleLinkPage
{
    /**
     * Class name of the database object list.
     *
     * @var    string
     */
    public $objectListClassName = ButtonClickList::class;

    /**
     * Default sort field.
     *
     * @var    string
     */
    public $sortField = 'clickTime';

    /**
     * Default sort order.
     *
     * @var    string
     */
    public $sortOrder = 'DESC';

    /**
     * Active menu item identifier for navigation highlighting.
     *
     * @var    string
     */
    public $activeMenuItem = 'shrinkr.acp.menu.link.statistics.list';

    /**
     * Required permissions to access this page.
     *
     * @var    string[]
     */
    public $neededPermissions = ['admin.shrinkr.canManageButtonClicks'];

    /**
     * Valid sort fields for the list.
     *
     * @var    string[]
     */
    public $validSortFields = ['clickID', 'linkID', 'buttonType', 'clickTime'];

    /**
     * Filter: URL ID to filter clicks by.
     *
     * @var    int
     */
    public $linkID;

    /**
     * Filter: Button type to filter clicks by.
     *
     * @var    string
     */
    public $buttonType;

    /**
     * Filter: Start date for date range filtering.
     *
     * @var    string
     */
    public $dateFrom;

    /**
     * Filter: End date for date range filtering.
     *
     * @var    string
     */
    public $dateTo;

    /**
     * Time granularity for time series charts ('day' or 'hour').
     *
     * @var    string
     */
    public $timeGranularity = 'day';

    /**
     * Statistics data for display (aggregations, charts, etc.).
     *
     * @var    array
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

        if (isset($_REQUEST['linkID']) && $_REQUEST['linkID']) {
            $this->linkID = (int) $_REQUEST['linkID'];
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

        // Read time granularity parameter
        if (isset($_REQUEST['timeGranularity']) && in_array($_REQUEST['timeGranularity'], ['day', 'hour'])) {
            $this->timeGranularity = $_REQUEST['timeGranularity'];
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

        if ($this->linkID) {
            $conditions[] = 'linkID = ?';
            $parameters[] = $this->linkID;
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
            $linkIDs = [];
            foreach ($this->objects as $click) {
                if (isset($click->linkID)) {
                    $linkIDs[] = $click->linkID;
                }
            }
            
            if (!empty($linkIDs)) {
                $linkIDs = array_unique($linkIDs);
                $placeholders = str_repeat('?,', count($linkIDs) - 1) . '?';
                $sql = "SELECT linkID, hash FROM shrinkr1_link WHERE linkID IN ({$placeholders})";
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute($linkIDs);
                while ($row = $statement->fetchArray()) {
                    $urlHashes[$row['linkID']] = $row['hash'];
                }
            }
        }

        // Calculate statistics
        $this->calculateStatistics();

        WCF::getTPL()->assign([
            'linkID' => $this->linkID ?? 0,
            'buttonType' => $this->buttonType ?? '',
            'dateFrom' => $this->dateFrom ? date('Y-m-d', $this->dateFrom) : '',
            'dateTo' => $this->dateTo ? date('Y-m-d', $this->dateTo) : '',
            'timeGranularity' => $this->timeGranularity,
            'urlHashes' => $urlHashes,
            'statistics' => $this->statistics,
            'sortField' => $this->sortField,
            'sortOrder' => $this->sortOrder,
            'acpPageSubMenuCategoryList' => 'shrinkr.acp.menu.link.statistics.list',
        ]);
    }

    /**
     * Calculates statistics for button clicks.
     */
    private function calculateStatistics(): void
    {
        $conditions = [];
        $parameters = [];

        if ($this->linkID) {
            $conditions[] = 'linkID = ?';
            $parameters[] = $this->linkID;
        }

        if ($this->buttonType) {
            $conditions[] = 'buttonType = ?';
            $parameters[] = $this->buttonType;
        }

        // If no date filter is set, use default range of last 30 days for better statistics
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
        $sql = "SELECT COUNT(*) as total FROM shrinkr1_button_click {$whereClause}";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute($parameters);
        $this->statistics['total'] = $statement->fetchSingleColumn();

        // Clicks by button type
        $sql = "SELECT buttonType, COUNT(*) as count 
                FROM shrinkr1_button_click 
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
            $sql = "SELECT COUNT(*) AS cnt FROM shrinkr1_button_click {$where}";
            $stmt = WCF::getDB()->prepareStatement($sql);
            $stmt->execute($params);
            return (int) $stmt->fetchSingleColumn();
        };

        $this->statistics['today'] = $countWithRange($conditions, $parameters, $todayStart, null);
        $this->statistics['yesterday'] = $countWithRange($conditions, $parameters, $yesterdayStart, $todayStart - 1);
        $this->statistics['last7'] = $countWithRange($conditions, $parameters, $sevenDaysStart, null);

        // Top URLs (Top 5)
        $sql = "SELECT linkID, COUNT(*) as count 
                FROM shrinkr1_button_click 
                {$whereClause}
                GROUP BY linkID
                ORDER BY count DESC
                LIMIT 5";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute($parameters);
        $topUrlIDs = [];
        $this->statistics['topUrls'] = [];
        while ($row = $statement->fetchArray()) {
            $this->statistics['topUrls'][$row['linkID']] = $row['count'];
            $topUrlIDs[] = $row['linkID'];
        }
        
        // Load URL hashes for top URLs
        $topUrlHashes = [];
        if (!empty($topUrlIDs)) {
            $placeholders = str_repeat('?,', count($topUrlIDs) - 1) . '?';
            $sql = "SELECT linkID, hash FROM shrinkr1_link WHERE linkID IN ({$placeholders})";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute($topUrlIDs);
            while ($row = $statement->fetchArray()) {
                $topUrlHashes[$row['linkID']] = $row['hash'];
            }
        }
        $this->statistics['topUrlHashes'] = $topUrlHashes;

        // Visit statistics (Besuche)
        $this->calculateVisitStatistics($conditions, $parameters);

        // Referrer statistics
        $this->calculateReferrerStatistics($conditions, $parameters);

        // Combined statistics (Button-Klicks + Besuche)
        $this->calculateCombinedStatistics();

        // Extended analytics statistics (country, device, browser, os)
        $this->calculateExtendedAnalyticsStatistics($conditions, $parameters);

        // Time series data for charts
        $this->calculateTimeSeriesData($conditions, $parameters);
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

        // Map linkID filter to visits
        if ($this->linkID) {
            $visitConditions[] = 'linkID = ?';
            $visitParameters[] = $this->linkID;
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
        $sql = "SELECT COUNT(*) FROM shrinkr1_visit {$whereClause}";
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
            $sql = "SELECT COUNT(*) FROM shrinkr1_visit {$where}";
            $stmt = WCF::getDB()->prepareStatement($sql);
            $stmt->execute($params);
            return (int) $stmt->fetchSingleColumn();
        };

        $this->statistics['visits']['today'] = $countWithRange($visitConditions, $visitParameters, $todayStart, null);
        $this->statistics['visits']['yesterday'] = $countWithRange($visitConditions, $visitParameters, $yesterdayStart, $todayStart - 1);
        $this->statistics['visits']['last7'] = $countWithRange($visitConditions, $visitParameters, $sevenDaysStart, null);

        // Top URLs by visits (Top 5)
        $sql = "SELECT linkID, COUNT(*) as count 
                FROM shrinkr1_visit 
                {$whereClause}
                GROUP BY linkID
                ORDER BY count DESC
                LIMIT 5";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute($visitParameters);
        $this->statistics['visits']['topUrls'] = [];
        $topVisitUrlIDs = [];
        while ($row = $statement->fetchArray()) {
            $this->statistics['visits']['topUrls'][$row['linkID']] = $row['count'];
            $topVisitUrlIDs[] = $row['linkID'];
        }

        // Load URL hashes for top visit URLs
        if (!empty($topVisitUrlIDs)) {
            $placeholders = str_repeat('?,', count($topVisitUrlIDs) - 1) . '?';
            $sql = "SELECT linkID, hash FROM shrinkr1_link WHERE linkID IN ({$placeholders})";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute($topVisitUrlIDs);
            $this->statistics['visits']['topUrlHashes'] = [];
            while ($row = $statement->fetchArray()) {
                $this->statistics['visits']['topUrlHashes'][$row['linkID']] = $row['hash'];
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

        if ($this->linkID) {
            $visitConditions[] = 'linkID = ?';
            $visitParameters[] = $this->linkID;
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
                FROM shrinkr1_visit 
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
                FROM shrinkr1_visit 
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

    /**
     * Calculates extended analytics statistics (country, device, browser, os).
     *
     * @param array $conditions Base conditions for filtering
     * @param array $parameters Base parameters for filtering
     * @return void
     */
    private function calculateExtendedAnalyticsStatistics(array $conditions, array $parameters): void
    {
        // Build WHERE clause for visits
        $visitConditions = [];
        $visitParameters = [];

        if ($this->linkID) {
            $visitConditions[] = 'linkID = ?';
            $visitParameters[] = $this->linkID;
        }

        if ($this->dateFrom) {
            $visitConditions[] = 'visitTime >= ?';
            $visitParameters[] = $this->dateFrom;
        }

        if ($this->dateTo) {
            $visitConditions[] = 'visitTime <= ?';
            $visitParameters[] = $this->dateTo;
        }

        // Country statistics
        $countryWhereClause = '';
        if (!empty($visitConditions)) {
            $countryWhereClause = 'WHERE ' . implode(' AND ', $visitConditions) . ' AND country IS NOT NULL AND country != \'\'';
        } else {
            $countryWhereClause = 'WHERE country IS NOT NULL AND country != \'\'';
        }
        $sql = "SELECT country, COUNT(*) as count 
                FROM shrinkr1_visit 
                {$countryWhereClause}
                GROUP BY country
                ORDER BY count DESC
                LIMIT 20";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute($visitParameters);
        $this->statistics['analytics']['countries'] = [];
        while ($row = $statement->fetchArray()) {
            $this->statistics['analytics']['countries'][$row['country']] = $row['count'];
        }

        // Device type statistics
        $deviceWhereClause = '';
        if (!empty($visitConditions)) {
            $deviceWhereClause = 'WHERE ' . implode(' AND ', $visitConditions) . ' AND deviceType IS NOT NULL AND deviceType != \'\'';
        } else {
            $deviceWhereClause = 'WHERE deviceType IS NOT NULL AND deviceType != \'\'';
        }
        $sql = "SELECT deviceType, COUNT(*) as count 
                FROM shrinkr1_visit 
                {$deviceWhereClause}
                GROUP BY deviceType
                ORDER BY count DESC";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute($visitParameters);
        $this->statistics['analytics']['devices'] = [];
        while ($row = $statement->fetchArray()) {
            $this->statistics['analytics']['devices'][] = [
                'label' => ucfirst($row['deviceType']),
                'value' => (int) $row['count']
            ];
        }

        // Browser statistics
        $browserWhereClause = '';
        if (!empty($visitConditions)) {
            $browserWhereClause = 'WHERE ' . implode(' AND ', $visitConditions) . ' AND browser IS NOT NULL AND browser != \'\'';
        } else {
            $browserWhereClause = 'WHERE browser IS NOT NULL AND browser != \'\'';
        }
        $sql = "SELECT browser, COUNT(*) as count 
                FROM shrinkr1_visit 
                {$browserWhereClause}
                GROUP BY browser
                ORDER BY count DESC
                LIMIT 15";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute($visitParameters);
        $this->statistics['analytics']['browsers'] = [];
        while ($row = $statement->fetchArray()) {
            $this->statistics['analytics']['browsers'][] = [
                'label' => $row['browser'],
                'value' => (int) $row['count']
            ];
        }

        // OS statistics
        $osWhereClause = '';
        if (!empty($visitConditions)) {
            $osWhereClause = 'WHERE ' . implode(' AND ', $visitConditions) . ' AND os IS NOT NULL AND os != \'\'';
        } else {
            $osWhereClause = 'WHERE os IS NOT NULL AND os != \'\'';
        }
        $sql = "SELECT os, COUNT(*) as count 
                FROM shrinkr1_visit 
                {$osWhereClause}
                GROUP BY os
                ORDER BY count DESC
                LIMIT 15";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute($visitParameters);
        $this->statistics['analytics']['os'] = [];
        while ($row = $statement->fetchArray()) {
            $this->statistics['analytics']['os'][$row['os']] = $row['count'];
        }
    }

    /**
     * Calculates time series data for charts (daily visits/clicks over time).
     *
     * @param array $conditions Base conditions for filtering
     * @param array $parameters Base parameters for filtering
     * @return void
     */
    private function calculateTimeSeriesData(array $conditions, array $parameters): void
    {
        // Default to last 30 days if no date range specified
        $fromTimestamp = $this->dateFrom ?: (TIME_NOW - (30 * 86400));
        $toTimestamp = $this->dateTo ?: TIME_NOW;

        // Build WHERE clause for visits
        $visitConditions = [];
        $visitParameters = [];

        if ($this->linkID) {
            $visitConditions[] = 'linkID = ?';
            $visitParameters[] = $this->linkID;
        }

        $visitConditions[] = 'visitTime >= ?';
        $visitParameters[] = $fromTimestamp;
        $visitConditions[] = 'visitTime <= ?';
        $visitParameters[] = $toTimestamp;

        $whereClause = 'WHERE ' . implode(' AND ', $visitConditions);

        // Visits grouped by day or hour
        if ($this->timeGranularity === 'hour') {
            $sql = "SELECT DATE(FROM_UNIXTIME(visitTime)) as date, HOUR(FROM_UNIXTIME(visitTime)) as hour, COUNT(*) as count 
                    FROM shrinkr1_visit 
                    {$whereClause}
                    GROUP BY DATE(FROM_UNIXTIME(visitTime)), HOUR(FROM_UNIXTIME(visitTime))
                    ORDER BY date ASC, hour ASC";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute($visitParameters);
            $this->statistics['timeSeries']['visits'] = [];
            while ($row = $statement->fetchArray()) {
                $timestamp = strtotime($row['date'] . ' ' . str_pad($row['hour'], 2, '0', STR_PAD_LEFT) . ':00:00') * 1000; // JavaScript timestamp (milliseconds)
                $this->statistics['timeSeries']['visits'][] = [
                    'timestamp' => $timestamp,
                    'value' => (int) $row['count']
                ];
            }
        } else {
            // Daily visits (grouped by day)
            $sql = "SELECT DATE(FROM_UNIXTIME(visitTime)) as date, COUNT(*) as count 
                    FROM shrinkr1_visit 
                    {$whereClause}
                    GROUP BY DATE(FROM_UNIXTIME(visitTime))
                    ORDER BY date ASC";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute($visitParameters);
            $this->statistics['timeSeries']['visits'] = [];
            while ($row = $statement->fetchArray()) {
                $timestamp = strtotime($row['date'] . ' 00:00:00') * 1000; // JavaScript timestamp (milliseconds)
                $this->statistics['timeSeries']['visits'][] = [
                    'timestamp' => $timestamp,
                    'value' => (int) $row['count']
                ];
            }
        }

        // Daily button clicks (grouped by day)
        $clickConditions = [];
        $clickParameters = [];

        if ($this->linkID) {
            $clickConditions[] = 'linkID = ?';
            $clickParameters[] = $this->linkID;
        }

        if ($this->buttonType) {
            $clickConditions[] = 'buttonType = ?';
            $clickParameters[] = $this->buttonType;
        }

        $clickConditions[] = 'clickTime >= ?';
        $clickParameters[] = $fromTimestamp;
        $clickConditions[] = 'clickTime <= ?';
        $clickParameters[] = $toTimestamp;

        $clickWhereClause = 'WHERE ' . implode(' AND ', $clickConditions);

        // Button clicks grouped by day or hour
        if ($this->timeGranularity === 'hour') {
            $sql = "SELECT DATE(FROM_UNIXTIME(clickTime)) as date, HOUR(FROM_UNIXTIME(clickTime)) as hour, COUNT(*) as count 
                    FROM shrinkr1_button_click 
                    {$clickWhereClause}
                    GROUP BY DATE(FROM_UNIXTIME(clickTime)), HOUR(FROM_UNIXTIME(clickTime))
                    ORDER BY date ASC, hour ASC";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute($clickParameters);
            $this->statistics['timeSeries']['clicks'] = [];
            while ($row = $statement->fetchArray()) {
                $timestamp = strtotime($row['date'] . ' ' . str_pad($row['hour'], 2, '0', STR_PAD_LEFT) . ':00:00') * 1000; // JavaScript timestamp (milliseconds)
                $this->statistics['timeSeries']['clicks'][] = [
                    'timestamp' => $timestamp,
                    'value' => (int) $row['count']
                ];
            }
        } else {
            // Daily button clicks (grouped by day)
            $sql = "SELECT DATE(FROM_UNIXTIME(clickTime)) as date, COUNT(*) as count 
                    FROM shrinkr1_button_click 
                    {$clickWhereClause}
                    GROUP BY DATE(FROM_UNIXTIME(clickTime))
                    ORDER BY date ASC";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute($clickParameters);
            $this->statistics['timeSeries']['clicks'] = [];
            while ($row = $statement->fetchArray()) {
                $timestamp = strtotime($row['date'] . ' 00:00:00') * 1000; // JavaScript timestamp (milliseconds)
                $this->statistics['timeSeries']['clicks'][] = [
                    'timestamp' => $timestamp,
                    'value' => (int) $row['count']
                ];
            }
        }
    }
}

