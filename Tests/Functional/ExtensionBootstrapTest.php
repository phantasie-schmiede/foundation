<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with the source code.
 */

namespace PSBits\Foundation\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use PSBits\Foundation\Utility\Localization\LoggingUtility;
use PSBits\Foundation\Utility\Typo3VersionUtility;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Class ExtensionBootstrapTest
 *
 * Guards the seams that differ between the supported TYPO3 majors. It is cheap on
 * purpose: as soon as core drops one of these files, this is the test that tells
 * us which version conditional we need.
 *
 * @package PSBits\Foundation\Tests\Functional
 */
class ExtensionBootstrapTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/psbits/foundation',
    ];

    #[Test]
    public function extensionIsLoadedAsPackage(): void
    {
        $packageManager = GeneralUtility::makeInstance(PackageManager::class);
        $package        = $packageManager->getPackage('psbits/foundation');

        self::assertNotNull($package, 'The extension was not resolved as a composer package.');
        self::assertSame(
            realpath($package->getPackagePath()),
            realpath(\dirname(__DIR__, 2))
        );
    }

    /**
     * Configuration/Icons.php reads the extension's public icons straight from the package
     * path, so the symlink below public/typo3conf/ext is never needed for them to be found.
     * What has to hold is that the icons are picked up and registered from the package, which
     * is what Configuration/Icons.php is responsible for. The identifier is derived from the
     * extension key plus the file name, see IconUtility::getDefaultIdentifier().
     */
    #[Test]
    public function publicIconsAreRegisteredFromThePackage(): void
    {
        $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);

        // Resources/Public/Icons/Extension.svg -> foundation-extension, see
        // IconUtility::getDefaultIdentifier(), which combines extension key and file name.
        self::assertTrue(
            $iconRegistry->isRegistered('foundation-extension'),
            'The icons from Resources/Public/Icons were not registered from the package.'
        );
    }

    /**
     * ext_tables.php stopped being loaded in v13, so the page type registration
     * has to live in ext_localconf.php - which is loaded in every supported
     * version. The file must not come back.
     */
    #[Test]
    public function legacyExtTablesPhpIsGone(): void
    {
        self::assertFileDoesNotExist(
            \dirname(__DIR__, 2) . '/ext_tables.php',
            'ext_tables.php is not loaded since v13. Register page types in ext_localconf.php instead.'
        );
    }

    /**
     * ext_tables.sql is a different story: core still reads it through SqlReader
     * in PackageSetup::updateDatabaseSchemaForAllPackages(), which runs on
     * extension setup and installation. So it is the canonical place for the two
     * logging tables and must stay.
     */
    #[Test]
    public function extTablesSqlIsStillSupportedByCore(): void
    {
        self::assertTrue(
            Typo3VersionUtility::isAtLeast('12.0'),
            'Unexpected core version, this test only knows about the v12+ support window.'
        );
        self::assertFileExists(
            \dirname(__DIR__, 2) . '/ext_tables.sql',
            'ext_tables.sql is still read via SqlReader/PackageSetup; dropping it loses both logging tables.'
        );
    }

    /**
     * The two tables the extension writes to must actually exist after the test
     * instance was set up from ext_tables.sql.
     */
    #[Test]
    public function loggingTablesExist(): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(LoggingUtility::LOG_TABLES['ACCESS']);
        $tableNames = $connection->createSchemaManager()
            ->listTableNames();

        self::assertContains(LoggingUtility::LOG_TABLES['ACCESS'], $tableNames);
        self::assertContains(LoggingUtility::LOG_TABLES['MISSING'], $tableNames);
    }

    #[Test]
    public function classesAutoloadThroughComposer(): void
    {
        self::assertTrue(
            class_exists(\PSBits\Foundation\Service\ExtensionInformationService::class),
            'The PSBits\\Foundation namespace is not autoloadable.'
        );
    }

    /**
     * The v13+ consolidated entry point is the class the testing framework
     * bootstraps through. Fail early and explicitly if it is unavailable, so the
     * functional suite does not die with an uninformative error further down.
     */
    #[Test]
    public function systemEnvironmentBuilderIsAvailable(): void
    {
        self::assertTrue(
            class_exists(SystemEnvironmentBuilder::class),
            'TYPO3\\CMS\\Core\\Core\\SystemEnvironmentBuilder is missing; '
            . 'the testing framework bootstrap cannot run against this core version.'
        );
    }
}
