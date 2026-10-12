<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Service\Configuration\Tca;

use TYPO3\CMS\Core\Utility\GeneralUtility;

use function explode;
use function in_array;

/**
 * Class ShowItemList
 *
 * A parsed showitem list (comma separated entries, optionally with ";" separated parts like
 * "--palette--;identifier;paletteIdentifier").
 *
 * @package PSBits\Foundation\Service\Configuration\Tca
 */
final readonly class ShowItemList
{
    /**
     * @var string[]
     */
    private array $items;

    /**
     * @param string[] $items
     */
    private function __construct(array $items)
    {
        $this->items = $items;
    }

    public static function fromString(?string $showItem): self
    {
        return new self(GeneralUtility::trimExplode(',', $showItem ?? ''));
    }

    public function containsField(string $fieldName): bool
    {
        return in_array($fieldName, $this->getFieldNames(), true);
    }

    /**
     * Checks if the list contains a reference to the given palette (in the form "--palette--;field;identifier").
     */
    public function containsPaletteReference(string $paletteIdentifier): bool
    {
        foreach ($this->items as $item) {
            $parts = array_map('trim', explode(';', $item));

            if ('--palette--' === $parts[0] && $paletteIdentifier === ($parts[2] ?? '')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the field names of all items, stripping palette reference markers.
     *
     * @return string[]
     */
    public function getFieldNames(): array
    {
        return array_map(
            static function(string $item): string {
                return explode(';', $item)[0];
            },
            $this->items
        );
    }

    /**
     * @return string[]
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
