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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PSBits\Foundation\Service\GlobalVariableProviders\EarlyAccessConstantsProvider;
use PSBits\Foundation\Service\GlobalVariableService;
use PSBits\Foundation\Tests\Examples\BackedEnum;
use PSBits\Foundation\Tests\Examples\Enum;
use PSBits\Foundation\Utility\Typo3VersionUtility;
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

    /**
     * @var array<string, mixed>|null
     */
    private ?array $savedTca = null;

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

    #[Test]
    #[DataProvider('processDataProvider')]
    public function process(string $xml, string $expectedResult): void
    {
        self::assertEquals($expectedResult, $this->subject->processMarkers($xml));
    }

    /**
     * On v14, plugins are registered as their own content type, so the data structure is applied to that type and
     * the flexform field is added to its showitem.
     */
    #[Test]
    public function registerAppliesTheFlexFormToThePluginContentTypeOnV14(): void
    {
        if (!Typo3VersionUtility::isAtLeast('14.0')) {
            self::markTestSkipped('This behavior only applies to TYPO3 v14 or higher.');
        }

        $GLOBALS['TCA']['tt_content']['types']['poems_poem'] = [
            'showitem' => '--palette--;;headers, bodytext',
        ];

        $this->subject->register('<T3DataStructure/>', 'Poems_Poem');

        $type = $GLOBALS['TCA']['tt_content']['types']['poems_poem'];
        self::assertSame('<T3DataStructure/>', $type['columnsOverrides']['pi_flexform']['config']['ds']);
        self::assertStringContainsString('--div--;core.form.tabs:plugin, pi_flexform', $type['showitem']);
    }

    /**
     * Below v14, the flexform is applied as a subtype of the list content type. addPiFlexFormValue() only extends the
     * existing column-level ds array, so the test pre-seeds it.
     */
    #[Test]
    public function registerAppliesTheFlexFormToTheListTypeSubtypeBelowV14(): void
    {
        if (Typo3VersionUtility::isAtLeast('14.0')) {
            self::markTestSkipped('This behavior only applies below TYPO3 v14.');
        }

        $GLOBALS['TCA']['tt_content']['columns']['pi_flexform']['config']['ds'] = [];

        $this->subject->register('<T3DataStructure/>', 'Poems_Poem');

        $type = $GLOBALS['TCA']['tt_content']['types']['list'];
        self::assertSame('pi_flexform', $type['subtypes_addlist']['poems_poem']);
        self::assertSame(
            '<T3DataStructure/>',
            $GLOBALS['TCA']['tt_content']['columns']['pi_flexform']['config']['ds']['poems_poem,list']
        );
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->savedTca = $GLOBALS['TCA'] ?? null;
        $providerMock   = $this->getMockBuilder(EarlyAccessConstantsProvider::class)
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
        if (null === $this->savedTca) {
            unset($GLOBALS['TCA']);
        } else {
            $GLOBALS['TCA'] = $this->savedTca;
        }
        $this->savedTca = null;

        GlobalVariableService::clearCache();
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }
}
