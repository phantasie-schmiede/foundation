<?php
declare(strict_types=1);

use PSBits\Foundation\Service\Configuration\PageTypeService;
use PSBits\Foundation\Service\ExtensionInformationService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

defined('TYPO3') or die();

(static function () {
    $extensionInformationService = GeneralUtility::makeInstance(ExtensionInformationService::class);
    $pageTypeService = GeneralUtility::makeInstance(PageTypeService::class);

    foreach ($extensionInformationService->getAllExtensionInformation() as $extensionInformation) {
        $pageTypeService->addToRegistry($extensionInformation);
    }
})();
