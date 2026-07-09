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
use PSBits\Foundation\Service\Configuration\TcaService;
use PSBits\Foundation\Tests\Examples\Domain\Model\AllTcaAttributesModel;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use ReflectionMethod;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Class TcaServiceTest
 *
 * @package PSBits\Foundation\Tests\Functional
 */
class TcaServiceTest extends FunctionalTestCase
{
    private const string TABLE_NAME = 'tx_foundation_all_tca_attributes';

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
        unset($GLOBALS['TCA'][self::TABLE_NAME]);

        $tcaService = GeneralUtility::makeInstance(TcaService::class);
        $tableName = $tcaService->convertClassNameToTableName(AllTcaAttributesModel::class);
        self::assertSame(self::TABLE_NAME, $tableName);
        $tcaService->setTableName($tableName);

        $buildFromAttributesMethod = new ReflectionMethod(TcaService::class, 'buildFromAttributes');
        $buildFromAttributesMethod->setAccessible(true);
        $buildFromAttributesMethod->invoke($tcaService, AllTcaAttributesModel::class, false);

        $actualTca = $GLOBALS['TCA'][self::TABLE_NAME] ?? [];
        $expectedTca = require __DIR__ . '/Fixtures/ExpectedTcaForAllTcaAttributesModel.php';
        self::assertEquals($expectedTca, $actualTca);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TCA'][self::TABLE_NAME]);
        parent::tearDown();
    }
}
