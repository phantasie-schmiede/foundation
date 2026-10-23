<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBitsTest\Pluginfixture\Data;

use PSBits\Foundation\Data\AbstractExtensionInformation;
use PSBits\Foundation\Data\PluginConfiguration;
use PSBitsTest\Pluginfixture\Controller\PluginFixtureController;

/**
 * Class ExtensionInformation
 *
 * @package PSBitsTest\Pluginfixture\Data
 */
class ExtensionInformation extends AbstractExtensionInformation
{
    public function __construct()
    {
        parent::__construct();
        $this->addPlugin(
            new PluginConfiguration(
                name      : 'PluginFixture',
                controllers: [
                    PluginFixtureController::class,
                ],
            )
        );
    }
}
