<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Service\Configuration;

use Generator;
use PSBits\Foundation\Tests\Examples\BackedEnum;
use PSBits\Foundation\Tests\Examples\Enum;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class FlexFormServiceTest
 *
 * @package PSBits\Foundation\Service\Configuration
 */
class FlexFormServiceTest extends UnitTestCase
{
    public const string TEST_CONSTANT = 'testValue';

    private FlexFormService $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new FlexFormService();
    }

    public static function processMarkersDataProvider(): Generator
    {
        yield 'no markers' => [
            '<T3DataStructure><sheets/></T3DataStructure>',
            '<T3DataStructure><sheets/></T3DataStructure>',
        ];
        yield 'php constant' => [
            '<value>###\PSBits\Foundation\Service\Configuration\FlexFormServiceTest::TEST_CONSTANT###</value>',
            '<value>testValue</value>',
        ];
        yield 'backed enum case' => [
            '<value>###\PSBits\Foundation\Tests\Examples\BackedEnum::Delta###</value>',
            '<value>' . BackedEnum::Delta->value . '</value>',
        ];
        yield 'unit enum case' => [
            '<value>###\PSBits\Foundation\Tests\Examples\Enum::Alpha###</value>',
            '<value>' . Enum::Alpha->name . '</value>',
        ];
        yield 'multiple markers' => [
            '<a>###\PSBits\Foundation\Tests\Examples\BackedEnum::Epsilon###</a><b>###\PSBits\Foundation\Tests\Examples\BackedEnum::Zeta###</b>',
            '<a>' . BackedEnum::Epsilon->value . '</a><b>' . BackedEnum::Zeta->value . '</b>',
        ];
        yield 'unknown marker is kept as-is' => [
            '<value>###UNKNOWN_MARKER###</value>',
            '<value>###UNKNOWN_MARKER###</value>',
        ];
    }

    /**
     * @test
     * @dataProvider processMarkersDataProvider
     */
    public function processMarkers(string $xml, string $expectedResult): void
    {
        self::assertEquals($expectedResult, $this->subject->processMarkers($xml));
    }
}
