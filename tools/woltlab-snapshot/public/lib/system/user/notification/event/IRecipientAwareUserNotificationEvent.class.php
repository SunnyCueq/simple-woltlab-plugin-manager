<?php

namespace wcf\system\user\notification\event;

use wcf\data\user\User;

/**
 * @author      Alexander Ebert
 * @copyright   2001-2025 WoltLab GmbH, Oliver Kliebisch
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since       6.1
 */
interface IRecipientAwareUserNotificationEvent extends IUserNotificationEvent
{
    /**
     * Sets the recipient of the notification event to allow filtering of any
     * additional data.
     */
    public function setRecipient(User $user): void;
}
