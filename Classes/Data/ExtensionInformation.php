<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Data;

use PSBits\Foundation\Controller\Backend\AnalyzeLocalLangController;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ExtensionInformation
 *
 * @package PSBits\Foundation\Data
 */
class ExtensionInformation extends AbstractExtensionInformation
{
    public function __construct()
    {
        parent::__construct();
        $mainModuleKey = $this->buildModuleKeyPrefix() . 'main';
        $this->addMainModule(
            GeneralUtility::makeInstance(
                MainModuleConfiguration::class, key: $mainModuleKey, position: [
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
    }
}
