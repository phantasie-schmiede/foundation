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
use PSBits\Foundation\Data\ExtensionInformationInterface;
use PSBits\Foundation\Utility\Configuration\IconUtility;
use PSBits\Foundation\Utility\LocalizationUtility;
use PSBits\Foundation\Utility\Typo3VersionUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\ArrayUtility as Typo3CoreArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class PageTypeService
 *
 * @package PSBits\Foundation\Service\Configuration
 */
class PageTypeService
{
    public const array ICON_SUFFIXES = [
        'CONTENT_FROM_PID' => '-contentFromPid',
        'ROOT'             => '-root',
        'HIDE_IN_MENU'     => '-hideinmenu',
    ];
    public const array PAGE_TYPE_REGISTRATION_MODES = [
        'EXT_TABLES'   => 'ext_tables',
        'TCA_OVERRIDE' => 'tca_override',
    ];

    public function __construct(
        protected PageDoktypeRegistry $pageDoktypeRegistry,
    ) {
    }

    /**
     * Allow backend users to drag and drop the new page types.
     */
    public function addToDragArea(ExtensionInformationInterface $extensionInformation): void
    {
        /*
         * v14 determines the drag area doktypes automatically from the PageDoktypeRegistry and the
         * user's group permissions; the doktypesToShowInNewPageDragArea TSconfig option it used to
         * rely on is deprecated since v14.2, so there is nothing to register on that major.
         */
        if (Typo3VersionUtility::isAtLeast('14.0')) {
            return;
        }

        foreach ($extensionInformation->getPageTypes() as $configuration) {
            ExtensionManagementUtility::addUserTSConfig(
                'options.pageTree.doktypesToShowInNewPageDragArea := addToList(' . $configuration->getDoktype() . ')'
            );
        }
    }

    public function addToRegistry(ExtensionInformationInterface $extensionInformation): void
    {
        foreach ($extensionInformation->getPageTypes() as $configuration) {
            if (!empty($configuration->getAllowedTables())) {
                $doktypeConfiguration['allowedTables']     = implode(',', $configuration->getAllowedTables());
                $doktypeConfiguration['onlyAllowedTables'] = true;
            }

            $this->pageDoktypeRegistry->add($configuration->getDoktype(), $doktypeConfiguration ?? []);
        }
    }

    /**
     * Add new page types to the TCA of 'pages' (select box).
     *
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function addToSelectBox(
        ExtensionInformationInterface $extensionInformation,
    ): void {
        $table = 'pages';

        foreach ($extensionInformation->getPageTypes() as $configuration) {
            $doktype = $configuration->getDoktype();
            $name    = $configuration->getName();
            $label   = $configuration->getLabel() ?? 'LLL:EXT:' . $extensionInformation->getExtensionKey(
            ) . '/Resources/Private/Language/Backend/Configuration/TCA/Overrides/page.xlf:pageType.' . $name;

            if (!LocalizationUtility::translationExists($label)) {
                $label = ucfirst(trim(implode(' ', preg_split('/(?=[A-Z])/', $name))));
            }

            ExtensionManagementUtility::addTcaSelectItem($table, 'doktype', [
                $label,
                $doktype,
            ], '1', 'after');

            $iconIdentifier = $configuration->getIconIdentifier() ?? IconUtility::getDefaultIdentifier(
                $extensionInformation,
                'pageType' . ucfirst($name)
            );
            /*
             * On v14 the IconRegistry is unavailable while the TCA is built (ServiceProvider::getIconRegistry()
             * throws while the boot state is not complete), so the variant identifiers are added as is: the
             * core IconFactory falls back to the default icon identifier once the icon turns out to be
             * unregistered. On v13 the registry is resolved on demand (pages.php runs in that late phase) and
             * only registered variants are added.
             */
            $iconRegistry = Typo3VersionUtility::isAtLeast('14.0')
                ? null
                : GeneralUtility::makeInstance(IconRegistry::class);
            $icons = [
                $doktype => $iconIdentifier,
            ];

            foreach (self::ICON_SUFFIXES as $suffix) {
                if (null === $iconRegistry || $iconRegistry->isRegistered($iconIdentifier . $suffix)) {
                    $icons[$doktype . $suffix] = $iconIdentifier . $suffix;
                }
            }

            Typo3CoreArrayUtility::mergeRecursiveWithOverrule($GLOBALS['TCA'][$table], [
                // add icons for new page type:
                'ctrl'  => [
                    'typeicon_classes' => $icons,
                ],
                // add all page standard fields and tabs to your new page type
                'types' => [
                    $doktype => [
                        'showitem' => $GLOBALS['TCA'][$table]['types'][PageRepository::DOKTYPE_DEFAULT]['showitem'],
                    ],
                ],
            ]);
        }
    }
}
