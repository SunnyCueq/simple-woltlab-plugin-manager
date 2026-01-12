<?php

namespace shrinkr\system\cronjob;

use wcf\data\cronjob\Cronjob;
use wcf\system\cronjob\AbstractCronjob;
use wcf\system\WCF;

/**
 * Cronjob to deactivate expired specials.
 *
 * Deactivates specials (isActive = 0) that have been expired for more than 2 days.
 * Between expiration and deactivation, the special is shown as "Ended" in the ACP.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage system.cronjob
 */
class DiscountCountdownCleanupCronjob extends AbstractCronjob
{
    /**
     * @inheritDoc
     */
    public function execute(Cronjob $cronjob)
    {
        parent::execute($cronjob);

        // 2 days = 172800 seconds
        $twoDaysAgo = TIME_NOW - 172800;

        // Find specials that expired more than 2 days ago and are still active
        $sql = "SELECT specialID
                FROM " . WCF::getDB()->escapeString('shrinkr' . WCF_N . '_special') . "
                WHERE isActive = 1
                  AND endTime > 0
                  AND endTime < ?";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute([$twoDaysAgo]);
        $specialIDs = $statement->fetchAll(\PDO::FETCH_COLUMN);

        if (!empty($specialIDs)) {
            // Deactivate the specials
            $placeholders = str_repeat('?,', count($specialIDs) - 1) . '?';
            $updateSql = "UPDATE " . WCF::getDB()->escapeString('shrinkr' . WCF_N . '_special') . "
                         SET isActive = 0
                         WHERE specialID IN ({$placeholders})";
            $updateStatement = WCF::getDB()->prepareStatement($updateSql);
            $updateStatement->execute($specialIDs);
        }

    }
}
