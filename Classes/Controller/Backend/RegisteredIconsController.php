<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Controller\Backend;

use PSBits\Foundation\Attribute\ModuleAction;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\PathUtility;

use function count;

/**
 * Class RegisteredIconsController
 *
 * @package PSBits\Foundation\Controller\Backend
 */
#[AsController]
class RegisteredIconsController extends AbstractModuleController
{
    public function __construct(
        protected readonly IconRegistry $iconRegistry,
        ModuleTemplateFactory           $moduleTemplateFactory,
    ) {
        parent::__construct($moduleTemplateFactory);
    }

    /**
     * @throws Exception
     */
    #[ModuleAction(default: true)]
    public function overviewAction(): ResponseInterface
    {
        $iconIdentifiers = $this->iconRegistry->getAllRegisteredIconIdentifiers();
        $registeredIcons = [];

        foreach ($iconIdentifiers as $iconIdentifier) {
            $iconPath                         = $this->iconRegistry->getIconConfigurationByIdentifier($iconIdentifier)['options']['source'];
            $registeredIcons[$iconIdentifier] = [
                'publicWebPath' => PathUtility::getPublicResourceWebPath($iconPath),
                'sourcePath'    => $iconPath,
            ];
        }

        ksort($registeredIcons);

        $this->moduleTemplate->assignMultiple([
            'registeredIcons' => $registeredIcons,
            'totalCount'      => count($registeredIcons),
        ]);

        return $this->htmlResponse();
    }
}
