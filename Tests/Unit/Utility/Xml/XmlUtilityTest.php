<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Utility\Xml;

use Generator;
use JsonException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

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
            file_get_contents(__DIR__ . '/Data/ComplexXml.xml'),
        ];
        yield 'simple XML' => [
            include __DIR__ . '/Data/SimpleXml.php',
            file_get_contents(__DIR__ . '/Data/SimpleXml.xml'),
        ];
    }

    /**
     * @test
     * @dataProvider convertFromAndToXmlDataProvider
     *
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function convertFromAndToXml(string $xml): void
    {
        $array = XmlUtility::convertFromXml($xml);
        self::assertEquals(
            $xml,
            XmlUtility::convertToXml($array)
        );
    }

    /**
     * @test
     * @dataProvider convertFromXmlDataProvider
     *
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function convertFromXml(array $expectedResult, string $xml): void
    {
        self::assertEquals(
            $expectedResult,
            XmlUtility::convertFromXml($xml)
        );
    }

    /**
     * @test
     * @dataProvider convertToXmlDataProvider
     */
    public function convertToXml(array $array, string $expectedResult): void
    {
        self::assertEquals(
            $expectedResult,
            XmlUtility::convertToXml($array)
        );
    }
}
