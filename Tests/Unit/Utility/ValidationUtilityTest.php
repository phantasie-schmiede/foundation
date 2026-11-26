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
use PHPUnit\Framework\Exception;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ValidationUtilityTest extends UnitTestCase
{
    private const array CONSTANT = [
        'BAR' => 'bar',
        'FOO' => 'foo',
    ];

    public static function checkKeyAgainstConstantValidDataProvider(): Generator
    {
        yield 'existing key FOO' => ['FOO'];
        yield 'existing key BAR' => ['BAR'];
    }

    public static function checkValueAgainstConstantValidDataProvider(): Generator
    {
        yield 'existing value foo' => ['foo'];
        yield 'existing value bar' => ['bar'];
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    #[Test]
    public function checkArrayAgainstConstantKeysDoesNotThrowForAllValidKeys(): void
    {
        foreach (['FOO', 'BAR'] as $key) {
            self::assertArrayHasKey($key, self::CONSTANT);
        }

        ValidationUtility::checkArrayAgainstConstantKeys(self::CONSTANT, ['FOO', 'BAR']);
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Test]
    public function checkArrayAgainstConstantKeysThrowsForInvalidKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ValidationUtility::checkArrayAgainstConstantKeys(self::CONSTANT, ['FOO', 'INVALID']);
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    #[Test]
    public function checkArrayAgainstConstantValuesDoesNotThrowForAllValidValues(): void
    {
        foreach (['foo', 'bar'] as $value) {
            self::assertContains($value, self::CONSTANT);
        }

        ValidationUtility::checkArrayAgainstConstantValues(self::CONSTANT, ['foo', 'bar']);
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Test]
    public function checkArrayAgainstConstantValuesThrowsForInvalidValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ValidationUtility::checkArrayAgainstConstantValues(self::CONSTANT, ['foo', 'invalid']);
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    #[Test]
    #[DataProvider('checkKeyAgainstConstantValidDataProvider')]
    public function checkKeyAgainstConstantDoesNotThrowForExistingKey(string $key): void
    {
        self::assertArrayHasKey($key, self::CONSTANT);
        ValidationUtility::checkKeyAgainstConstant(self::CONSTANT, $key);
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Test]
    public function checkKeyAgainstConstantThrowsForMissingKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ValidationUtility::checkKeyAgainstConstant(self::CONSTANT, 'MISSING');
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    #[Test]
    #[DataProvider('checkValueAgainstConstantValidDataProvider')]
    public function checkValueAgainstConstantDoesNotThrowForExistingValue(string $value): void
    {
        self::assertContains($value, self::CONSTANT);
        ValidationUtility::checkValueAgainstConstant(self::CONSTANT, $value);
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Test]
    public function checkValueAgainstConstantThrowsForMissingValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ValidationUtility::checkValueAgainstConstant(self::CONSTANT, 'missing');
    }
}
