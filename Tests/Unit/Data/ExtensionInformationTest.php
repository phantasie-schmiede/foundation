<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Tests\Unit\Data;

use PHPUnit\Framework\Attributes\Test;
use PSBits\Foundation\Data\AbstractExtensionInformation;
use PSBits\Foundation\Data\ExtensionInformation;
use PSBits\Foundation\Data\ExtensionInformationInterface;
use PSBits\Foundation\Data\MainModuleConfiguration;
use PSBits\Foundation\Data\ModuleConfiguration;
use PSBits\Foundation\Data\PageTypeConfiguration;
use PSBits\Foundation\Data\PluginConfiguration;
use PSBits\Foundation\Tests\Examples\Data\TestExtensionInformation;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class ExtensionInformationTest
 *
 * @package PSBits\Foundation\Tests\Unit\Data
 */
class ExtensionInformationTest extends UnitTestCase
{
    #[Test]
    public function abstractInformationExtractsVendorAndExtensionNameFromClassName(): void
    {
        $information = new TestExtensionInformation();

        self::assertSame('PSBits', $information->getVendorName());
        self::assertSame('Foundation', $information->getExtensionName());
        self::assertSame('foundation', $information->getExtensionKey());
    }

    #[Test]
    public function abstractInformationImplementsExtensionInformationInterface(): void
    {
        $information = new TestExtensionInformation();

        self::assertInstanceOf(ExtensionInformationInterface::class, $information);
    }

    #[Test]
    public function builderMethodsCollectConfigurations(): void
    {
        $information = new TestExtensionInformation();

        $mainModules = $information->getMainModules();
        self::assertCount(1, $mainModules);
        self::assertInstanceOf(MainModuleConfiguration::class, $mainModules[0]);
        self::assertSame('testmain', $mainModules[0]->getKey());

        $modules = $information->getModules();
        self::assertCount(1, $modules);
        self::assertInstanceOf(ModuleConfiguration::class, $modules[0]);
        self::assertSame('testmodule', $modules[0]->getKey());

        $pageTypes = $information->getPageTypes();
        self::assertCount(1, $pageTypes);
        self::assertInstanceOf(PageTypeConfiguration::class, $pageTypes[0]);
        self::assertSame('test_page', $pageTypes[0]->getName());

        $plugins = $information->getPlugins();
        self::assertCount(1, $plugins);
        self::assertInstanceOf(PluginConfiguration::class, $plugins[0]);
        self::assertSame('test_plugin', $plugins[0]->getName());
    }

    #[Test]
    public function builderMethodsAreChainable(): void
    {
        $information = new class () extends AbstractExtensionInformation {
            /**
             * @var static[]
             */
            public array $chainedResults = [];

            public function __construct()
            {
                parent::__construct();
                $first                = $this->addModule(new ModuleConfiguration(key: 'a'));
                $second               = $first->addModule(new ModuleConfiguration(key: 'b'));
                $third                = $second->addPlugin(new PluginConfiguration(name: 'p'));
                $this->chainedResults = [$first, $second, $third];
            }
        };

        foreach ($information->chainedResults as $chained) {
            self::assertSame($information, $chained);
        }
        self::assertCount(2, $information->getModules());
        self::assertCount(1, $information->getPlugins());
    }

    #[Test]
    public function buildModuleKeyPrefixStripsUnderscoresAndAppendsTrailingUnderscore(): void
    {
        $information = new TestExtensionInformation();
        $method      = new \ReflectionMethod(AbstractExtensionInformation::class, 'buildModuleKeyPrefix');

        self::assertSame('foundation_', $method->invoke($information));
    }

    #[Test]
    public function concreteExtensionInformationRegistersMainModuleAndChildModules(): void
    {
        $information = new ExtensionInformation();

        $mainModules = $information->getMainModules();
        self::assertCount(1, $mainModules);
        $mainModule = $mainModules[0];
        self::assertSame('foundation_main', $mainModule->getKey());
        self::assertSame(['after' => 'tools', 'before' => 'system'], $mainModule->getPosition());

        $modules = $information->getModules();
        self::assertCount(2, $modules);
        self::assertSame('foundation_analyzelocallang', $modules[0]->getKey());
        self::assertSame('foundation_main', $modules[0]->getParentModule());
        self::assertSame('foundation_registeredicons', $modules[1]->getKey());
        self::assertSame('foundation_main', $modules[1]->getParentModule());
    }
}
