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
use RuntimeException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class VariableUtilityTest
 *
 * @package PSBits\Foundation\Utility
 */
class VariableUtilityTest extends UnitTestCase
{
    public static function getValueByPathArrayDataProvider(): Generator
    {
        yield 'simple key' => [
            ['foo' => 'bar'],
            'foo',
            'bar',
        ];
        yield 'nested key' => [
            ['foo' => ['bar' => 'baz']],
            'foo.bar',
            'baz',
        ];
        yield 'deeply nested key' => [
            ['a' => ['b' => ['c' => 42]]],
            'a.b.c',
            42,
        ];
    }

    /**
     * @test
     * @dataProvider getValueByPathArrayDataProvider
     */
    public function getValueByPathOnArray(array $variable, string $path, mixed $expectedResult): void
    {
        self::assertEquals(
            $expectedResult,
            VariableUtility::getValueByPath($variable, $path)
        );
    }

    /**
     * @test
     */
    public function getValueByPathThrowsForMissingPathInStrictMode(): void
    {
        $this->expectException(RuntimeException::class);
        VariableUtility::getValueByPath(['foo' => 'bar'], 'missing');
    }

    /**
     * @test
     */
    public function getValueByPathReturnsNullForMissingPathInNonStrictMode(): void
    {
        self::assertNull(
            VariableUtility::getValueByPath(['foo' => 'bar'], 'missing', false)
        );
    }

    /**
     * @test
     */
    public function getValueByPathOnObject(): void
    {
        $object = new class {
            public function getName(): string
            {
                return 'test';
            }
        };

        self::assertEquals('test', VariableUtility::getValueByPath($object, 'name'));
    }

    /**
     * @test
     */
    public function getValueByPathOnNestedObjectAndArray(): void
    {
        $inner = new class {
            public function getValue(): int
            {
                return 99;
            }
        };

        $array = ['key' => $inner];

        self::assertEquals(99, VariableUtility::getValueByPath($array, 'key.value'));
    }
}
