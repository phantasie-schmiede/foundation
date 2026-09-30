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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
    #[DataProvider('checkKeyAgainstConstantValidDataProvider')]
    public function checkKeyAgainstConstantDoesNotThrowForExistingKey(string $key): void
    {
        self::assertArrayHasKey($key, self::CONSTANT);
        ValidationUtility::checkKeyAgainstConstant(self::CONSTANT, $key);
    }

    #[Test]
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

    #[Test]
    #[DataProvider('checkValueAgainstConstantValidDataProvider')]
    public function checkValueAgainstConstantDoesNotThrowForExistingValue(string $value): void
    {
        self::assertContains($value, self::CONSTANT);
        ValidationUtility::checkValueAgainstConstant(self::CONSTANT, $value);
    }

    #[Test]
    public function checkValueAgainstConstantThrowsForMissingValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ValidationUtility::checkValueAgainstConstant(self::CONSTANT, 'missing');
    }

    #[Test]
    public function checkArrayAgainstConstantKeysDoesNotThrowForAllValidKeys(): void
    {
        foreach (['FOO', 'BAR'] as $key) {
            self::assertArrayHasKey($key, self::CONSTANT);
        }

        ValidationUtility::checkArrayAgainstConstantKeys(self::CONSTANT, ['FOO', 'BAR']);
    }

    #[Test]
    public function checkArrayAgainstConstantKeysThrowsForInvalidKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ValidationUtility::checkArrayAgainstConstantKeys(self::CONSTANT, ['FOO', 'INVALID']);
    }

    #[Test]
    public function checkArrayAgainstConstantValuesDoesNotThrowForAllValidValues(): void
    {
        foreach (['foo', 'bar'] as $value) {
            self::assertContains($value, self::CONSTANT);
        }

        ValidationUtility::checkArrayAgainstConstantValues(self::CONSTANT, ['foo', 'bar']);
    }

    #[Test]
    public function checkArrayAgainstConstantValuesThrowsForInvalidValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ValidationUtility::checkArrayAgainstConstantValues(self::CONSTANT, ['foo', 'invalid']);
    }
}
