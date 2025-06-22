<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Service\Configuration;

use JsonException;
use PSBits\Foundation\Attribute\PluginAction;
use PSBits\Foundation\Data\ExtensionInformationInterface;
use PSBits\Foundation\Data\PluginConfiguration;
use PSBits\Foundation\Utility\Configuration\FilePathUtility;
use PSBits\Foundation\Utility\LocalizationUtility;
use PSBits\Foundation\Utility\ReflectionUtility;
use PSBits\Foundation\Utility\TypoScript\PageObjectConfiguration;
use PSBits\Foundation\Utility\TypoScript\TypoScriptUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use function in_array;
use function is_int;

/**
 * Class PluginService
 *
 * @package PSBits\Foundation\Service\Configuration
 */
class PluginService
{
    /**
     * This variable is used to keep track of already registered wizard groups and is pre-filled with TYPO3's default
     * groups as defined in typo3\cms-backend\Configuration\page.tsconfig
     *
     * @var string[]
     */
    private array $contentElementWizardGroups = [
        'common',
        'forms',
        'menu',
        'plugins',
        'special',
    ];

    public function __construct(
        protected FlexFormService     $flexFormService,
        protected IconRegistry        $iconRegistry,
        protected PageDoktypeRegistry $pageDoktypeRegistry,
    ) {
    }

    /**
     * For use in ext_localconf.php files
     *
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function addPluginToElementWizard(
        ExtensionInformationInterface $extensionInformation,
        PluginConfiguration           $pluginConfiguration,
    ): void {
        $group = $pluginConfiguration->getGroup() ?: mb_strtolower($extensionInformation->getVendorName());
        $iconIdentifier = $pluginConfiguration->getIconIdentifier() ?: $extensionInformation->getExtensionKey(
            ) . '-' . str_replace(
                '_',
                '-',
                GeneralUtility::camelCaseToLowerCaseUnderscored($pluginConfiguration->getName())
            );
        $ll = 'LLL:EXT:' . $extensionInformation->getExtensionKey(
            ) . '/Resources/Private/Language/Backend/Configuration/TsConfig/Page/Mod/Wizards/newContentElement.xlf:' . $group . '.elements.' . lcfirst(
                $pluginConfiguration->getName()
            );
        $description = $ll . '.description';
        $title = $ll . '.title';
        $listType = str_replace('_', '', $extensionInformation->getExtensionKey()) . '_' . mb_strtolower(
                $pluginConfiguration->getName()
            );

        if (false === LocalizationUtility::translationExists($description)) {
            $description = '';
        }

        if (false === LocalizationUtility::translationExists($title, false)) {
            $title = $this->getDefaultLabelPathForPlugin($extensionInformation, $pluginConfiguration->getName());

            if (false === LocalizationUtility::translationExists($title)) {
                $title = $pluginConfiguration->getName();
            }
        }

        $configuration = [
            'description'          => $description,
            'iconIdentifier'       => $this->iconRegistry->isRegistered(
                $iconIdentifier
            ) ? $iconIdentifier : 'content-plugin',
            'title'                => $title,
            'tt_content_defValues' => [
                'CType'     => 'list',
                'list_type' => $listType,
            ],
        ];

        $this->addElementWizardItem($configuration, $extensionInformation->getExtensionKey(), $group, $listType);
    }

    /**
     * For use in ext_localconf.php files
     *
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function configurePlugins(ExtensionInformationInterface $extensionInformation): void
    {
        foreach ($extensionInformation->getPlugins() as $configuration) {
            [
                $controllersAndCachedActions,
                $controllersAndUncachedActions,
            ] = $this->collectActionsAndConfiguration($configuration);

            ExtensionUtility::configurePlugin(
                $extensionInformation->getExtensionName(),
                $configuration->getName(),
                $controllersAndCachedActions,
                $controllersAndUncachedActions
            );

            if (0 < $configuration->getTypeNum()) {
                $this->registerPageTypeForPlugin($configuration, $extensionInformation);
            }

            if ($configuration->isAddToElementWizard()) {
                $this->addPluginToElementWizard($extensionInformation, $configuration);
            }
        }
    }

    /**
     * For use in Configuration/TCA/Overrides/tt_content.php files
     *
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function registerPlugins(ExtensionInformationInterface $extensionInformation): void
    {
        foreach ($extensionInformation->getPlugins() as $configuration) {
            $title = $configuration->getTitle();

            if (empty($title)) {
                $title = $this->getDefaultLabelPathForPlugin($extensionInformation, $configuration->getName());

                if (false === LocalizationUtility::translationExists($title)) {
                    $title = $configuration->getName();
                }
            }

            $iconIdentifier = $extensionInformation->getExtensionKey() . '-' . str_replace(
                    '_',
                    '-',
                    GeneralUtility::camelCaseToLowerCaseUnderscored($configuration->getName())
                );

            $pluginSignature = ExtensionUtility::registerPlugin(
                $extensionInformation->getExtensionName(),
                $configuration->getName(),
                $title,
                $this->iconRegistry->isRegistered($iconIdentifier) ? $iconIdentifier : 'content-plugin'
            );
            $this->registerFlexFormForPlugin($extensionInformation, $configuration, $pluginSignature);
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    private function addElementWizardGroup(string $extensionKey, string $key): void
    {
        $header = 'LLL:EXT:' . $extensionKey . '/Resources/Private/Language/Backend/Configuration/TsConfig/Page/Mod/Wizards/newContentElement.xlf:' . $key . '.header';
        LocalizationUtility::translationExists($header);
        $pageTS['mod']['wizards']['newContentElement']['wizardItems'][$key] = [
            'header' => $header,
            'show'   => '*',
        ];

        ExtensionManagementUtility::addPageTSConfig(TypoScriptUtility::convertArrayToTypoScript($pageTS));
        $this->contentElementWizardGroups[] = $key;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    private function addElementWizardItem(
        array  $configuration,
        string $extensionKey,
        string $group,
        string $key,
    ): void {
        if (!in_array($group, $this->contentElementWizardGroups, true)) {
            $this->addElementWizardGroup($extensionKey, $group);
        }

        $newPageTS['mod']['wizards']['newContentElement']['wizardItems'][$group]['elements'][$key] = $configuration;
        ExtensionManagementUtility::addPageTSConfig(TypoScriptUtility::convertArrayToTypoScript($newPageTS));
    }

    /**
     * @param PluginConfiguration $configuration
     *
     * @return array[]
     * @throws ReflectionException
     */
    private function collectActionsAndConfiguration(
        PluginConfiguration $configuration,
    ): array {
        $controllersAndCachedActions = [];
        $controllersAndUncachedActions = [];

        foreach ($configuration->getControllers() as $key => $value) {
            if (is_int($key)) {
                $controllerClassName = $value;
            } else {
                $controllerClassName = $key;
                $specifiedActions = $value;
            }

            $controller = new ReflectionClass($controllerClassName);
            $controllersAndCachedActions[$controllerClassName] = [];
            $controllersAndUncachedActions[$controllerClassName] = [];
            $methods = $controller->getMethods();

            foreach ($methods as $method) {
                $pluginAction = ReflectionUtility::getAttributeInstance(PluginAction::class, $method);

                if (!$pluginAction instanceof PluginAction) {
                    continue;
                }

                $methodName = $method->getName();
                $actionName = mb_substr($methodName, 0, -6);

                if (isset($specifiedActions) && !in_array($actionName, $specifiedActions, true)) {
                    continue;
                }

                if (true === $pluginAction->isDefault()) {
                    array_unshift($controllersAndCachedActions[$controllerClassName], $actionName);
                } else {
                    $controllersAndCachedActions[$controllerClassName][] = $actionName;
                }

                if (true === $pluginAction->isUncached()) {
                    if (true === $pluginAction->isDefault()) {
                        array_unshift($controllersAndUncachedActions[$controllerClassName], $actionName);
                    } else {
                        $controllersAndUncachedActions[$controllerClassName][] = $actionName;
                    }
                }
            }

            unset($specifiedActions);
        }

        array_walk($controllersAndCachedActions, static function(&$value) {
            $value = implode(', ', $value);
        });

        array_walk($controllersAndUncachedActions, static function(&$value) {
            $value = implode(', ', $value);
        });

        return [
            $controllersAndCachedActions,
            $controllersAndUncachedActions,
        ];
    }

    private function getDefaultLabelPathForPlugin(
        ExtensionInformationInterface $extensionInformation,
        string                        $pluginName,
    ): string {
        return 'LLL:EXT:' . $extensionInformation->getExtensionKey(
            ) . '/Resources/Private/Language/Backend/Configuration/TCA/Overrides/tt_content.xlf:plugin.' . lcfirst(
                $pluginName
            ) . '.title';
    }

    private function registerFlexFormForPlugin(
        ExtensionInformationInterface $extensionInformation,
        PluginConfiguration           $configuration,
        string                        $pluginSignature,
    ): void {
        if (!empty($configuration->getFlexForm())) {
            if (!str_contains($configuration->getFlexForm(), '/')) {
                $fileName = $configuration->getFlexForm();
            }
        } else {
            $fileName = $configuration->getName();
        }

        if (isset($fileName)) {
            $flexFormFilePath = FilePathUtility::EXTENSION_DIRECTORY_PREFIX . $extensionInformation->getExtensionKey(
                ) . '/Configuration/FlexForms/' . $fileName . '.xml';
        } else {
            $flexFormFilePath = $configuration->getFlexForm();
        }

        $flexFormFilePath = GeneralUtility::getFileAbsFileName($flexFormFilePath);

        if (file_exists($flexFormFilePath)) {
            $this->flexFormService->register(file_get_contents($flexFormFilePath), $pluginSignature);
        }
    }

    private function registerPageTypeForPlugin(
        PluginConfiguration           $pluginConfiguration,
        ExtensionInformationInterface $extensionInformation,
    ): void {
        $pageObjectConfiguration = GeneralUtility::makeInstance(PageObjectConfiguration::class);
        $pageObjectConfiguration->setCacheable($pluginConfiguration->isTypeNumCacheable());
        $pageObjectConfiguration->setContentType($pluginConfiguration->getTypeNumContentType());
        $pageObjectConfiguration->setDisableAllHeaderCode($pluginConfiguration->isTypeNumDisableAllHeaderCode());
        $pageObjectConfiguration->setExtensionName($extensionInformation->getExtensionName());
        $pageObjectConfiguration->setPluginName($pluginConfiguration->getName());
        $pageObjectConfiguration->setTypeNum($pluginConfiguration->getTypeNum());
        $typoScriptObjectName = strtolower(
            implode('_', [
                $extensionInformation->getVendorName(),
                $extensionInformation->getExtensionName(),
                $pluginConfiguration->getName(),
            ])
        );
        $pageObjectConfiguration->setTypoScriptObjectName($typoScriptObjectName);
        $pageObjectConfiguration->setVendorName($extensionInformation->getVendorName());
        TypoScriptUtility::registerPageType($pageObjectConfiguration);
    }
}
