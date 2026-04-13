<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Utility;

use Generator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class ArrayUtility
 *
 * @package PSBits\Foundation\Utility
 */
class ArrayUtilityTest extends UnitTestCase
{
    /**
     * @return Generator
     */
    public static function countRecursiveDataProvider(): Generator
    {
        yield 'empty array' => [
            [],
            0,
        ];
        yield 'simple array' => [
            [
                1,
                2,
                3,
            ],
            3,
        ];
        yield 'multidimensional array' => [
            [
                [
                    'foo' => [
                        'bar'    => 1,
                        'foobar' => 2,
                    ],
                ],
                'test',
                [
                    'bar' => 3,
                    'value',
                ],
            ],
            5,
        ];
    }

    /**
     * @test
     * @dataProvider countRecursiveDataProvider
     *
     * @param array $array
     * @param int   $expectedResult
     *
     * @return void
     */
    public function countRecursive(array $array, int $expectedResult): void
    {
        self::assertEquals(
            $expectedResult,
            ArrayUtility::countRecursive($array)
        );
    }

    public static function findLastOccurrenceDataProvider(): Generator
    {
        yield 'value exists once' => [
            'b',
            ['a', 'b', 'c'],
            1,
        ];
        yield 'value exists multiple times returns last key' => [
            'x',
            ['x', 'y', 'x'],
            2,
        ];
        yield 'value does not exist returns false' => [
            'z',
            ['a', 'b', 'c'],
            false,
        ];
        yield 'associative array returns last key' => [
            'v',
            ['first' => 'v', 'second' => 'w', 'third' => 'v'],
            'third',
        ];
    }

    /**
     * @test
     * @dataProvider findLastOccurrenceDataProvider
     */
    public function findLastOccurrence(mixed $needle, array $array, bool|int|string $expectedResult): void
    {
        self::assertSame(
            $expectedResult,
            ArrayUtility::findLastOccurrence($needle, $array)
        );
    }

    public static function guaranteeArrayTypeDataProvider(): Generator
    {
        yield 'already an array is returned as-is' => [
            [1, 2, 3],
            null,
            [1, 2, 3],
        ];
        yield 'null becomes empty array' => [
            null,
            null,
            [],
        ];
        yield 'empty string becomes empty array' => [
            '',
            null,
            [],
        ];
        yield 'scalar value wrapped in array' => [
            42,
            null,
            [42],
        ];
        yield 'string exploded by delimiter' => [
            'a, b, c',
            ',',
            ['a', 'b', 'c'],
        ];
        yield 'string without delimiter wrapped in array' => [
            'hello',
            null,
            ['hello'],
        ];
    }

    /**
     * @test
     * @dataProvider guaranteeArrayTypeDataProvider
     */
    public function guaranteeArrayType(mixed $variable, ?string $explodeOnCharacter, array $expectedResult): void
    {
        self::assertEquals(
            $expectedResult,
            ArrayUtility::guaranteeArrayType($variable, $explodeOnCharacter)
        );
    }

    public static function inArrayRecursiveDataProvider(): Generator
    {
        yield 'value found in flat array' => [
            ['a', 'b', 'c'],
            'b',
            false,
            false,
            [1],
        ];
        yield 'value not found in flat array' => [
            ['a', 'b', 'c'],
            'z',
            false,
            false,
            [],
        ];
        yield 'value found in nested array' => [
            ['level1' => ['level2' => 'target']],
            'target',
            false,
            false,
            ['level1.level2'],
        ];
        yield 'key search finds matching key' => [
            ['foo' => 1, 'bar' => 2],
            'foo',
            true,
            false,
            ['foo'],
        ];
        yield 'substring search finds partial match' => [
            ['hello world', 'foo bar'],
            'world',
            false,
            true,
            [0],
        ];
    }

    /**
     * @test
     * @dataProvider inArrayRecursiveDataProvider
     */
    public function inArrayRecursive(
        array  $haystack,
        mixed  $needle,
        bool   $searchKey,
        bool   $searchForSubstring,
        array  $expectedResult,
    ): void {
        self::assertEquals(
            $expectedResult,
            ArrayUtility::inArrayRecursive($haystack, $needle, $searchKey, $searchForSubstring)
        );
    }

    public static function insertIntoArrayDataProvider(): Generator
    {
        yield 'indexed array insert at index 1' => [
            ['a', 'b', 'c'],
            ['x', 'y'],
            1,
            ['a', 'x', 'y', 'b', 'c'],
        ];
        yield 'indexed array insert at index 0' => [
            ['a', 'b'],
            ['x'],
            0,
            ['x', 'a', 'b'],
        ];
        yield 'associative array insert' => [
            ['first' => 1, 'third' => 3],
            ['second' => 2],
            1,
            ['first' => 1, 'second' => 2, 'third' => 3],
        ];
    }

    /**
     * @test
     * @dataProvider insertIntoArrayDataProvider
     */
    public function insertIntoArray(array $array, array $elements, int $index, array $expectedResult): void
    {
        self::assertEquals(
            $expectedResult,
            ArrayUtility::insertIntoArray($array, $elements, $index)
        );
    }

    public static function isMultiDimensionalArrayDataProvider(): Generator
    {
        yield 'flat array is not multi-dimensional' => [
            [1, 2, 3],
            false,
        ];
        yield 'empty array is not multi-dimensional' => [
            [],
            false,
        ];
        yield 'array with nested array is multi-dimensional' => [
            [1, [2, 3], 4],
            true,
        ];
        yield 'associative array with nested array is multi-dimensional' => [
            ['key' => ['nested' => 'value']],
            true,
        ];
    }

    /**
     * @test
     * @dataProvider isMultiDimensionalArrayDataProvider
     */
    public function isMultiDimensionalArray(array $array, bool $expectedResult): void
    {
        self::assertSame(
            $expectedResult,
            ArrayUtility::isMultiDimensionalArray($array)
        );
    }

    public static function setValueByPathDataProvider(): Generator
    {
        yield 'set value at simple path' => [
            [],
            'foo',
            'bar',
            '.',
            ['foo' => 'bar'],
        ];
        yield 'set value at nested path' => [
            [],
            'foo.bar',
            42,
            '.',
            ['foo' => ['bar' => 42]],
        ];
        yield 'overwrite existing value' => [
            ['foo' => ['bar' => 'old']],
            'foo.bar',
            'new',
            '.',
            ['foo' => ['bar' => 'new']],
        ];
    }

    /**
     * @test
     * @dataProvider setValueByPathDataProvider
     */
    public function setValueByPath(
        array  $array,
        string $path,
        mixed  $value,
        string $delimiter,
        array  $expectedResult,
    ): void {
        ArrayUtility::setValueByPath($array, $path, $value, $delimiter);
        self::assertEquals($expectedResult, $array);
    }
}
