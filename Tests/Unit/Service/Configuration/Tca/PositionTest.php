<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Tests\Unit\Service\Configuration\Tca;

use PHPUnit\Framework\Attributes\Test;
use PSBits\Foundation\Service\Configuration\Tca\Position;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class PositionTest
 *
 * @package PSBits\Foundation\Tests\Unit\Service\Configuration\Tca
 */
class PositionTest extends UnitTestCase
{
    #[Test]
    public function fromStringParsesKeywordAndReference(): void
    {
        $position = Position::fromString('after:my_field');

        self::assertSame('after', $position->getKeyword());
        self::assertSame('my_field', $position->getReference());
        self::assertSame('after:my_field', $position->toString());
    }

    #[Test]
    public function fromStringWithMissingReferenceReturnsEmptyReference(): void
    {
        $position = Position::fromString('after');

        self::assertSame('after', $position->getKeyword());
        self::assertSame('', $position->getReference());
    }

    #[Test]
    public function isMethodsMatchKeywords(): void
    {
        self::assertTrue(Position::fromString('palette:my_palette')->isPalette());
        self::assertTrue(Position::fromString('tab:my_tab')->isTab());
        self::assertTrue(Position::fromString('newLineAfter:my_field')->isNewLineAfter());
        self::assertTrue(Position::fromString('newLineBefore:my_field')->isNewLineBefore());
        self::assertFalse(Position::fromString('after:my_field')->isNewLineAfter());
    }

    #[Test]
    public function normalizeConvertsReferenceToColumnName(): void
    {
        $converter = static fn(string $name): string => 'converted_' . $name;

        self::assertSame('after:converted_myField', Position::normalize('after:myField', $converter));
    }

    #[Test]
    public function normalizeKeepsPaletteNamesUnchanged(): void
    {
        $converter = static fn(string $name): string => 'converted_' . $name;

        self::assertSame('palette:my-palette', Position::normalize('palette:my-palette', $converter));
    }

    #[Test]
    public function normalizeReturnsEmptyStringForEmptyPosition(): void
    {
        self::assertSame('', Position::normalize('', fn(string $name): string => $name));
    }

    #[Test]
    public function applyLineBreaksPrependsLineBreakForNewLineAfter(): void
    {
        [
            'position'   => $position,
            'fieldNames' => $fieldNames,
        ] = Position::applyLineBreaks('newLineAfter:my_field', ['field_a', 'field_b']);

        self::assertSame('after:my_field', $position);
        self::assertSame(['--linebreak--', 'field_a', 'field_b'], $fieldNames);
    }

    #[Test]
    public function applyLineBreaksAppendsLineBreakForNewLineBefore(): void
    {
        [
            'position'   => $position,
            'fieldNames' => $fieldNames,
        ] = Position::applyLineBreaks('newLineBefore:my_field', ['field_a', 'field_b']);

        self::assertSame('before:my_field', $position);
        self::assertSame(['field_a', 'field_b', '--linebreak--'], $fieldNames);
    }

    #[Test]
    public function applyLineBreaksKeepsOtherPositionsUnchanged(): void
    {
        [
            'position'   => $position,
            'fieldNames' => $fieldNames,
        ] = Position::applyLineBreaks('after:my_field', ['field_a']);

        self::assertSame('after:my_field', $position);
        self::assertSame(['field_a'], $fieldNames);
    }

    #[Test]
    public function applyLineBreaksKeepsEmptyPositionUnchanged(): void
    {
        [
            'position'   => $position,
            'fieldNames' => $fieldNames,
        ] = Position::applyLineBreaks('', ['field_a']);

        self::assertSame('', $position);
        self::assertSame(['field_a'], $fieldNames);
    }
}
