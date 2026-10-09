<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Service\Configuration\Tca;

use PSBits\Foundation\Attribute\TCA\Column;
use PSBits\Foundation\Attribute\TCA\Palette;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function str_contains;

/**
 * Class Position
 *
 * A parsed position specification in the form "keyword:reference" as used by TCA attributes
 * (e.g. "after:myField", "palette:myPalette", "newLineAfter:myField").
 *
 * @package PSBits\Foundation\Service\Configuration\Tca
 */
final readonly class Position
{
    private function __construct(
        private string $keyword,
        private string $reference,
    ) {
    }

    /**
     * If the position is "newLineAfter" or "newLineBefore", it is converted to "after"/"before" and a line break is
     * prepended/appended to the field names.
     *
     * @param string[] $fieldNames
     *
     * @return array{position: string, fieldNames: string[]}
     */
    public static function applyLineBreaks(string $position, array $fieldNames): array
    {
        $parsedPosition = self::fromString($position);

        if ($parsedPosition->isNewLineAfter()) {
            return [
                'position'   => $parsedPosition->withKeyword(Column::POSITIONS['AFTER'])
                    ->toString(),
                'fieldNames' => array_merge([Palette::SPECIAL_FIELDS['LINE_BREAK']], $fieldNames),
            ];
        }

        if ($parsedPosition->isNewLineBefore()) {
            return [
                'position'   => $parsedPosition->withKeyword(Column::POSITIONS['BEFORE'])
                    ->toString(),
                'fieldNames' => array_merge($fieldNames, [Palette::SPECIAL_FIELDS['LINE_BREAK']]),
            ];
        }

        return [
            'position'   => $position,
            'fieldNames' => $fieldNames,
        ];
    }

    public static function fromString(string $position): self
    {
        $parts = GeneralUtility::trimExplode(':', $position, false, 2);

        return new self($parts[0] ?? '', $parts[1] ?? '');
    }

    /**
     * Normalizes a position by converting the reference from a property name to a column name, unless it already
     * looks like a palette name (which contains a dash).
     *
     * @param callable(string): string $columnConverter Converts a property name to a column name.
     */
    public static function normalize(string $position, callable $columnConverter): string
    {
        if ('' === $position) {
            return '';
        }

        $parsedPosition = self::fromString($position);

        // Check if the reference is NOT a palette name.
        if (!str_contains($parsedPosition->getReference(), '-')) {
            $parsedPosition = $parsedPosition->withReference($columnConverter($parsedPosition->getReference()));
        }

        return $parsedPosition->toString();
    }

    public function getKeyword(): string
    {
        return $this->keyword;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function is(string $keyword): bool
    {
        return $keyword === $this->keyword;
    }

    public function isNewLineAfter(): bool
    {
        return $this->is(Palette::SPECIAL_POSITIONS['NEW_LINE_AFTER']);
    }

    public function isNewLineBefore(): bool
    {
        return $this->is(Palette::SPECIAL_POSITIONS['NEW_LINE_BEFORE']);
    }

    public function isPalette(): bool
    {
        return $this->is(Column::POSITIONS['PALETTE']);
    }

    public function isTab(): bool
    {
        return $this->is(Column::POSITIONS['TAB']);
    }

    public function toString(): string
    {
        return $this->keyword . ':' . $this->reference;
    }

    /**
     * Returns a new position with the given keyword and the same reference.
     */
    public function withKeyword(string $keyword): self
    {
        return new self($keyword, $this->reference);
    }

    /**
     * Returns a new position with the given reference and the same keyword.
     */
    public function withReference(string $reference): self
    {
        return new self($this->keyword, $reference);
    }
}
