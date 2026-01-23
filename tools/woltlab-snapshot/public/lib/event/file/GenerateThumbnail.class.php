<?php

namespace wcf\event\file;

use wcf\data\file\File;
use wcf\event\IPsr14Event;
use wcf\system\file\processor\ThumbnailFormat;

/**
 * Requests the generation of a thumbnail.
 *
 * @author      Alexander Ebert
 * @copyright   2001-2024 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since       6.1
 */
final class GenerateThumbnail implements IPsr14Event
{
    private string $pathname;
    private bool $sourceIsDamaged = false;

    public function __construct(
        public readonly File $file,
        public readonly ThumbnailFormat $thumbnailFormat,
    ) {}

    /**
     * Returns true if a file has already been set and no further files are
     * being accepted.
     */
    public function hasFile(): bool
    {
        return isset($this->pathname);
    }

    /**
     * Sets the pathname of the generated image unless it has already been set
     * in which case the call will throw an exception. You must check the result
     * of `hasFile()` first.
     */
    public function setGeneratedFile(string $pathname): void
    {
        if (isset($this->pathname)) {
            throw new \BadMethodCallException("Cannot set the generated file, a value has already been set.");
        }

        $this->pathname = $pathname;
    }

    public function getPathname(): ?string
    {
        return $this->pathname ?? null;
    }

    /**
     * Flags the source image as damaged which should stop further processing
     * of this file.
     */
    public function markSourceAsDamaged(): void
    {
        $this->sourceIsDamaged = true;
    }

    public function sourceIsMarkedAsDamaged(): bool
    {
        return $this->sourceIsDamaged;
    }
}
