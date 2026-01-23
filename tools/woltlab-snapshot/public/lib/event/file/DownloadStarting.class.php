<?php

namespace wcf\event\file;

use wcf\data\file\File;
use wcf\event\IPsr14Event;

/**
 * Notifies of the imminent begin of a file download.
 *
 * @author      Alexander Ebert
 * @copyright   2001-2025 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since       6.1
 */
final class DownloadStarting implements IPsr14Event
{
    public function __construct(
        public readonly File $file,
    ) {}
}
