<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\EventListener;

use InvalidArgumentException;
use JsonException;
use PSBits\Foundation\Data\ExtensionInformationInterface;
use PSBits\Foundation\Data\PluginConfiguration;
use PSBits\Foundation\Service\ExtensionInformationService;
use PSBits\Foundation\Utility\LocalizationUtility;
use PSBits\Foundation\Utility\Typo3VersionUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use TYPO3\CMS\Backend\Controller\Event\ModifyNewContentElementWizardItemsEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_keys;
use function lcfirst;
use function mb_strtolower;
use function str_replace;
use function str_starts_with;

/**
 * Class ContentElementWizardPluginRegistration
 *
 * On both supported core majors, the content element wizard picks up plugin items derived from the TCA (on v13 via the
 * list_type subtypes, on v14 via the CType registration). This listener replaces those items with the configuration
 * from the plugin's ExtensionInformation, or removes them, if the plugin is excluded from the wizard.
 *
 * @package PSBits\Foundation\EventListener
 */
#[AsEventListener(identifier: 'foundation_contentElementWizardPluginRegistration')]
final readonly class ContentElementWizardPluginRegistration
{
    public function __construct(
        private ExtensionInformationService $extensionInformationService,
        private IconRegistry                $iconRegistry,
    ) {
    }

    public function __invoke(ModifyNewContentElementWizardItemsEvent $event): void
    {
        foreach ($this->extensionInformationService->getAllExtensionInformation() as $extensionInformation) {
            foreach ($extensionInformation->getPlugins() as $configuration) {
                $this->handlePlugin($event, $extensionInformation, $configuration);
            }
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    private function handlePlugin(
        ModifyNewContentElementWizardItemsEvent $event,
        ExtensionInformationInterface           $extensionInformation,
        PluginConfiguration                     $configuration,
    ): void {
        $listType      = $this->getPluginListType($extensionInformation, $configuration);
        $defaultValues = $this->getPluginDefaultValues($listType);

        $this->removeTcaDerivedWizardItem($event, $defaultValues);

        if (false === $configuration->isAddToElementWizard()) {
            return;
        }

        $group = $configuration->getGroup() ?: mb_strtolower($extensionInformation->getVendorName());

        $event->setWizardItem(
            $group . '_' . $listType,
            [
                'description'    => $this->getDescription($extensionInformation, $configuration, $group),
                'iconIdentifier' => $this->getIconIdentifier($extensionInformation, $configuration),
                'title'          => $this->getTitle($extensionInformation, $configuration, $group),
                'defaultValues'  => $defaultValues,
            ],
            ['after' => $this->getAnchorForGroup($event, $group, $extensionInformation)]
        );
    }

    /**
     * Removes the wizard item derived from the TCA plugin registration, so the custom configuration (or the
     * exclusion) is not rendered twice.
     *
     * @param array<string, string> $defaultValues
     */
    private function removeTcaDerivedWizardItem(ModifyNewContentElementWizardItemsEvent $event, array $defaultValues): void
    {
        foreach ($event->getWizardItems() as $identifier => $item) {
            if (($item['defaultValues'] ?? null) === $defaultValues) {
                $event->removeWizardItem((string)$identifier);

                return;
            }
        }
    }

    /**
     * Returns the identifier the new wizard item has to be placed after: the last existing item of the group or the
     * group header. If the group does not exist yet, its header is registered first.
     */
    private function getAnchorForGroup(
        ModifyNewContentElementWizardItemsEvent $event,
        string                                 $group,
        ExtensionInformationInterface         $extensionInformation,
    ): string {
        $anchor = null;

        foreach (array_keys($event->getWizardItems()) as $key) {
            if ($key === $group || str_starts_with((string)$key, $group . '_')) {
                $anchor = (string)$key;
            }
        }

        if (null !== $anchor) {
            return $anchor;
        }

        $header = 'LLL:EXT:' . $extensionInformation->getExtensionKey()
            . '/Resources/Private/Language/Backend/Configuration/TsConfig/Page/Mod/Wizards/newContentElement.xlf:'
            . $group . '.header';
        LocalizationUtility::translationExists($header);

        $event->setWizardItem(
            $group,
            ['header' => LocalizationUtility::translate($header, $extensionInformation->getExtensionName()) ?? '']
        );

        return $group;
    }

    private function getDescription(
        ExtensionInformationInterface $extensionInformation,
        PluginConfiguration           $configuration,
        string                       $group,
    ): string {
        $ll = $this->getLabelPath($extensionInformation, $configuration, $group) . '.description';

        if (false === LocalizationUtility::translationExists($ll)) {
            return '';
        }

        return (string)LocalizationUtility::translate($ll, $extensionInformation->getExtensionName());
    }

    private function getTitle(
        ExtensionInformationInterface $extensionInformation,
        PluginConfiguration           $configuration,
        string                       $group,
    ): string {
        $ll = $this->getLabelPath($extensionInformation, $configuration, $group) . '.title';

        if (true === LocalizationUtility::translationExists($ll, false)) {
            return (string)LocalizationUtility::translate($ll, $extensionInformation->getExtensionName());
        }

        $ll = $this->getDefaultLabelPathForPlugin($extensionInformation, $configuration->getName());

        if (false === LocalizationUtility::translationExists($ll)) {
            return $configuration->getName();
        }

        return (string)LocalizationUtility::translate($ll, $extensionInformation->getExtensionName());
    }

    private function getIconIdentifier(
        ExtensionInformationInterface $extensionInformation,
        PluginConfiguration           $configuration,
    ): string {
        $iconIdentifier = $configuration->getIconIdentifier() ?: $extensionInformation->getExtensionKey()
            . '-' . str_replace('_', '-', GeneralUtility::camelCaseToLowerCaseUnderscored($configuration->getName()));

        return $this->iconRegistry->isRegistered($iconIdentifier) ? $iconIdentifier : 'content-plugin';
    }

    private function getLabelPath(
        ExtensionInformationInterface $extensionInformation,
        PluginConfiguration           $configuration,
        string                       $group,
    ): string {
        return 'LLL:EXT:' . $extensionInformation->getExtensionKey()
            . '/Resources/Private/Language/Backend/Configuration/TsConfig/Page/Mod/Wizards/newContentElement.xlf:'
            . $group . '.elements.' . lcfirst($configuration->getName());
    }

    private function getDefaultLabelPathForPlugin(
        ExtensionInformationInterface $extensionInformation,
        string                        $pluginName,
    ): string {
        return 'LLL:EXT:' . $extensionInformation->getExtensionKey()
            . '/Resources/Private/Language/Backend/Configuration/TCA/Overrides/tt_content.xlf:plugin.'
            . lcfirst($pluginName) . '.title';
    }

    /**
     * @return array<string, string>
     */
    private function getPluginDefaultValues(string $listType): array
    {
        /*
         * On v14, plugins are registered as their own content type instead of list_type subtypes.
         */
        if (Typo3VersionUtility::isAtLeast('14.0')) {
            return ['CType' => $listType];
        }

        return ['CType' => 'list', 'list_type' => $listType];
    }

    private function getPluginListType(
        ExtensionInformationInterface $extensionInformation,
        PluginConfiguration           $configuration,
    ): string {
        return str_replace('_', '', $extensionInformation->getExtensionKey()) . '_' . mb_strtolower(
            $configuration->getName()
        );
    }
}
