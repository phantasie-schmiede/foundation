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
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Class TcaBootTest
 *
 * The TCA of the fixture extension tcafixture is built at boot from the model
 * attributes by Configuration/TCA/Overrides/buildTcaForAllDomainModels.php.
 * This test pins that whole chain: the attribute TCA must be present in
 * $GLOBALS['TCA'] and the table must have been created by the schema
 * migration. It fails if the building file is moved back to a flat
 * Configuration/TCA/<file>.php location, because those files are only
 * honoured by their return value and the side effect would be dropped.
 *
 * @package PSBits\Foundation\Tests\Functional
 */
class TcaBootTest extends FunctionalTestCase
{
    private const string TABLE_NAME = 'tx_tcafixture_domain_model_macguffin';

    protected array $testExtensionsToLoad = [
        'typo3conf/ext/psbits/foundation',
        'typo3conf/ext/psbitstest/tcafixture',
    ];

    #[Test]
    public function tcaOfAttributeModelsIsRegisteredAtBoot(): void
    {
        static::assertArrayHasKey(self::TABLE_NAME, $GLOBALS['TCA']);
        static::assertArrayHasKey(
            'default_sortby',
            $GLOBALS['TCA'][self::TABLE_NAME]['ctrl'],
            'The ctrl configuration of the attribute-built TCA is missing; the full TCA build did not run.'
        );
    }

    #[Test]
    public function tableOfAttributeModelsIsCreatedDuringSchemaMigration(): void
    {
        $tables = $this->getConnectionPool()
            ->getConnectionForTable('pages')
            ->createSchemaManager()
            ->listTableNames();

        static::assertContains(self::TABLE_NAME, $tables);
    }
}
