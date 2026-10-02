<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use PSBits\Foundation\Exceptions\MisconfiguredTcaException;
use PSBits\Foundation\Service\Configuration\TcaService;
use PSBits\Foundation\Tests\Examples\Domain\Model\AllTcaAttributesModel;
use PSBits\Foundation\Tests\Examples\Domain\Model\ExtendedTcaChildModel;
use PSBits\Foundation\Tests\Examples\Domain\Model\ExtendedTcaParentModel;
use PSBits\Foundation\Tests\Examples\Domain\Model\PositionLoopModel;
use PSBits\Foundation\Tests\Examples\Domain\Model\ProtectedSortModel;
use PSBits\Foundation\Tests\Examples\Domain\Model\SortConflictModel;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use ReflectionMethod;
use RuntimeException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Class TcaServiceTest
 *
 * @package PSBits\Foundation\Tests\Functional
 */
class TcaServiceTest extends FunctionalTestCase
{
    private const string TABLE_NAME_ALL_ATTRIBUTES = 'tx_foundation_all_tca_attributes';
    private const string TABLE_NAME_EXTENDED_TCA   = 'tx_foundation_extended_tca';
    private const string TABLE_NAME_POSITION_LOOP  = 'tx_foundation_position_loop';
    private const string TABLE_NAME_SORT_CONFLICT  = 'tx_foundation_sort_conflict';
    private const string TABLE_NAME_PROTECTED_SORT = 'tx_foundation_protected_sort';

    protected array $testExtensionsToLoad = [
        'typo3conf/ext/psbits/foundation',
    ];

    /**
     * @throws ContainerExceptionInterface
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
        $tcaService->setTableName($tableName);

        $buildFromAttributesMethod = new ReflectionMethod(TcaService::class, 'buildFromAttributes');
        $buildFromAttributesMethod->invoke($tcaService, AllTcaAttributesModel::class, false);

        $actualTca   = $GLOBALS['TCA'][self::TABLE_NAME_ALL_ATTRIBUTES] ?? [];
        $expectedTca = require __DIR__ . '/Fixtures/ExpectedTcaForAllTcaAttributesModel.php';
        self::assertEquals($expectedTca, $actualTca);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    #[Test]
    public function buildFromAttributesCreatesExpectedTcaForExtendedTcaParentModel(): void
    {
        unset($GLOBALS['TCA'][self::TABLE_NAME_EXTENDED_TCA]);

        $tcaService = GeneralUtility::makeInstance(TcaService::class);
        $tcaService->setTableName(self::TABLE_NAME_EXTENDED_TCA);

        $buildFromAttributesMethod = new ReflectionMethod(TcaService::class, 'buildFromAttributes');
        $buildFromAttributesMethod->invoke($tcaService, ExtendedTcaParentModel::class, false);

        $actualTca   = $GLOBALS['TCA'][self::TABLE_NAME_EXTENDED_TCA] ?? [];
        $expectedTca = require __DIR__ . '/Fixtures/ExpectedTcaForExtendedTcaParentModel.php';
        self::assertEquals($expectedTca, $actualTca);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    #[Test]
    public function buildFromAttributesInOverrideModeAddsOnlyChildProperties(): void
    {
        unset($GLOBALS['TCA'][self::TABLE_NAME_EXTENDED_TCA]);

        $tcaService = GeneralUtility::makeInstance(TcaService::class);
        $tcaService->setTableName(self::TABLE_NAME_EXTENDED_TCA);

        $buildFromAttributesMethod = new ReflectionMethod(TcaService::class, 'buildFromAttributes');
        $buildFromAttributesMethod->invoke($tcaService, ExtendedTcaParentModel::class, false);
        $buildFromAttributesMethod->invoke($tcaService, ExtendedTcaChildModel::class, true);

        $actualTca   = $GLOBALS['TCA'][self::TABLE_NAME_EXTENDED_TCA] ?? [];
        $expectedTca = require __DIR__ . '/Fixtures/ExpectedTcaForExtendedTcaChildModel.php';
        self::assertEquals($expectedTca, $actualTca);
    }

    /**
     * @throws ContainerExceptionInterface
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

        $tcaService = GeneralUtility::makeInstance(TcaService::class);
        $tcaService->setTableName(self::TABLE_NAME_POSITION_LOOP);

        $buildFromAttributesMethod = new ReflectionMethod(TcaService::class, 'buildFromAttributes');
        $buildFromAttributesMethod->invoke($tcaService, PositionLoopModel::class, false);
    }

    /**
     * @throws ContainerExceptionInterface
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

        $tcaService = GeneralUtility::makeInstance(TcaService::class);
        $tcaService->setTableName(self::TABLE_NAME_SORT_CONFLICT);

        $buildFromAttributesMethod = new ReflectionMethod(TcaService::class, 'buildFromAttributes');
        $buildFromAttributesMethod->invoke($tcaService, SortConflictModel::class, false);
    }

    /**
     * @throws ContainerExceptionInterface
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

        $tcaService = GeneralUtility::makeInstance(TcaService::class);
        $tcaService->setTableName(self::TABLE_NAME_PROTECTED_SORT);

        $buildFromAttributesMethod = new ReflectionMethod(TcaService::class, 'buildFromAttributes');
        $buildFromAttributesMethod->invoke($tcaService, ProtectedSortModel::class, false);
    }

    protected function tearDown(): void
    {
        unset(
            $GLOBALS['TCA'][self::TABLE_NAME_ALL_ATTRIBUTES],
            $GLOBALS['TCA'][self::TABLE_NAME_EXTENDED_TCA],
            $GLOBALS['TCA'][self::TABLE_NAME_POSITION_LOOP],
            $GLOBALS['TCA'][self::TABLE_NAME_SORT_CONFLICT],
            $GLOBALS['TCA'][self::TABLE_NAME_PROTECTED_SORT]
        );
        parent::tearDown();
    }
}
