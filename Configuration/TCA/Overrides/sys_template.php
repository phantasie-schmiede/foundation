<?php
declare(strict_types=1);

use PSB\PsbFoundation\Service\ExtensionInformationService;
use PSB\PsbFoundation\Service\LocalizationService;
use PSB\PsbFoundation\Utility\TypoScript\TypoScriptUtility;
use Symfony\Component\Finder\Finder;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

defined('TYPO3') or die();

(static function () {
    // register TypoScript of those extensions which provide an ExtensionInformation-class
    $extensionInformationService = GeneralUtility::makeInstance(ExtensionInformationService::class);
    $allExtensionInformation = $extensionInformationService->getAllExtensionInformation();
    $localizationService = GeneralUtility::makeInstance(LocalizationService::class);

    foreach ($allExtensionInformation as $extensionInformation) {
        $pathStub = 'Configuration/TypoScript';
        $realPath = ExtensionManagementUtility::extPath($extensionInformation->getExtensionKey()) . $pathStub;

        if (!is_dir($realPath)) {
            continue;
        }

        $finder = Finder::create()->files()->in($realPath)->name('*.typoscript');

        if (true === $finder->hasResults()) {
            $title = 'LLL:EXT:' . $extensionInformation->getExtensionKey() . '/Resources/Private/Language/Backend/Configuration/TCA/Overrides/sys_template.xlf:template.title';

            if (false === $localizationService->translationExists($title, false)) {
                $title = 'Main configuration';
            }

            TypoScriptUtility::registerTypoScript($extensionInformation, $pathStub, $title);
        }
    }
})();
