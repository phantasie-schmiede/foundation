<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Data;

use InvalidArgumentException;
use PSBits\Foundation\Controller\Backend\AnalyzeLocalLangController;
use PSBits\Foundation\Controller\Backend\RegisteredIconsController;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ExtensionInformation extends AbstractExtensionInformation
{
    /**
     * @throws InvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct();
        $mainModuleKey = $this->buildModuleKeyPrefix() . 'main';
        $this->addMainModule(
            GeneralUtility::makeInstance(
                MainModuleConfiguration::class,
                key: $mainModuleKey,
                position: [
                    'after'  => 'tools',
                    'before' => 'system',
                ]
            )
        );
        $this->addModule(
            GeneralUtility::makeInstance(
                ModuleConfiguration::class,
                controllers : [AnalyzeLocalLangController::class],
                key         : $this->buildModuleKeyPrefix() . 'analyzelocallang',
                parentModule: $mainModuleKey
            )
        );
        $this->addModule(
            GeneralUtility::makeInstance(
                ModuleConfiguration::class,
                controllers : [RegisteredIconsController::class],
                key         : $this->buildModuleKeyPrefix() . 'registeredicons',
                parentModule: $mainModuleKey
            )
        );
    }
}
