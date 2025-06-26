<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Data;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use function get_class;

/**
 * Class AbstractExtensionInformation
 *
 * You only need to extend this class to be able to provide the extension_key, the ExtensionName and the VendorName.
 * This information is extracted within the constructor using the namespaced class name of your class. If you want to
 * use module, plugin or page type services, make sure to overwrite the constants MODULES and PLUGINS with your own
 * information
 * (see examples below).
 *
 * @package PSBits\Foundation\Data
 */
abstract class AbstractExtensionInformation implements ExtensionInformationInterface
{
    private string $extensionKey;

    private string $extensionName;

    /**
     * @var MainModuleConfiguration[]
     */
    private array $mainModules = [];

    /**
     * @var ModuleConfiguration[]
     */
    private array $modules = [];

    /**
     * @var PageTypeConfiguration[]
     */
    private array $pageTypes = [];

    /**
     * @var PluginConfiguration[]
     */
    private array $plugins = [];

    private string $vendorName;

    public function __construct()
    {
        [
            $this->vendorName,
            $this->extensionName,
        ] = explode('\\', get_class($this));
        $this->extensionKey = GeneralUtility::camelCaseToLowerCaseUnderscored($this->extensionName);
    }

    public function getExtensionKey(): string
    {
        return $this->extensionKey;
    }

    public function getExtensionName(): string
    {
        return $this->extensionName;
    }

    /**
     * @return MainModuleConfiguration[]
     */
    public function getMainModules(): array
    {
        return $this->mainModules;
    }

    /**
     * @return ModuleConfiguration[]
     */
    public function getModules(): array
    {
        return $this->modules;
    }

    /**
     * @return PageTypeConfiguration[]
     */
    public function getPageTypes(): array
    {
        return $this->pageTypes;
    }

    /**
     * @return PluginConfiguration[]
     */
    public function getPlugins(): array
    {
        return $this->plugins;
    }

    public function getVendorName(): string
    {
        return $this->vendorName;
    }

    protected function addMainModule(MainModuleConfiguration $configuration): static
    {
        $this->mainModules[] = $configuration;

        return $this;
    }

    protected function addModule(ModuleConfiguration $configuration): static
    {
        $this->modules[] = $configuration;

        return $this;
    }

    protected function addPageType(PageTypeConfiguration $configuration): static
    {
        $this->pageTypes[] = $configuration;

        return $this;
    }

    protected function addPlugin(PluginConfiguration $configuration): static
    {
        $this->plugins[] = $configuration;

        return $this;
    }

    protected function buildModuleKeyPrefix(): string
    {
        return strtolower(str_replace('_', '', $this->extensionKey)) . '_';
    }
}
