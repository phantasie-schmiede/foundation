<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Tests\Unit\Service\Configuration\Tca;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PSBits\Foundation\Service\Configuration\Tca\ShowItemList;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class ShowItemListTest
 *
 * @package PSBits\Foundation\Tests\Unit\Service\Configuration\Tca
 */
class ShowItemListTest extends UnitTestCase
{
    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function containsPaletteReferenceDataProvider(): array
    {
        return [
            'palette reference with matching identifier' => [
                '--palette--;main;main_palette',
                'main_palette',
                true,
            ],
            'palette reference with other identifier'    => [
                '--palette--;main;other_palette',
                'main_palette',
                false,
            ],
            'palette reference without identifier'       => [
                '--palette--;main',
                'main_palette',
                false,
            ],
            'plain field is no palette reference'        => [
                'my_field',
                'main_palette',
                false,
            ],
            'whitespace around parts is trimmed'         => [
                ' --palette--; main ; main_palette ',
                'main_palette',
                true,
            ],
        ];
    }

    #[Test]
    #[DataProvider('containsPaletteReferenceDataProvider')]
    public function containsPaletteReference(string $showItem, string $paletteIdentifier, bool $expected): void
    {
        self::assertSame($expected, ShowItemList::fromString($showItem)->containsPaletteReference($paletteIdentifier));
    }

    #[Test]
    public function containsPaletteReferenceFindsReferenceInList(): void
    {
        $showItemList = ShowItemList::fromString('field_a, --palette--;main;my_palette, field_b');

        self::assertTrue($showItemList->containsPaletteReference('my_palette'));
        self::assertFalse($showItemList->containsPaletteReference('other_palette'));
    }

    #[Test]
    public function containsFieldFindsPlainField(): void
    {
        $showItemList = ShowItemList::fromString('field_a, field_b');

        self::assertTrue($showItemList->containsField('field_a'));
        self::assertTrue($showItemList->containsField('field_b'));
        self::assertFalse($showItemList->containsField('field_c'));
    }

    #[Test]
    public function containsFieldIgnoresFieldsOfPaletteReferences(): void
    {
        $showItemList = ShowItemList::fromString('field_a, --palette--;field_b;my_palette');

        self::assertTrue($showItemList->containsField('field_a'));
        self::assertFalse($showItemList->containsField('field_b'));
        self::assertFalse($showItemList->containsField('my_palette'));
    }

    #[Test]
    public function fromStringTrimsWhitespace(): void
    {
        $showItemList = ShowItemList::fromString(' field_a ,  field_b ');

        self::assertSame(['field_a', 'field_b'], $showItemList->getFieldNames());
    }

    #[Test]
    public function fromStringWithEmptyStringReturnsSingleEmptyItem(): void
    {
        self::assertSame([''], ShowItemList::fromString('')->getItems());
        self::assertSame([''], ShowItemList::fromString(null)->getItems());
    }
}
