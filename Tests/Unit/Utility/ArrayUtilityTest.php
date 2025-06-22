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
}
