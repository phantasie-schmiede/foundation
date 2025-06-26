<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Data;

/**
 * Class ModuleConfiguration
 *
 * @package PSBits\Foundation\Data
 * @see     https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/HowTo/BackendModule/ModuleConfiguration.html
 */
class ModuleConfiguration extends MainModuleConfiguration
{
    /**
     * @param string      $key
     * @param string      $access              https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/HowTo/BackendModule/ModuleConfiguration.html#confval-access
     * @param array       $controllers         https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/HowTo/BackendModule/ModuleConfiguration.html#confval-workspaces
     * @param string|null $iconIdentifier      https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/HowTo/BackendModule/ModuleConfiguration.html#confval-iconIdentifier
     * @param string|null $labels              https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/HowTo/BackendModule/ModuleConfiguration.html#confval-labels
     * @param string|null $navigationComponent https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/HowTo/BackendModule/ModuleConfiguration.html#confval-navigationComponent
     * @param string      $parentModule        https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/HowTo/BackendModule/ModuleConfiguration.html#confval-parent
     * @param array|null  $position            https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/HowTo/BackendModule/ModuleConfiguration.html#confval-position
     * @param bool        $renderInModuleMenu  https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/HowTo/BackendModule/ModuleConfiguration.html#confval-appearance.renderInModuleMenu
     * @param string|null $workspaces          https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/HowTo/BackendModule/ModuleConfiguration.html#confval-workspaces
     */
    public function __construct(
        protected string  $key,
        protected string  $access = 'group, user',
        protected array   $controllers = [],
        protected ?string $iconIdentifier = null,
        protected ?string $labels = null,
        protected ?string $navigationComponent = null,
        protected string  $parentModule = 'web',
        protected ?array  $position = null,
        protected bool    $renderInModuleMenu = true,
        protected ?string $workspaces = null,
    ) {
    }

    public function getAccess(): string
    {
        return $this->access;
    }

    public function getControllers(): array
    {
        return $this->controllers;
    }

    public function getParentModule(): string
    {
        return $this->parentModule;
    }
}
