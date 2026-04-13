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
use JsonException;
use PSBits\Foundation\Tests\Examples\BackedEnum;
use PSBits\Foundation\Tests\Examples\Enum;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class StringUtilityTest
 *
 * @package PSBits\Foundation\Utility
 */
class StringUtilityTest extends UnitTestCase
{
    public const string TEST_CONSTANT       = 'test';
    public const array  TEST_CONSTANT_ARRAY = [
        'INDEX' => 'test',
    ];

    /**
     * @return Generator
     */
    public static function convertStringDataProvider(): Generator
    {
        yield 'empty string' => [
            '',
            '',
        ];
        yield 'zero' => [
            '0',
            0,
        ];
        yield 'integer' => [
            '123',
            123,
        ];
        yield 'decimal with period' => [
            '0.1',
            0.1,
        ];
        yield 'decimal with comma' => [
            '0,1',
            0.1,
        ];
        yield 'leading zeros are not truncated' => [
            '0123',
            '0123',
        ];
        yield 'floats with trailing zeros are not truncated (e.g. version numbers)' => [
            '2024.10',
            '2024.10',
        ];
        yield 'boolean false' => [
            'false',
            false,
        ];
        yield 'boolean true' => [
            'true',
            true,
        ];
        yield 'numeric CSV with two elements' => [
            '12, 521',
            '12, 521',
        ];
        yield 'CSV with more elements' => [
            '0, 121, abc',
            '0, 121, abc',
        ];
        yield 'JSON array' => [
            '[1,2,3]',
            [
                1,
                2,
                3,
            ],
        ];
        yield 'JSON object' => [
            '{"1":1,"2":2,"3":3}',
            [
                1 => 1,
                2 => 2,
                3 => 3,
            ],
        ];
        yield 'constant' => [
            '\PSBits\Foundation\Utility\StringUtilityTest::TEST_CONSTANT',
            self::TEST_CONSTANT,
        ];
        yield 'array constant with quotes' => [
            '\PSBits\Foundation\Utility\StringUtilityTest::TEST_CONSTANT_ARRAY[\'INDEX\']',
            self::TEST_CONSTANT_ARRAY['INDEX'],
        ];
        yield 'array constant without quotes' => [
            '\PSBits\Foundation\Utility\StringUtilityTest::TEST_CONSTANT_ARRAY[INDEX]',
            self::TEST_CONSTANT_ARRAY['INDEX'],
        ];
        yield 'enum' => [
            '\PSBits\Foundation\Tests\Examples\Enum::Alpha',
            Enum::Alpha,
        ];
        yield 'backed enum' => [
            '\PSBits\Foundation\Tests\Examples\BackedEnum::Delta',
            BackedEnum::Delta,
        ];
        // @TODO: test TypoScript ('TS:...')!
    }

    /**
     * @test
     * @dataProvider convertStringDataProvider
     *
     * @param string $string
     * @param        $expectedResult
     *
     * @return void
     * @throws JsonException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function convertString(string $string, $expectedResult): void
    {
        self::assertEquals(
            $expectedResult,
            StringUtility::convertString($string)
        );
    }

    public static function cleanUrlDataProvider(): Generator
    {
        yield 'plain url unchanged' => [
            'https://example.com/path',
            'https://example.com/path',
        ];
        yield 'url encoded string is decoded' => [
            'hello%20world',
            'hello world',
        ];
        yield 'html entity decoded' => [
            'https://example.com/path?a=1&amp;b=2',
            'https://example.com/path?a=1&b=2',
        ];
        yield 'combined url and html encoding' => [
            'hello%20world&amp;foo',
            'hello world&foo',
        ];
    }

    /**
     * @test
     * @dataProvider cleanUrlDataProvider
     */
    public function cleanUrl(string $url, string $expectedResult): void
    {
        self::assertEquals($expectedResult, StringUtility::cleanUrl($url));
    }

    public static function convertToFloatDataProvider(): Generator
    {
        yield 'period decimal separator' => ['1.5', 1.5];
        yield 'comma decimal separator' => ['1,5', 1.5];
        yield 'integer string' => ['42', 42.0];
        yield 'zero' => ['0', 0.0];
        yield 'negative with period' => ['-3.14', -3.14];
        yield 'negative with comma' => ['-3,14', -3.14];
    }

    /**
     * @test
     * @dataProvider convertToFloatDataProvider
     */
    public function convertToFloat(string $variable, float $expectedResult): void
    {
        self::assertEquals($expectedResult, StringUtility::convertToFloat($variable));
    }

    public static function isEmptyDataProvider(): Generator
    {
        yield 'empty string' => ['', true];
        yield 'only spaces' => ['   ', true];
        yield 'only tab' => ["\t", true];
        yield 'only newline' => ["\n", true];
        yield 'non-empty string' => ['hello', false];
        yield 'string with content and spaces' => ['  hello  ', false];
    }

    /**
     * @test
     * @dataProvider isEmptyDataProvider
     */
    public function stringIsEmpty(string $string, bool $expectedResult): void
    {
        self::assertSame($expectedResult, StringUtility::isEmpty($string));
    }

    public static function sanitizePropertyNameDataProvider(): Generator
    {
        yield 'underscore to camelCase' => ['first_name', 'firstName'];
        yield 'dash to camelCase' => ['first-name', 'firstName'];
        yield 'camelCase without separators becomes all lowercase' => ['firstName', 'firstname'];
        yield 'all lowercase' => ['name', 'name'];
        yield 'uppercase with underscores' => ['FIRST_NAME', 'firstName'];
        yield 'mixed case with dash' => ['some-Field', 'someField'];
    }

    /**
     * @test
     * @dataProvider sanitizePropertyNameDataProvider
     */
    public function sanitizePropertyName(string $propertyName, string $expectedResult): void
    {
        self::assertEquals($expectedResult, StringUtility::sanitizePropertyName($propertyName));
    }

    public static function getFirstWordDataProvider(): Generator
    {
        yield 'single word' => ['hello', 'hello'];
        yield 'multiple words' => ['hello world foo', 'hello'];
        yield 'words with extra spaces trimmed' => ['  hello  world', 'hello'];
    }

    /**
     * @test
     * @dataProvider getFirstWordDataProvider
     */
    public function getFirstWord(string $sentence, string $expectedResult): void
    {
        self::assertEquals($expectedResult, StringUtility::getFirstWord($sentence));
    }

    public static function cropDataProvider(): Generator
    {
        yield 'short string not cropped' => [
            'Hello',
            10,
            '…',
            false,
            false,
            'Hello',
        ];
        yield 'long string cropped without word boundary' => [
            'Hello World',
            5,
            '…',
            false,
            false,
            'Hello…',
        ];
        yield 'string ending with period gets no appendix' => [
            'Hello World.',
            12,
            '…',
            false,
            false,
            'Hello World.',
        ];
    }

    /**
     * @test
     * @dataProvider cropDataProvider
     */
    public function crop(
        string $string,
        int    $length,
        string $appendix,
        bool   $respectWordBoundaries,
        bool   $respectHtml,
        string $expectedResult,
    ): void {
        self::assertEquals(
            $expectedResult,
            StringUtility::crop($string, $length, $appendix, $respectWordBoundaries, $respectHtml)
        );
    }
}
