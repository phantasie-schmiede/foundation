<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Tests\Unit\Service\Configuration\Tca;

use Countable;
use InvalidArgumentException as BaseInvalidArgumentException;
use LogicException;
use PHPUnit\Event\NoPreviousThrowableException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Exception as FrameworkException;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\InvalidArgumentException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\IncompatibleReturnValueException;
use PHPUnit\Framework\MockObject\MethodCannotBeConfiguredException;
use PHPUnit\Framework\MockObject\MethodNameAlreadyConfiguredException;
use PSBits\Foundation\Data\ExtensionInformationInterface;
use PSBits\Foundation\Data\PluginConfiguration;
use PSBits\Foundation\Exceptions\ImplementationException;
use PSBits\Foundation\Service\Configuration\Tca\NameResolver;
use PSBits\Foundation\Service\ExtensionInformationService;
use PSBits\Foundation\Tests\Examples\Domain\Model\AllTcaAttributesModel;
use PSBits\Foundation\Tests\Examples\Domain\Model\ChildTcaModel;
use PSBits\Foundation\Tests\Examples\Domain\Model\ForeignTableModel;
use PSBits\Foundation\Tests\Examples\Domain\Model\ParentTcaModel;
use ReflectionException;
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
 * Class NameResolverTest
 *
 * Unit tests for the name resolution part of the TCA configuration (class name to table name, property name to
 * column name, class to table mapping).
 *
 * @package PSBits\Foundation\Tests\Unit\Service\Configuration\Tca
 */
class NameResolverTest extends UnitTestCase
{
    private ExtensionInformationService $extensionInformationService;
    private PackageManager              $packageManager;
    private NameResolver                $subject;

    /**
     * @var PackageInterface[]
     */
    private array $activePackages = [];

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NoPreviousThrowableException
     * @throws ReflectionException
     */
    protected function setUp(): void
    {
        parent::setUp();
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->method('getActivePackages')
            ->willReturnCallback(fn(): array => $this->activePackages);
        $this->extensionInformationService = $this->createMock(ExtensionInformationService::class);
        $this->packageManager              = $packageManager;
        $this->subject                     = new NameResolver($this->extensionInformationService, $packageManager);
        $this->resetStaticState();
    }

    /**
     * @throws ReflectionException
     * @throws RuntimeException
     */
    protected function tearDown(): void
    {
        $this->resetStaticState();
        parent::tearDown();
    }

    /**
     * @throws ReflectionException
     */
    private function resetStaticState(): void
    {
        $classTableMapping = new ReflectionProperty(NameResolver::class, 'classTableMapping');
        $classTableMapping->setValue(null, []);
        $allowCaching = new ReflectionProperty(NameResolver::class, 'allowCaching');
        $allowCaching->setValue(null, true);
    }

    /**
     * @throws BaseInvalidArgumentException
     * @throws ExpectationFailedException
     * @throws ReflectionException
     */
    #[Test]
    public function convertClassNameToTableNameReturnsNameFromTableAttribute(): void
    {
        self::assertSame(
            'tx_foundation_all_tca_attributes',
            $this->subject->convertClassNameToTableName(AllTcaAttributesModel::class)
        );
    }

    /**
     * @throws BaseInvalidArgumentException
     * @throws ExpectationFailedException
     * @throws ReflectionException
     */
    #[Test]
    public function convertClassNameToTableNameFallsBackToNamingConvention(): void
    {
        self::assertSame(
            'tx_foundation_data_pluginconfiguration',
            $this->subject->convertClassNameToTableName(PluginConfiguration::class)
        );
    }

    /**
     * @throws BaseInvalidArgumentException
     * @throws ExpectationFailedException
     * @throws ReflectionException
     */
    #[Test]
    public function convertClassNameToTableNameSkipsVendorAndProductForCoreClasses(): void
    {
        self::assertSame(
            'tx_extbase_domain_model_category',
            $this->subject->convertClassNameToTableName(Category::class)
        );
    }

    /**
     * @throws BaseInvalidArgumentException
     * @throws ExpectationFailedException
     * @throws ReflectionException
     */
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

    /**
     * @throws BaseInvalidArgumentException
     * @throws ExpectationFailedException
     * @throws ReflectionException
     */
    #[Test]
    public function convertPropertyNameToColumnNameConvertsCamelCaseWithoutClassName(): void
    {
        self::assertSame('my_field_name', $this->subject->convertPropertyNameToColumnName('myFieldName'));
    }

    /**
     * @throws BaseInvalidArgumentException
     * @throws ExpectationFailedException
     * @throws ReflectionException
     */
    #[Test]
    public function convertPropertyNameToColumnNameReturnsNameFromFieldAttribute(): void
    {
        self::assertSame(
            'mapped_field',
            $this->subject->convertPropertyNameToColumnName('mappedField', AllTcaAttributesModel::class)
        );
    }

    /**
     * @throws BaseInvalidArgumentException
     * @throws ExpectationFailedException
     * @throws ReflectionException
     */
    #[Test]
    public function convertPropertyNameToColumnNameFallsBackToConventionWithoutFieldAttribute(): void
    {
        self::assertSame(
            'text_field',
            $this->subject->convertPropertyNameToColumnName('textField', AllTcaAttributesModel::class)
        );
    }

    /**
     * @throws BaseInvalidArgumentException
     * @throws ExpectationFailedException
     * @throws ReflectionException
     */
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

    /**
     * @throws Exception
     * @throws ExpectationFailedException
     * @throws ImplementationException
     * @throws IncompatibleReturnValueException
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws NoPreviousThrowableException
     * @throws ReflectionException
     */
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
        $this->subject = new NameResolver($extensionInformationService, $this->packageManager);

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

    /**
     * @throws Exception
     * @throws ExpectationFailedException
     * @throws ImplementationException
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws MethodCannotBeConfiguredException
     * @throws MethodNameAlreadyConfiguredException
     * @throws NoPreviousThrowableException
     * @throws ReflectionException
     */
    #[Test]
    public function getClassesTableMappingReturnsCachedMappingWithoutRebuilding(): void
    {
        $cachedMapping = [
            'tca' => [
                AllTcaAttributesModel::class => 'tx_foundation_all_tca_attributes',
            ],
        ];
        $classTableMapping = new ReflectionProperty(NameResolver::class, 'classTableMapping');
        $classTableMapping->setValue(null, $cachedMapping);
        $extensionInformationService = $this->createMock(ExtensionInformationService::class);
        $extensionInformationService->expects(self::never())
            ->method('getAllExtensionInformation');
        $this->subject = new NameResolver($extensionInformationService, $this->packageManager);

        self::assertSame($cachedMapping, $this->subject->getClassesTableMapping());
    }

    /**
     * @throws ExpectationFailedException
     * @throws ImplementationException
     * @throws LogicException
     * @throws ReflectionException
     */
    #[Test]
    public function convertTableNameToClassNamesFindsMappedClassNames(): void
    {
        $classTableMapping = new ReflectionProperty(NameResolver::class, 'classTableMapping');
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

    /**
     * @throws ExpectationFailedException
     */
    #[Test]
    public function inheritPropertiesFromParentClassesMergesParentProperties(): void
    {
        $method  = new ReflectionMethod(NameResolver::class, 'inheritPropertiesFromParentClasses');
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

    /**
     * @throws Exception
     * @throws ExpectationFailedException
     * @throws FrameworkException
     * @throws IncompatibleReturnValueException
     * @throws InvalidArgumentException
     * @throws NoPreviousThrowableException
     */
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

        $method = new ReflectionMethod(NameResolver::class, 'checkClassesConfiguration');
        $method->invoke($this->subject);

        $classesConfiguration = new ReflectionProperty(NameResolver::class, 'classesConfiguration');
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

    /**
     * @param array<string, mixed> $configuration
     */
    private function injectClassesConfiguration(array $configuration): void
    {
        $classesConfiguration = new ReflectionProperty(NameResolver::class, 'classesConfiguration');
        $classesConfiguration->setValue($this->subject, new ClassesConfiguration($configuration));
    }
}
