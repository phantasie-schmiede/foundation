<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Tests\Functional;

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use PSBits\Foundation\Utility\Typo3VersionUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Class PluginRegistrationTest
 *
 * The plugin of the fixture extension pluginfixture is registered at boot by the PluginService through
 * Configuration/TCA/Overrides/tt_content.php, while the TCA is still being built. On v14 the core
 * withholds the IconRegistry until the boot is complete, so the registration must not resolve it
 * there: the icon identifier is passed through as is, and the core IconFactory applies the fallback
 * for unregistered identifiers once the icons are rendered.
 *
 * On v13 the plugin is registered as a list_type subtype, which the core deprecates in favour of the
 * CType registration. That deprecation is the expected consequence of the v13 compatibility path, so
 * it is ignored for this test.
 *
 * @package PSBits\Foundation\Tests\Functional
 */
#[IgnoreDeprecations]
class PluginRegistrationTest extends FunctionalTestCase
{
    private const string PLUGIN_SIGNATURE = 'pluginfixture_pluginfixture';

    protected array $testExtensionsToLoad = [
        'typo3conf/ext/psbits/foundation',
        'typo3conf/ext/psbitstest/pluginfixture',
    ];

    #[Test]
    public function pluginIsRegisteredInTheTtContentTcaAtBoot(): void
    {
        if (Typo3VersionUtility::isAtLeast('14.0')) {
            $column       = 'CType';
            $expectedIcon = 'pluginfixture-plugin-fixture';
        } else {
            $column       = 'list_type';
            $expectedIcon = 'content-plugin';
        }

        $items      = $GLOBALS['TCA']['tt_content']['columns'][$column]['config']['items'] ?? [];
        $pluginItem = null;

        foreach ($items as $item) {
            if (($item['value'] ?? null) === self::PLUGIN_SIGNATURE) {
                $pluginItem = $item;

                break;
            }
        }

        static::assertNotNull($pluginItem, sprintf('The plugin is missing from the tt_content %s items.', $column));
        static::assertSame(
            $expectedIcon,
            $pluginItem['icon'] ?? null,
            'The plugin icon identifier is not what the PluginService is expected to register.'
        );
    }

    #[Test]
    public function pluginCTypeHasATypeIconOnV14(): void
    {
        if (!Typo3VersionUtility::isAtLeast('14.0')) {
            static::markTestSkipped('Plugins are registered as their own content type on v14 only.');
        }

        static::assertArrayHasKey(
            self::PLUGIN_SIGNATURE,
            $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'] ?? [],
            'The plugin type icon is missing from the tt_content ctrl configuration.'
        );
    }
}
