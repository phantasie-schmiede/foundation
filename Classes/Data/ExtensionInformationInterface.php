<?php
declare(strict_types=1);

/*
 * This file is part of PSB Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbFoundation\Data;

/**
 * Interface ExtensionInformationInterface
 *
 * @package PSB\PsbFoundation\Data
 */
interface ExtensionInformationInterface
{
    public function getExtensionKey(): string;

    public function getExtensionName(): string;

    /**
     * @return MainModuleConfiguration[]
     */
    public function getMainModules(): array;

    /**
     * @return ModuleConfiguration[]
     */
    public function getModules(): array;

    /**
     * @return PageTypeConfiguration[]
     */
    public function getPageTypes(): array;

    /**
     * @return PluginConfiguration[]
     */
    public function getPlugins(): array;

    public function getVendorName(): string;
}
