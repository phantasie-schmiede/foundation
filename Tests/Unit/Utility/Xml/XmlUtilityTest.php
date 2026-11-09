<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Utility\Xml;

use DOMException;
use Error;
use Generator;
use InvalidArgumentException as BaseInvalidArgumentException;
use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use RuntimeException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use TYPO3\CMS\Extbase\Configuration\Exception\NoServerRequestGivenException;

/**
 * Class XmlUtilityTest
 *
 * @package PSBits\Foundation\Utility\Xml
 */
class XmlUtilityTest extends UnitTestCase
{
    public static function convertFromAndToXmlDataProvider(): Generator
    {
        yield 'complex XML' => [
            file_get_contents(__DIR__ . '/Data/ComplexXml.xml'),
        ];
        yield 'simple XML' => [
            file_get_contents(__DIR__ . '/Data/SimpleXml.xml'),
        ];
    }

    public static function convertFromXmlDataProvider(): Generator
    {
        yield 'complex XML' => [
            include __DIR__ . '/Data/ComplexXml.php',
            file_get_contents(__DIR__ . '/Data/ComplexXml.xml'),
        ];
        yield 'simple XML' => [
            include __DIR__ . '/Data/SimpleXml.php',
            file_get_contents(__DIR__ . '/Data/SimpleXml.xml'),
        ];
    }

    public static function convertToXmlDataProvider(): Generator
    {
        yield 'complex XML' => [
            include __DIR__ . '/Data/ComplexXml.php',
        ];
        yield 'simple XML' => [
            include __DIR__ . '/Data/SimpleXml.php',
        ];
    }

    /**
     * @throws BaseInvalidArgumentException
     * @throws ContainerExceptionInterface
     * @throws DOMException
     * @throws Error
     * @throws ExpectationFailedException
     * @throws JsonException
     * @throws NoServerRequestGivenException // t3:14
     * @throws ReflectionException
     * @throws RuntimeException
     */
    #[Test]
    #[DataProvider('convertFromAndToXmlDataProvider')]
    public function convertFromAndToXml(string $xml): void
    {
        $array = XmlUtility::convertFromXml($xml);
        self::assertEquals(
            $array,
            XmlUtility::convertFromXml(XmlUtility::convertToXml($array))
        );
    }

    /**
     * @param array<string, mixed> $expectedResult
     * @throws BaseInvalidArgumentException
     * @throws ContainerExceptionInterface
     * @throws Error
     * @throws ExpectationFailedException
     * @throws JsonException
     * @throws NoServerRequestGivenException // t3:14
     * @throws ReflectionException
     * @throws RuntimeException
     */
    #[Test]
    #[DataProvider('convertFromXmlDataProvider')]
    public function convertFromXml(array $expectedResult, string $xml): void
    {
        self::assertEquals(
            $expectedResult,
            XmlUtility::convertFromXml($xml)
        );
    }

    /**
     * @param array<string, mixed> $array
     * @throws BaseInvalidArgumentException
     * @throws ContainerExceptionInterface
     * @throws DOMException
     * @throws Error
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws JsonException
     * @throws NoServerRequestGivenException // t3:14
     * @throws ReflectionException
     * @throws RuntimeException
     */
    #[Test]
    #[DataProvider('convertToXmlDataProvider')]
    public function convertToXml(array $array): void
    {
        $xml = XmlUtility::convertToXml($array, wellFormatted: false);
        self::assertStringStartsWith(XmlUtility::XML_HEADER, $xml);
        self::assertEquals(
            $array,
            XmlUtility::convertFromXml($xml)
        );
    }
}
