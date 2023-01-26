<?php
declare(strict_types=1);

use PSB\PsbFoundation\Service\Configuration\RegistrationService;
use PSB\PsbFoundation\Service\ExtensionInformationService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

defined('TYPO3') or die();

(static function () {
    // register all plugins of those extensions which provide an ExtensionInformation-class
    $extensionInformationService = GeneralUtility::makeInstance(ExtensionInformationService::class);
    $registrationService = GeneralUtility::makeInstance(RegistrationService::class);
    $allExtensionInformation = $extensionInformationService->getExtensionInformation();

    foreach ($allExtensionInformation as $extensionInformation) {
        $registrationService->registerPlugins($extensionInformation);
    }
})();
