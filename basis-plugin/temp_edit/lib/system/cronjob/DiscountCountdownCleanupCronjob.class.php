<?php

namespace shrinkr\system\cronjob;

use wcf\data\cronjob\Cronjob;
use wcf\system\cronjob\AbstractCronjob;
use wcf\system\WCF;

/**
 * Cronjob to deactivate expired specials.
 * 
 * Automatically deactivates special events (sets isActive = 0) that have been
 * expired for more than 2 days. Between expiration and deactivation, the special
 * is shown as "Ended" in the ACP. Runs periodically via WoltLab's cronjob system.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  system.cronjob
 */
class DiscountCountdownCleanupCronjob extends AbstractCronjob
{
    /**
     * Executes the cronjob to deactivate expired specials.
     * 
     * Finds all specials that expired more than 2 days ago and are still active,
     * then deactivates them by setting isActive = 0.
     *
     * @param   Cronjob  $cronjob  The cronjob object
     * @return  void
     */
    public function execute(Cronjob $cronjob)
    {
        parent::execute($cronjob);

        $twoDaysAgo = TIME_NOW - 172800;

        $sql = "SELECT specialID
                FROM " . WCF::getDB()->escapeString('shrinkr' . WCF_N . '_special') . "
                WHERE isActive = 1
                  AND endTime > 0
                  AND endTime < ?";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute([$twoDaysAgo]);
        $specialIDs = $statement->fetchAll(\PDO::FETCH_COLUMN);

        if (!empty($specialIDs)) {
            $placeholders = str_repeat('?,', count($specialIDs) - 1) . '?';
            $updateSql = "UPDATE " . WCF::getDB()->escapeString('shrinkr' . WCF_N . '_special') . "
                         SET isActive = 0
                         WHERE specialID IN ({$placeholders})";
            $updateStatement = WCF::getDB()->prepareStatement($updateSql);
            $updateStatement->execute($specialIDs);
        }

    }
}
