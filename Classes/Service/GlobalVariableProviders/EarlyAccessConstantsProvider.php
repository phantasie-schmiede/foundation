<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Service\GlobalVariableProviders;

use PSBits\Foundation\Exceptions\ImplementationException;
use PSBits\Foundation\Service\ExtensionInformationService;
use PSBits\Foundation\Utility\Configuration\FilePathUtility;
use PSBits\Foundation\Utility\TypoScript\TypoScriptUtility;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function count;

/**
 * Class EarlyAccessConstantsProvider
 *
 * Extensions may provide a YAML-file with constants that can be used in ext_localconf.php-files (before TypoScript is
 * available). Those constants can be accessed via the GlobalVariableService and are registered as
 * TypoScript-constants, too.
 *
 * If your constants are neither needed that early during TYPO3's bootstrap process nor are they context-specific, you
 * may consider placing them in the config.yaml of your SiteConfiguration:
 * https://docs.typo3.org/c/typo3/cms-core/master/en-us/Changelog/10.4/Feature-91080-SiteSettingsAsTsConstantsAndInTsConfig.html
 *
 * To provide a simple configuration that is valid for all stages, just create the file
 * /Configuration/EarlyAccessConstants/constants.yaml inside your extension directory.
 * It is possible to provide context-specific files that enable you to manage the requirements of different stages. The
 * context is added to the directory structure whereas the last part serves as filename and is converted to lowercase.
 *
 * Examples:
 * /Configuration/EarlyAccessConstants/development.yaml
 * /Configuration/EarlyAccessConstants/production.yaml
 * /Configuration/EarlyAccessConstants/Production/staging.yaml
 *
 * @package PSBits\Foundation\Service\GlobalVariableProviders
 */
class EarlyAccessConstantsProvider extends AbstractProvider
{
    public const DIRECTORY = '/Configuration/EarlyAccessConstants/';

    /**
     * @throws ImplementationException
     */
    public function getGlobalVariables(): array
    {
        $mergedConstants = [];
        $extensionInformationService = GeneralUtility::makeInstance(ExtensionInformationService::class);
        $allExtensionInformation = $extensionInformationService->getAllExtensionInformation();

        // This builds the path for a context-specific file with a lowercase filename.
        /** @var array $contextParts */
        $contextParts = explode(
            '/',
            Environment::getContext()
                ->__toString()
        );
        $lastIndex = count($contextParts) - 1;
        $contextParts[$lastIndex] = lcfirst($contextParts[$lastIndex]);
        $contextSpecificFilePath = self::DIRECTORY . implode('/', $contextParts) . '.yaml';

        foreach ($allExtensionInformation as $extensionInformation) {
            $basePath = FilePathUtility::EXTENSION_DIRECTORY_PREFIX . $extensionInformation->getExtensionKey();
            $yamlFiles = [
                GeneralUtility::getFileAbsFileName($basePath . self::DIRECTORY . 'constants.yaml'),
                GeneralUtility::getFileAbsFileName($basePath . $contextSpecificFilePath),
            ];

            foreach ($yamlFiles as $yamlFile) {
                if (file_exists($yamlFile)) {
                    $constants = Yaml::parseFile($yamlFile) ?? [];
                    ArrayUtility::mergeRecursiveWithOverrule($mergedConstants, $constants);
                }
            }
        }

        ExtensionManagementUtility::addTypoScriptConstants(
            TypoScriptUtility::convertArrayToTypoScript($mergedConstants)
        );

        return $mergedConstants;
    }
}
