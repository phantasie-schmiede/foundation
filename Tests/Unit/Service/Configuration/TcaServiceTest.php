<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Tests\Unit\Service\Configuration;

use PHPUnit\Framework\Attributes\Test;
use PSBits\Foundation\Service\Configuration\TcaService;
use PSBits\Foundation\Service\ExtensionInformationService;
use PSBits\Foundation\Tests\Examples\Domain\Model\AllTcaAttributesModel;
use PSBits\Foundation\Utility\Localization\LoggingUtility;
use ReflectionProperty;
use RuntimeException;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class TcaServiceTest
 *
 * Unit tests for the TcaService facade.
 * Changes to $GLOBALS['TCA'] are restored automatically through the global
 * backup of the unit test suite.
 *
 * @package PSBits\Foundation\Tests\Unit\Service\Configuration
 */
class TcaServiceTest extends UnitTestCase
{
    private const string TABLE_NAME = 'tx_unit_test_table';

    private TcaService $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->method('getActivePackages')
            ->willReturn([]);
        $extensionInformationService = $this->createMock(ExtensionInformationService::class);
        $this->subject               = new TcaService($extensionInformationService, $packageManager);
    }

    protected function tearDown(): void
    {
        $this->resetLoggingState();
        $languageDirectory = rtrim(Environment::getPublicPath(), '/') . '/test_lang';

        if (is_dir($languageDirectory)) {
            @unlink($languageDirectory . '/xlf');
            @rmdir($languageDirectory);
        }
        parent::tearDown();
    }

    private function resetLoggingState(): void
    {
        $logMissingLanguageLabels = new ReflectionProperty(LoggingUtility::class, 'logMissingLanguageLabels');
        $logMissingLanguageLabels->setValue(null, null);
    }

    #[Test]
    public function checkIfTableNameIsSetThrowsWithoutTableName(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(1646899798);
        $this->expectExceptionMessageMatches('/You have to specify a table with setTable\(\) first!$/');

        $this->subject->checkIfTableNameIsSet();
    }

    #[Test]
    public function setTableNameAcceptsPlainTableName(): void
    {
        $this->subject->setTableName(self::TABLE_NAME);

        $tableName = new ReflectionProperty(TcaService::class, 'tableName');
        self::assertSame(self::TABLE_NAME, $tableName->getValue($this->subject));
        $this->subject->checkIfTableNameIsSet();
    }

    #[Test]
    public function setTableNameConvertsFullyQualifiedClassName(): void
    {
        $this->subject->setTableName(AllTcaAttributesModel::class);

        $tableName = new ReflectionProperty(TcaService::class, 'tableName');
        self::assertSame('tx_foundation_all_tca_attributes', $tableName->getValue($this->subject));
    }

    #[Test]
    public function addColumnConfigurationAddsColumnToTca(): void
    {
        $GLOBALS['TCA'][self::TABLE_NAME] = ['columns' => []];
        $this->subject->setTableName(self::TABLE_NAME);

        $this->subject->addColumnConfiguration('my_column', [
            'label'  => 'My Column',
            'config' => [
                'type' => 'string',
            ],
        ]);

        self::assertSame(
            [
                'label'  => 'My Column',
                'config' => [
                    'type' => 'string',
                ],
            ],
            $GLOBALS['TCA'][self::TABLE_NAME]['columns']['my_column']
        );
    }

    #[Test]
    public function addColumnConfigurationThrowsWithoutTableName(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(1646899798);

        $this->subject->addColumnConfiguration('my_column', ['config' => []]);
    }

    #[Test]
    public function addToPaletteAppendsFieldsToExistingPalette(): void
    {
        $GLOBALS['TCA'][self::TABLE_NAME] = [
            'columns'  => [
                'field_a' => ['config' => []],
                'field_b' => ['config' => []],
            ],
            'palettes' => [
                'my_palette' => [
                    'showitem' => 'field_a',
                ],
            ],
        ];
        $this->subject->setTableName(self::TABLE_NAME);

        $this->subject->addToPalette('my_palette', ['field_b']);

        self::assertSame('field_a, field_b', $GLOBALS['TCA'][self::TABLE_NAME]['palettes']['my_palette']['showitem']);
    }

    #[Test]
    public function addToPaletteInsertsLineBreakAfterReferenceField(): void
    {
        $GLOBALS['TCA'][self::TABLE_NAME] = [
            'columns'  => [
                'field_a' => ['config' => []],
                'field_b' => ['config' => []],
            ],
            'palettes' => [
                'my_palette' => [
                    'showitem' => 'field_a',
                ],
            ],
        ];
        $this->subject->setTableName(self::TABLE_NAME);

        $this->subject->addToPalette('my_palette', ['field_b'], 'newLineAfter:field_a');

        self::assertSame(
            'field_a, --linebreak--, field_b',
            $GLOBALS['TCA'][self::TABLE_NAME]['palettes']['my_palette']['showitem']
        );
    }

    #[Test]
    public function addToPaletteInsertsLineBreakBeforeReferenceField(): void
    {
        $GLOBALS['TCA'][self::TABLE_NAME] = [
            'columns'  => [
                'field_a' => ['config' => []],
                'field_b' => ['config' => []],
            ],
            'palettes' => [
                'my_palette' => [
                    'showitem' => 'field_a',
                ],
            ],
        ];
        $this->subject->setTableName(self::TABLE_NAME);

        $this->subject->addToPalette('my_palette', ['field_b'], 'newLineBefore:field_a');

        self::assertSame(
            'field_b, --linebreak--, field_a',
            $GLOBALS['TCA'][self::TABLE_NAME]['palettes']['my_palette']['showitem']
        );
    }

    #[Test]
    public function createPaletteCreatesPaletteWithoutLabel(): void
    {
        $GLOBALS['TCA'][self::TABLE_NAME] = [];
        $this->subject->setTableName(self::TABLE_NAME);

        $this->subject->createPalette('my_palette');

        self::assertSame(['showitem' => ''], $GLOBALS['TCA'][self::TABLE_NAME]['palettes']['my_palette']);
    }

    #[Test]
    public function createPaletteStoresPlainTextLabelAndDescription(): void
    {
        $GLOBALS['TCA'][self::TABLE_NAME] = [];
        $this->subject->setTableName(self::TABLE_NAME);

        $this->subject->createPalette('my_palette', 'My Label', 'My Description');

        self::assertSame(
            [
                'showitem'    => '',
                'label'       => 'My Label',
                'description' => 'My Description',
            ],
            $GLOBALS['TCA'][self::TABLE_NAME]['palettes']['my_palette']
        );
    }

    #[Test]
    public function createPaletteFallsBackToExistingDefaultLabelTranslation(): void
    {
        $GLOBALS['TCA'][self::TABLE_NAME] = [];
        $this->subject->setTableName(self::TABLE_NAME);
        $this->setLoggingState(false);
        $this->writeLanguageFile([
            'palette.my_palette.label',
            'palette.my_palette.description',
        ]);
        $defaultLabelPath = new ReflectionProperty(TcaService::class, 'defaultLabelPath');
        $defaultLabelPath->setValue($this->subject, 'LLL:test_lang/xlf:');

        $this->subject->createPalette('my_palette', 'LLL:EXT:nonexistent/missing.xlf:label', 'Plain Description');

        self::assertSame(
            [
                'showitem'    => '',
                'label'       => 'LLL:test_lang/xlf:palette.my_palette.label',
                'description' => 'Plain Description',
            ],
            $GLOBALS['TCA'][self::TABLE_NAME]['palettes']['my_palette']
        );
    }

    #[Test]
    public function createPaletteSkipsMissingLanguageLabels(): void
    {
        $GLOBALS['TCA'][self::TABLE_NAME] = [];
        $this->subject->setTableName(self::TABLE_NAME);
        $this->setLoggingState(false);

        $this->subject->createPalette('my_palette', 'LLL:EXT:nonexistent/missing.xlf:label');

        self::assertSame(['showitem' => ''], $GLOBALS['TCA'][self::TABLE_NAME]['palettes']['my_palette']);
    }

    #[Test]
    public function getConfigurationForPropertyOfDomainModelReturnsColumnConfiguration(): void
    {
        $columnConfiguration = [
            'label'  => 'Mapped Field',
            'config' => [
                'type' => 'string',
            ],
        ];
        $GLOBALS['TCA']['tx_foundation_all_tca_attributes'] = [
            'columns' => [
                'mapped_field' => $columnConfiguration,
            ],
        ];

        $result = $this->subject->getConfigurationForPropertyOfDomainModel(new AllTcaAttributesModel(), 'mappedField');

        self::assertSame($columnConfiguration, $result);
    }

    #[Test]
    public function getConfigurationForPropertyOfDomainModelThrowsForUnknownColumn(): void
    {
        $GLOBALS['TCA']['tx_foundation_all_tca_attributes'] = [
            'columns' => [],
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(1660914340);

        $this->subject->getConfigurationForPropertyOfDomainModel(new AllTcaAttributesModel(), 'mappedField');
    }

    private function setLoggingState(bool $state): void
    {
        $logMissingLanguageLabels = new ReflectionProperty(LoggingUtility::class, 'logMissingLanguageLabels');
        $logMissingLanguageLabels->setValue(null, $state);
    }

    private function writeLanguageFile(array $labelIds): void
    {
        $languageDirectory = rtrim(Environment::getPublicPath(), '/') . '/test_lang';
        mkdir($languageDirectory, 0777, true);

        $transUnits = '';

        foreach ($labelIds as $labelId) {
            $transUnits .= sprintf(
                '<trans-unit id="%s"><source>%s</source><target>%s</target></trans-unit>',
                htmlspecialchars($labelId),
                htmlspecialchars($labelId),
                htmlspecialchars($labelId)
            );
        }

        file_put_contents(
            $languageDirectory . '/xlf',
            <<<XML
            <?xml version="1.0" encoding="utf-8" standalone="yes"?>
            <xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:1.2">
                <file date="2026-01-01T00:00:00Z" source-language="en" target-language="de" datatype="plaintext" original="i18n">
                    <body>
                        {$transUnits}
                    </body>
                </file>
            </xliff>
            XML
        );
    }
}
