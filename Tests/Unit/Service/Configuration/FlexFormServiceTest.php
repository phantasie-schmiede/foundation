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
use PSBits\Foundation\Service\GlobalVariableProviders\EarlyAccessConstantsProvider;
use PSBits\Foundation\Service\GlobalVariableService;
use PSBits\Foundation\Tests\Examples\BackedEnum;
use PSBits\Foundation\Tests\Examples\Enum;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class FlexFormServiceTest
 *
 * @package PSBits\Foundation\Service\Configuration
 */
class FlexFormServiceTest extends UnitTestCase
{
    public const int    TEST_INT_CONSTANT    = 42;
    public const string TEST_STRING_CONSTANT = 'test_constant_value';
    private FlexFormService $subject;

    public static function processDataProvider(): Generator
    {
        yield 'XML without markers is returned unchanged' => [
            '<T3DataStructure><sheets/></T3DataStructure>',
            '<T3DataStructure><sheets/></T3DataStructure>',
        ];

        yield 'EarlyAccessConstants marker is replaced' => [
            '###EAC:flexForm.value###',
            'early_access_value',
        ];

        yield 'EarlyAccessConstants nested marker is replaced' => [
            '###EAC:flexForm.nested.answer###',
            '42',
        ];

        yield 'unknown EarlyAccessConstants path is left unchanged' => [
            '###EAC:flexForm.unknown###',
            '###EAC:flexForm.unknown###',
        ];

        yield 'backed enum case marker is replaced with backed value' => [
            '###\PSBits\Foundation\Tests\Examples\BackedEnum::Delta###',
            BackedEnum::Delta->value,
        ];

        yield 'pure enum case marker is replaced with case name' => [
            '###\PSBits\Foundation\Tests\Examples\Enum::Alpha###',
            Enum::Alpha->name,
        ];

        yield 'string class constant marker is replaced with constant value' => [
            '###\PSBits\Foundation\Service\Configuration\FlexFormServiceTest::TEST_STRING_CONSTANT###',
            self::TEST_STRING_CONSTANT,
        ];

        yield 'int class constant marker is replaced with string representation of constant value' => [
            '###\PSBits\Foundation\Service\Configuration\FlexFormServiceTest::TEST_INT_CONSTANT###',
            (string)self::TEST_INT_CONSTANT,
        ];

        yield 'unknown marker expression is left unchanged' => [
            '###\NonExistent\Class::SOMETHING###',
            '###\NonExistent\Class::SOMETHING###',
        ];

        yield 'unrecognised marker format is left unchanged' => [
            '###just_some_text###',
            '###just_some_text###',
        ];

        yield 'multiple markers in XML are all replaced' => [
            '<numIndex index="0">###\PSBits\Foundation\Tests\Examples\BackedEnum::Delta###</numIndex>' . '<numIndex index="1">###\PSBits\Foundation\Tests\Examples\BackedEnum::Epsilon###</numIndex>',
            '<numIndex index="0">' . BackedEnum::Delta->value . '</numIndex>' . '<numIndex index="1">' . BackedEnum::Epsilon->value . '</numIndex>',
        ];

        yield 'mixed markers and plain content' => [
            '<item><value>###\PSBits\Foundation\Tests\Examples\BackedEnum::Zeta###</value><label>My Label</label></item>',
            '<item><value>' . BackedEnum::Zeta->value . '</value><label>My Label</label></item>',
        ];
    }

    /**
     * @test
     * @dataProvider processDataProvider
     */
    public function process(string $xml, string $expectedResult): void
    {
        self::assertEquals($expectedResult, $this->subject->processMarkers($xml));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $providerMock = $this->getMockBuilder(EarlyAccessConstantsProvider::class)
            ->onlyMethods(['getGlobalVariables'])
            ->getMock();

        $providerMock->method('getGlobalVariables')
            ->willReturn([
                'flexForm' => [
                    'nested' => [
                        'answer' => 42,
                    ],
                    'value'  => 'early_access_value',
                ],
            ]);

        GeneralUtility::addInstance(EarlyAccessConstantsProvider::class, $providerMock);
        GlobalVariableService::registerGlobalVariableProvider(EarlyAccessConstantsProvider::class);
        GlobalVariableService::clearCache();
        $this->subject = new FlexFormService();
    }

    protected function tearDown(): void
    {
        GlobalVariableService::clearCache();
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }
}
