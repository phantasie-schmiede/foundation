<?php
declare(strict_types=1);

use PSBits\Foundation\Service\Configuration\PluginService;
use PSBits\Foundation\Service\ExtensionInformationService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

defined('TYPO3') or die();

(static function () {
    // register all plugins of those extensions which provide an ExtensionInformation-class
    $extensionInformationService = GeneralUtility::makeInstance(ExtensionInformationService::class);
    $pluginService = GeneralUtility::makeInstance(PluginService::class);
    $allExtensionInformation = $extensionInformationService->getAllExtensionInformation();

    foreach ($allExtensionInformation as $extensionInformation) {
        $pluginService->registerPlugins($extensionInformation);
    }
})();
