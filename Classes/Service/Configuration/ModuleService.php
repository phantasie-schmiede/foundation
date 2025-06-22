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
use PSBits\Foundation\Attribute\ModuleAction;
use PSBits\Foundation\Data\ExtensionInformationInterface;
use PSBits\Foundation\Data\MainModuleConfiguration;
use PSBits\Foundation\Data\ModuleConfiguration;
use PSBits\Foundation\Utility\LocalizationUtility;
use PSBits\Foundation\Utility\ReflectionUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function in_array;
use function is_int;

/**
 * Class ModuleService
 *
 * @package PSBits\Foundation\Service\Configuration
 */
class ModuleService
{
    public const  ICON_SUFFIXES = [
        'CONTENT_FROM_PID' => '-contentFromPid',
        'ROOT'             => '-root',
        'HIDE_IN_MENU'     => '-hideinmenu',
    ];

    public function __construct(
        protected IconRegistry $iconRegistry,
    ) {
    }

    /**
     * For use in ext_tables.php files
     *
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function buildModuleConfiguration(ExtensionInformationInterface $extensionInformation): array
    {
        $modules = [];

        foreach ($extensionInformation->getMainModules() as $configuration) {
            $modules[$configuration->getKey()] = $this->buildBasicConfiguration($configuration, $extensionInformation);
        }

        foreach ($extensionInformation->getModules() as $configuration) {
            $moduleConfiguration = $this->buildBasicConfiguration($configuration, $extensionInformation);
            $moduleConfiguration['access'] = $configuration->getAccess();
            $moduleConfiguration['extensionName'] = $extensionInformation->getExtensionName();
            $moduleConfiguration['parent'] = $configuration->getParentModule();

            if (!empty($configuration->getControllers())) {
                $moduleConfiguration['controllerActions'] = $this->collectActions($configuration->getControllers());
            }

            $modules[$configuration->getKey()] = $moduleConfiguration;
        }

        return $modules;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    private function buildBasicConfiguration(
        MainModuleConfiguration       $configuration,
        ExtensionInformationInterface $extensionInformation,
    ): array {
        $moduleKey = $configuration->getKey();
        $moduleConfiguration = [
            'appearance'     => [
                'renderInModuleMenu' => $configuration->getRenderInModuleMenu(),
            ],
            'iconIdentifier' => $this->determineIconIdentifier($configuration, $extensionInformation),
            'labels'         => $configuration->getLabels() ?? $this->getDefaultLabelPath(
                    $extensionInformation,
                    $moduleKey
                ),
        ];

        if (!empty($configuration->getNavigationComponent())) {
            $moduleConfiguration['navigationComponent'] = $configuration->getNavigationComponent();
        }

        if (!empty($configuration->getPosition())) {
            $moduleConfiguration['position'] = $configuration->getPosition();
        }

        if (!empty($configuration->getWorkspaces())) {
            $moduleConfiguration['workspaces'] = $configuration->getWorkspaces();
        }

        $this->checkLanguageLabels($moduleConfiguration['labels']);

        return $moduleConfiguration;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    private function checkLanguageLabels(string $filePath): void
    {
        foreach ([
                     'mlang_labels_tabdescr',
                     'mlang_labels_tablabel',
                     'mlang_tabs_tab',
                 ] as $label) {
            LocalizationUtility::translationExists($filePath . ':' . $label);
        }
    }

    /**
     * @throws ReflectionException
     */
    private function collectActions(
        array $controllersCollection,
    ): array {
        $controllersAndActions = [];

        foreach ($controllersCollection as $key => $value) {
            if (is_int($key)) {
                $controllerClassName = $value;
            } else {
                $controllerClassName = $key;
                $specifiedActions = $value;
            }

            $controller = new ReflectionClass($controllerClassName);
            $controllersAndActions[$controllerClassName] = [];
            $methods = $controller->getMethods();

            foreach ($methods as $method) {
                $moduleActionAttribute = ReflectionUtility::getAttributeInstance(ModuleAction::class, $method);

                if (!$moduleActionAttribute instanceof ModuleAction) {
                    continue;
                }

                $actionName = mb_substr($method->getName(), 0, -6);

                if (isset($specifiedActions) && !in_array($actionName, $specifiedActions, true)) {
                    continue;
                }

                if ($moduleActionAttribute->isDefault()) {
                    array_unshift($controllersAndActions[$controllerClassName], $actionName);
                } else {
                    $controllersAndActions[$controllerClassName][] = $actionName;
                }
            }

            unset($specifiedActions);
        }

        return $controllersAndActions;
    }

    private function determineIconIdentifier(
        MainModuleConfiguration       $configuration,
        ExtensionInformationInterface $extensionInformation,
    ): string {
        $iconIdentifier = $configuration->getIconIdentifier() ?? str_replace(
            '_',
            '-',
            $extensionInformation->getExtensionKey()
        ) . '-module-' . str_replace(
            '_',
            '-',
            GeneralUtility::camelCaseToLowerCaseUnderscored($configuration->getKey())
        );

        if ($this->iconRegistry->isRegistered($iconIdentifier)) {
            return $iconIdentifier;
        }

        if ($configuration instanceof ModuleConfiguration) {
            return 'module-generic';
        }

        // Fallback icon for top-level module:
        return 'modulegroup-help';
    }

    private function getDefaultLabelPath(
        ExtensionInformationInterface $extensionInformation,
        string                        $moduleKey,
    ): string {
        return 'LLL:EXT:' . $extensionInformation->getExtensionKey(
            ) . '/Resources/Private/Language/Backend/Modules/' . lcfirst($moduleKey) . '.xlf';
    }
}
