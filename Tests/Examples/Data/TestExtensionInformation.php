<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Tests\Examples\Data;

use PSBits\Foundation\Data\AbstractExtensionInformation;
use PSBits\Foundation\Data\MainModuleConfiguration;
use PSBits\Foundation\Data\ModuleConfiguration;
use PSBits\Foundation\Data\PageTypeConfiguration;
use PSBits\Foundation\Data\PluginConfiguration;

/**
 * Class TestExtensionInformation
 *
 * Concrete subclass of AbstractExtensionInformation for unit tests, so the
 * protected builder methods and the constructor wiring can be exercised.
 *
 * @package PSBits\Foundation\Tests\Examples\Data
 */
class TestExtensionInformation extends AbstractExtensionInformation
{
    public function __construct()
    {
        parent::__construct();
        $this->addMainModule(
            new MainModuleConfiguration(
                key: 'testmain',
                position: ['after' => 'tools'],
            )
        );
        $this->addModule(
            new ModuleConfiguration(
                key: 'testmodule',
                controllers: ['TestController'],
            )
        );
        $this->addPageType(
            new PageTypeConfiguration(
                doktype: 100,
                name: 'test_page',
            )
        );
        $this->addPlugin(
            new PluginConfiguration(
                name: 'test_plugin',
            )
        );
    }
}
