<?php
declare(strict_types=1);

use PSBits\Foundation\Service\ExtensionInformationService;
use PSBits\Foundation\Utility\Configuration\FilePathUtility;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

return call_user_func(
    static function() {
        $icons = [];
        $extensionInformationService = GeneralUtility::makeInstance(ExtensionInformationService::class);
        $allExtensionInformation = $extensionInformationService->getAllExtensionInformation();
        $packageManager = GeneralUtility::makeInstance(PackageManager::class);
        $pathMapping = [];

        foreach ($packageManager->getAvailablePackages() as $package) {
            $pathMapping[$package->getPackagePath(
            )] = FilePathUtility::EXTENSION_DIRECTORY_PREFIX . $package->getPackageKey() . '/';
        }

        foreach ($allExtensionInformation as $extensionInformation) {
            $path = GeneralUtility::getFileAbsFileName(
                FilePathUtility::EXTENSION_DIRECTORY_PREFIX . $extensionInformation->getExtensionKey(
                ) . '/Resources/Public/Icons'
            );

            if (!is_dir($path)) {
                continue;
            }

            $finder = Finder::create()
                ->files()
                ->in($path)
                ->name(
                    [
                        '*.png',
                        '*.svg',
                    ]
                );

            /** @var SplFileInfo $fileInfo */
            foreach ($finder as $fileInfo) {
                $iconIdentifier = str_replace(
                        '_',
                        '-',
                        $extensionInformation->getExtensionKey()
                    ) . '-' . str_replace(
                        '_',
                        '-',
                        GeneralUtility::camelCaseToLowerCaseUnderscored($fileInfo->getFilenameWithoutExtension())
                    );

                // Absolute icon paths do not work in every context inside TYPO3. Therefore we need to use EXT: prefix.
                $icons[$iconIdentifier] = [
                    'provider' => ('svg' === strtolower(
                            $fileInfo->getExtension()
                        )) ? SvgIconProvider::class : BitmapIconProvider::class,
                    'source'   => str_replace(
                        array_keys($pathMapping),
                        array_values($pathMapping),
                        $fileInfo->getPathname()
                    ),
                ];
            }
        }

        return $icons;
    }
);
