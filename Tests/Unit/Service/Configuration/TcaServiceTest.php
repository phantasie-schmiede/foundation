<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Tests\Unit\Service\Configuration;

use Countable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PSBits\Foundation\Data\ExtensionInformationInterface;
use PSBits\Foundation\Data\PluginConfiguration;
use PSBits\Foundation\Service\Configuration\TcaService;
use PSBits\Foundation\Service\ExtensionInformationService;
use PSBits\Foundation\Tests\Examples\Domain\Model\AllTcaAttributesModel;
use PSBits\Foundation\Tests\Examples\Domain\Model\ChildTcaModel;
use PSBits\Foundation\Tests\Examples\Domain\Model\ForeignTableModel;
use PSBits\Foundation\Tests\Examples\Domain\Model\ParentTcaModel;
use PSBits\Foundation\Utility\Localization\LoggingUtility;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Extbase\Domain\Model\Category;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ClassesConfiguration;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class TcaServiceTest
 *
 * Unit tests for the standalone (non attribute driven) parts of TcaService.
 * Changes to $GLOBALS['TCA'] are restored automatically through the global
 * backup of the unit test suite.
 *
 * @package PSBits\Foundation\Tests\Unit\Service\Configuration
 */
class TcaServiceTest extends UnitTestCase
{
    private const string TABLE_NAME = 'tx_unit_test_table';

    private ExtensionInformationService $extensionInformationService;
    private PackageManager              $packageManager;
    private TcaService                  $subject;

    /**
     * @var PackageInterface[]
     */
    private array $activePackages = [];

    protected function setUp(): void
    {
        parent::setUp();
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->method('getActivePackages')
            ->willReturnCallback(fn(): array => $this->activePackages);
        $this->extensionInformationService = $this->createMock(ExtensionInformationService::class);
        $this->packageManager              = $packageManager;
        $this->subject                     = new TcaService($this->extensionInformationService, $packageManager);
        $this->resetStaticState();
    }

    protected function tearDown(): void
    {
        $this->resetStaticState();
        $this->resetLoggingState();
        $languageDirectory = rtrim(Environment::getPublicPath(), '/') . '/test_lang';

        if (is_dir($languageDirectory)) {
            @unlink($languageDirectory . '/xlf');
            @rmdir($languageDirectory);
        }
        parent::tearDown();
    }

    private function resetStaticState(): void
    {
        $classTableMapping = new ReflectionProperty(TcaService::class, 'classTableMapping');
        $classTableMapping->setValue(null, []);
        $allowCaching = new ReflectionProperty(TcaService::class, 'allowCaching');
        $allowCaching->setValue(null, true);
    }

    private function resetLoggingState(): void
    {
        $logMissingLanguageLabels = new ReflectionProperty(LoggingUtility::class, 'logMissingLanguageLabels');
        $logMissingLanguageLabels->setValue(null, null);
    }

    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function isPaletteReferenceDataProvider(): array
    {
        return [
            'palette reference with matching identifier' => [
                '--palette--;main;main_palette',
                'main_palette',
                true,
            ],
            'palette reference with other identifier'    => [
                '--palette--;main;other_palette',
                'main_palette',
                false,
            ],
            'palette reference without identifier'       => [
                '--palette--;main',
                'main_palette',
                false,
            ],
            'plain field is no palette reference'        => [
                'my_field',
                'main_palette',
                false,
            ],
            'whitespace around parts is trimmed'         => [
                ' --palette--; main ; main_palette ',
                'main_palette',
                true,
            ],
        ];
    }

    #[Test]
    #[DataProvider('isPaletteReferenceDataProvider')]
    public function isPaletteReference(string $showItem, string $paletteIdentifier, bool $expected): void
    {
        $method = new ReflectionMethod(TcaService::class, 'isPaletteReference');

        self::assertSame($expected, $method->invoke(null, $showItem, $paletteIdentifier));
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
    public function convertClassNameToTableNameReturnsNameFromTableAttribute(): void
    {
        self::assertSame(
            'tx_foundation_all_tca_attributes',
            $this->subject->convertClassNameToTableName(AllTcaAttributesModel::class)
        );
    }

    #[Test]
    public function convertClassNameToTableNameFallsBackToNamingConvention(): void
    {
        self::assertSame(
            'tx_foundation_data_pluginconfiguration',
            $this->subject->convertClassNameToTableName(PluginConfiguration::class)
        );
    }

    #[Test]
    public function convertClassNameToTableNameSkipsVendorAndProductForCoreClasses(): void
    {
        self::assertSame(
            'tx_extbase_domain_model_category',
            $this->subject->convertClassNameToTableName(Category::class)
        );
    }

    #[Test]
    public function convertClassNameToTableNameUsesClassesConfigurationIfAvailable(): void
    {
        $this->injectClassesConfiguration([
            AllTcaAttributesModel::class => [
                'tableName' => 'custom_table',
            ],
        ]);

        self::assertSame('custom_table', $this->subject->convertClassNameToTableName(AllTcaAttributesModel::class));
    }

    #[Test]
    public function convertPropertyNameToColumnNameConvertsCamelCaseWithoutClassName(): void
    {
        self::assertSame('my_field_name', $this->subject->convertPropertyNameToColumnName('myFieldName'));
    }

    #[Test]
    public function convertPropertyNameToColumnNameReturnsNameFromFieldAttribute(): void
    {
        self::assertSame(
            'mapped_field',
            $this->subject->convertPropertyNameToColumnName('mappedField', AllTcaAttributesModel::class)
        );
    }

    #[Test]
    public function convertPropertyNameToColumnNameFallsBackToConventionWithoutFieldAttribute(): void
    {
        self::assertSame(
            'text_field',
            $this->subject->convertPropertyNameToColumnName('textField', AllTcaAttributesModel::class)
        );
    }

    #[Test]
    public function convertPropertyNameToColumnNameUsesClassesConfigurationIfAvailable(): void
    {
        $this->injectClassesConfiguration([
            AllTcaAttributesModel::class => [
                'properties' => [
                    'textField' => [
                        'fieldName' => 'custom_text',
                    ],
                ],
            ],
        ]);

        self::assertSame(
            'custom_text',
            $this->subject->convertPropertyNameToColumnName('textField', AllTcaAttributesModel::class)
        );
    }

    #[Test]
    public function getClassesTableMappingBuildsMappingFromExtensionInformation(): void
    {
        $extensionInformation = $this->createMock(ExtensionInformationInterface::class);
        $extensionInformation->method('getExtensionName')
            ->willReturn('foundation');
        $extensionInformationService = $this->createMock(ExtensionInformationService::class);
        $extensionInformationService->method('getAllExtensionInformation')
            ->willReturn([$extensionInformation]);
        $extensionInformationService->method('getDomainModelClassNames')
            ->willReturn([
                AllTcaAttributesModel::class,
                ForeignTableModel::class,
                AbstractEntity::class,
                Countable::class,
            ]);
        $this->subject = new TcaService($extensionInformationService, $this->packageManager);

        $mapping = $this->subject->getClassesTableMapping();

        self::assertSame(
            [
                'tca'          => [
                    AllTcaAttributesModel::class => 'tx_foundation_all_tca_attributes',
                ],
                'tcaOverrides' => [
                    ForeignTableModel::class => 'tx_foreign_thing',
                ],
            ],
            $mapping
        );
    }

    #[Test]
    public function getClassesTableMappingReturnsCachedMappingWithoutRebuilding(): void
    {
        $cachedMapping = [
            'tca' => [
                AllTcaAttributesModel::class => 'tx_foundation_all_tca_attributes',
            ],
        ];
        $classTableMapping = new ReflectionProperty(TcaService::class, 'classTableMapping');
        $classTableMapping->setValue(null, $cachedMapping);
        $extensionInformationService = $this->createMock(ExtensionInformationService::class);
        $extensionInformationService->expects(self::never())
            ->method('getAllExtensionInformation');
        $this->subject = new TcaService($extensionInformationService, $this->packageManager);

        self::assertSame($cachedMapping, $this->subject->getClassesTableMapping());
    }

    #[Test]
    public function convertTableNameToClassNamesFindsMappedClassNames(): void
    {
        $classTableMapping = new ReflectionProperty(TcaService::class, 'classTableMapping');
        $classTableMapping->setValue(null, [
            'tca'          => [
                AllTcaAttributesModel::class => 'tx_foundation_all_tca_attributes',
            ],
            'tcaOverrides' => [
                ForeignTableModel::class => 'tx_foreign_thing',
            ],
        ]);

        self::assertSame([AllTcaAttributesModel::class], $this->subject->convertTableNameToClassNames('tx_foundation_all_tca_attributes'));
        self::assertSame([ForeignTableModel::class], $this->subject->convertTableNameToClassNames('tx_foreign_thing'));
        self::assertSame([], $this->subject->convertTableNameToClassNames('tx_unknown_table'));
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

    #[Test]
    public function inheritPropertiesFromParentClassesMergesParentProperties(): void
    {
        $method  = new ReflectionMethod(TcaService::class, 'inheritPropertiesFromParentClasses');
        $classes = $method->invoke($this->subject, [
            ChildTcaModel::class  => [
                'properties' => [
                    'childProperty'  => [
                        'fieldName' => 'child_property',
                    ],
                    'parentProperty' => [
                        'fieldName' => 'child_override',
                    ],
                ],
            ],
            ParentTcaModel::class => [
                'properties' => [
                    'parentProperty' => [
                        'fieldName' => 'parent_property',
                    ],
                ],
            ],
        ]);

        self::assertSame(
            [
                'parentProperty' => [
                    'fieldName' => 'child_override',
                ],
                'childProperty'  => [
                    'fieldName' => 'child_property',
                ],
            ],
            $classes[ChildTcaModel::class]['properties']
        );
    }

    #[Test]
    public function checkClassesConfigurationLoadsClassesFilesFromActivePackages(): void
    {
        $packagePath = rtrim(Environment::getProjectPath(), '/') . '/var/tca_service_test/' . uniqid();
        mkdir($packagePath . '/Configuration/Extbase/Persistence', 0777, true);
        file_put_contents(
            $packagePath . '/Configuration/Extbase/Persistence/Classes.php',
            '<?php return ' . var_export([
                ChildTcaModel::class  => [
                    'properties' => [
                        'childProperty' => [],
                    ],
                ],
                ParentTcaModel::class => [
                    'properties' => [
                        'parentProperty' => [],
                    ],
                ],
            ], true) . ';'
        );

        $package = $this->createMock(PackageInterface::class);
        $package->method('getPackagePath')
            ->willReturn($packagePath . '/');
        $this->activePackages = [$package];

        $method = new ReflectionMethod(TcaService::class, 'checkClassesConfiguration');
        $method->invoke($this->subject);

        $classesConfiguration = new ReflectionProperty(TcaService::class, 'classesConfiguration');
        /** @var ClassesConfiguration $configuration */
        $configuration = $classesConfiguration->getValue($this->subject);
        self::assertTrue($configuration->hasClass(ChildTcaModel::class));
        self::assertArrayHasKey(
            'parentProperty',
            $configuration->getConfigurationFor(ChildTcaModel::class)['properties']
        );

        unlink($packagePath . '/Configuration/Extbase/Persistence/Classes.php');
        rmdir($packagePath . '/Configuration/Extbase/Persistence');
        rmdir($packagePath . '/Configuration/Extbase');
        rmdir($packagePath . '/Configuration');
        rmdir($packagePath);
    }

    private function injectClassesConfiguration(array $configuration): void
    {
        $classesConfiguration = new ReflectionProperty(TcaService::class, 'classesConfiguration');
        $classesConfiguration->setValue($this->subject, new ClassesConfiguration($configuration));
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
