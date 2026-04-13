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
use InvalidArgumentException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class ValidationUtilityTest
 *
 * @package PSBits\Foundation\Utility
 */
class ValidationUtilityTest extends UnitTestCase
{
    private const array CONSTANT = [
        'FOO' => 'foo',
        'BAR' => 'bar',
    ];

    public static function checkKeyAgainstConstantValidDataProvider(): Generator
    {
        yield 'existing key FOO' => ['FOO'];
        yield 'existing key BAR' => ['BAR'];
    }

    /**
     * @test
     * @dataProvider checkKeyAgainstConstantValidDataProvider
     */
    public function checkKeyAgainstConstantDoesNotThrowForExistingKey(string $key): void
    {
        ValidationUtility::checkKeyAgainstConstant(self::CONSTANT, $key);
        self::assertTrue(true);
    }

    /**
     * @test
     */
    public function checkKeyAgainstConstantThrowsForMissingKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ValidationUtility::checkKeyAgainstConstant(self::CONSTANT, 'MISSING');
    }

    public static function checkValueAgainstConstantValidDataProvider(): Generator
    {
        yield 'existing value foo' => ['foo'];
        yield 'existing value bar' => ['bar'];
    }

    /**
     * @test
     * @dataProvider checkValueAgainstConstantValidDataProvider
     */
    public function checkValueAgainstConstantDoesNotThrowForExistingValue(string $value): void
    {
        ValidationUtility::checkValueAgainstConstant(self::CONSTANT, $value);
        self::assertTrue(true);
    }

    /**
     * @test
     */
    public function checkValueAgainstConstantThrowsForMissingValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ValidationUtility::checkValueAgainstConstant(self::CONSTANT, 'missing');
    }

    /**
     * @test
     */
    public function checkArrayAgainstConstantKeysDoesNotThrowForAllValidKeys(): void
    {
        ValidationUtility::checkArrayAgainstConstantKeys(self::CONSTANT, ['FOO', 'BAR']);
        self::assertTrue(true);
    }

    /**
     * @test
     */
    public function checkArrayAgainstConstantKeysThrowsForInvalidKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ValidationUtility::checkArrayAgainstConstantKeys(self::CONSTANT, ['FOO', 'INVALID']);
    }

    /**
     * @test
     */
    public function checkArrayAgainstConstantValuesDoesNotThrowForAllValidValues(): void
    {
        ValidationUtility::checkArrayAgainstConstantValues(self::CONSTANT, ['foo', 'bar']);
        self::assertTrue(true);
    }

    /**
     * @test
     */
    public function checkArrayAgainstConstantValuesThrowsForInvalidValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ValidationUtility::checkArrayAgainstConstantValues(self::CONSTANT, ['foo', 'invalid']);
    }
}
