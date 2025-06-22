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
use PSBits\Foundation\Utility\LocalizationUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\ArrayUtility as Typo3CoreArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Class PageTypeService
 *
 * @package PSBits\Foundation\Service\Configuration
 */
class PageTypeService
{
    public const array ICON_SUFFIXES                = [
        'CONTENT_FROM_PID' => '-contentFromPid',
        'ROOT'             => '-root',
        'HIDE_IN_MENU'     => '-hideinmenu',
    ];
    public const array PAGE_TYPE_REGISTRATION_MODES = [
        'EXT_TABLES'   => 'ext_tables',
        'TCA_OVERRIDE' => 'tca_override',
    ];

    public function __construct(
        protected IconRegistry        $iconRegistry,
        protected PageDoktypeRegistry $pageDoktypeRegistry,
    ) {
    }

    /**
     * Allow backend users to drag and drop the new page types.
     */
    public function addToDragArea(ExtensionInformationInterface $extensionInformation): void
    {
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
                $doktypeConfiguration['allowedTables'] = implode(',', $configuration->getAllowedTables());
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
            $label = $configuration['label'] ?? 'LLL:EXT:' . $extensionInformation->getExtensionKey(
            ) . '/Resources/Private/Language/Backend/Configuration/TCA/Overrides/page.xlf:pageType.' . $doktype;
            LocalizationUtility::translationExists($label);

            ExtensionManagementUtility::addTcaSelectItem($table, 'doktype', [
                $label,
                $doktype,
            ], '1', 'after');

            $iconIdentifier = $configuration['iconIdentifier'] ?? 'page-type-' . $doktype;
            $icons = [
                $doktype => $iconIdentifier,
            ];

            foreach (self::ICON_SUFFIXES as $suffix) {
                if ($this->iconRegistry->isRegistered($iconIdentifier . $suffix)) {
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
