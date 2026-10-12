<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Tests\Functional;

use JsonException;
use PHPUnit\Framework\Attributes\Test;
use PSBits\Foundation\Exceptions\MisconfiguredTcaException;
use PSBits\Foundation\Service\Configuration\Tca\Builder;
use PSBits\Foundation\Service\Configuration\TcaService;
use PSBits\Foundation\Tests\Examples\Domain\Model\AllTcaAttributesModel;
use PSBits\Foundation\Tests\Examples\Domain\Model\CoreFieldPositionModel;
use PSBits\Foundation\Tests\Examples\Domain\Model\DataObjectModel;
use PSBits\Foundation\Tests\Examples\Domain\Model\ExtendedTcaChildModel;
use PSBits\Foundation\Tests\Examples\Domain\Model\ExtendedTcaParentModel;
use PSBits\Foundation\Tests\Examples\Domain\Model\PositionLoopModel;
use PSBits\Foundation\Tests\Examples\Domain\Model\PositionReferenceModel;
use PSBits\Foundation\Tests\Examples\Domain\Model\ProtectedSortModel;
use PSBits\Foundation\Tests\Examples\Domain\Model\SortConflictModel;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use RuntimeException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Class TcaServiceTest
 *
 * @package PSBits\Foundation\Tests\Functional
 */
class TcaServiceTest extends FunctionalTestCase
{
    private const string TABLE_NAME_ALL_ATTRIBUTES      = 'tx_foundation_all_tca_attributes';
    private const string TABLE_NAME_CORE_FIELD_POSITION = 'tx_foundation_core_field_position';
    private const string TABLE_NAME_DATA_OBJECT         = 'tx_foundation_data_object';
    private const string TABLE_NAME_EXTENDED_TCA        = 'tx_foundation_extended_tca';
    private const string TABLE_NAME_POSITION_LOOP       = 'tx_foundation_position_loop';
    private const string TABLE_NAME_POSITION_REFERENCE  = 'tx_foundation_position_reference';
    private const string TABLE_NAME_PROTECTED_SORT      = 'tx_foundation_protected_sort';
    private const string TABLE_NAME_SORT_CONFLICT       = 'tx_foundation_sort_conflict';

    protected array $testExtensionsToLoad = [
        'typo3conf/ext/psbits/foundation',
    ];

    /**
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws JsonException
     * @throws MisconfiguredTcaException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    #[Test]
    public function buildFromAttributesCreatesExpectedTcaForDataObjectModel(): void
    {
        unset($GLOBALS['TCA'][self::TABLE_NAME_DATA_OBJECT]);

        $this->buildFromAttributes(DataObjectModel::class, self::TABLE_NAME_DATA_OBJECT, false);

        $actualTca   = $GLOBALS['TCA'][self::TABLE_NAME_DATA_OBJECT] ?? [];
        $expectedTca = require __DIR__ . '/Fixtures/ExpectedTcaForDataObjectModel.php';
        self::assertEquals($expectedTca, $actualTca);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws JsonException
     * @throws MisconfiguredTcaException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    #[Test]
    public function buildFromAttributesAllowsDefaultFieldsAsPositionReference(): void
    {
        unset($GLOBALS['TCA'][self::TABLE_NAME_CORE_FIELD_POSITION]);

        $this->buildFromAttributes(
            CoreFieldPositionModel::class,
            self::TABLE_NAME_CORE_FIELD_POSITION,
            false
        );

        $actualTca   = $GLOBALS['TCA'][self::TABLE_NAME_CORE_FIELD_POSITION] ?? [];
        $expectedTca = require __DIR__ . '/Fixtures/ExpectedTcaForCoreFieldPositionModel.php';
        self::assertEquals($expectedTca, $actualTca);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws JsonException
     * @throws MisconfiguredTcaException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    #[Test]
    public function buildFromAttributesAllowsUserPalettesAndTabsAsPositionReference(): void
    {
        unset($GLOBALS['TCA'][self::TABLE_NAME_POSITION_REFERENCE]);

        $this->buildFromAttributes(PositionReferenceModel::class, self::TABLE_NAME_POSITION_REFERENCE, false);

        $actualTca   = $GLOBALS['TCA'][self::TABLE_NAME_POSITION_REFERENCE] ?? [];
        $expectedTca = require __DIR__ . '/Fixtures/ExpectedTcaForPositionReferenceModel.php';
        self::assertEquals($expectedTca, $actualTca);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws JsonException
     * @throws MisconfiguredTcaException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    #[Test]
    public function buildFromAttributesCreatesExpectedTcaForExampleModelWithAllAttributes(): void
    {
        unset($GLOBALS['TCA'][self::TABLE_NAME_ALL_ATTRIBUTES]);

        $tcaService = GeneralUtility::makeInstance(TcaService::class);
        $tableName  = $tcaService->convertClassNameToTableName(AllTcaAttributesModel::class);
        self::assertSame(self::TABLE_NAME_ALL_ATTRIBUTES, $tableName);
        $this->buildFromAttributes(AllTcaAttributesModel::class, $tableName, false);

        $actualTca   = $GLOBALS['TCA'][self::TABLE_NAME_ALL_ATTRIBUTES] ?? [];
        $expectedTca = require __DIR__ . '/Fixtures/ExpectedTcaForAllTcaAttributesModel.php';
        self::assertEquals($expectedTca, $actualTca);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws JsonException
     * @throws MisconfiguredTcaException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    #[Test]
    public function buildFromAttributesCreatesExpectedTcaForExtendedTcaParentModel(): void
    {
        unset($GLOBALS['TCA'][self::TABLE_NAME_EXTENDED_TCA]);

        $this->buildFromAttributes(ExtendedTcaParentModel::class, self::TABLE_NAME_EXTENDED_TCA, false);

        $actualTca   = $GLOBALS['TCA'][self::TABLE_NAME_EXTENDED_TCA] ?? [];
        $expectedTca = require __DIR__ . '/Fixtures/ExpectedTcaForExtendedTcaParentModel.php';
        self::assertEquals($expectedTca, $actualTca);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws JsonException
     * @throws MisconfiguredTcaException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    #[Test]
    public function buildFromAttributesInOverrideModeAddsOnlyChildProperties(): void
    {
        unset($GLOBALS['TCA'][self::TABLE_NAME_EXTENDED_TCA]);

        $this->buildFromAttributes(ExtendedTcaParentModel::class, self::TABLE_NAME_EXTENDED_TCA, false);
        $this->buildFromAttributes(ExtendedTcaChildModel::class, self::TABLE_NAME_EXTENDED_TCA, true);

        $actualTca   = $GLOBALS['TCA'][self::TABLE_NAME_EXTENDED_TCA] ?? [];
        $expectedTca = require __DIR__ . '/Fixtures/ExpectedTcaForExtendedTcaChildModel.php';
        self::assertEquals($expectedTca, $actualTca);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws JsonException
     * @throws MisconfiguredTcaException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    #[Test]
    public function buildFromAttributesThrowsOnPositionLoop(): void
    {
        unset($GLOBALS['TCA'][self::TABLE_NAME_POSITION_LOOP]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(1646995607);
        $this->expectExceptionMessageMatches('/Position relations create a loop/');

        $this->buildFromAttributes(PositionLoopModel::class, self::TABLE_NAME_POSITION_LOOP, false);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws JsonException
     * @throws MisconfiguredTcaException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    #[Test]
    public function buildFromAttributesThrowsWhenSortByAndDefaultSortByAreBothSet(): void
    {
        unset($GLOBALS['TCA'][self::TABLE_NAME_SORT_CONFLICT]);

        $this->expectException(MisconfiguredTcaException::class);
        $this->expectExceptionCode(1541107594);
        $this->expectExceptionMessageMatches('/You have to decide whether to use sortby or default_sortby/');

        $this->buildFromAttributes(SortConflictModel::class, self::TABLE_NAME_SORT_CONFLICT, false);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws JsonException
     * @throws MisconfiguredTcaException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    #[Test]
    public function buildFromAttributesThrowsWhenSortingOnProtectedColumn(): void
    {
        unset($GLOBALS['TCA'][self::TABLE_NAME_PROTECTED_SORT]);

        $this->expectException(MisconfiguredTcaException::class);
        $this->expectExceptionCode(1541107601);
        $this->expectExceptionMessageMatches('/would overwrite a reserved system column with sorting values/');

        $this->buildFromAttributes(ProtectedSortModel::class, self::TABLE_NAME_PROTECTED_SORT, false);
    }

    protected function tearDown(): void
    {
        unset(
            $GLOBALS['TCA'][self::TABLE_NAME_ALL_ATTRIBUTES],
            $GLOBALS['TCA'][self::TABLE_NAME_CORE_FIELD_POSITION],
            $GLOBALS['TCA'][self::TABLE_NAME_DATA_OBJECT],
            $GLOBALS['TCA'][self::TABLE_NAME_EXTENDED_TCA],
            $GLOBALS['TCA'][self::TABLE_NAME_POSITION_LOOP],
            $GLOBALS['TCA'][self::TABLE_NAME_POSITION_REFERENCE],
            $GLOBALS['TCA'][self::TABLE_NAME_PROTECTED_SORT],
            $GLOBALS['TCA'][self::TABLE_NAME_SORT_CONFLICT]
        );
        parent::tearDown();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws JsonException
     * @throws MisconfiguredTcaException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    private function buildFromAttributes(string $className, string $tableName, bool $overrideMode): void
    {
        $builder = GeneralUtility::makeInstance(Builder::class);
        $builder->buildFromAttributes($className, $tableName, $overrideMode);
    }
}
