<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Upgrade;

use PSBits\Foundation\Service\ExtensionInformationService;
use PSBits\Foundation\Utility\Typo3VersionUtility;
use TYPO3\CMS\Core\Attribute\UpgradeWizard;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Upgrades\AbstractListTypeToCTypeUpdate;

use function mb_strtolower;
use function str_replace;

/**
 * Class ListTypeToCTypeUpdate
 *
 * Since TYPO3 v14, plugins registered via PSBits Foundation are content types instead of list_type subtypes. This
 * wizard converts the corresponding content elements (and the affected backend user group permissions) of all
 * installed extensions that provide an ExtensionInformation class.
 *
 * The core only provides the upgrade wizard infrastructure on TYPO3 v14, so the wizard is only declared there; on
 * v13 a placeholder keeps the class present for the DI container's class scan.
 *
 * @package PSBits\Foundation\Upgrade
 */
if (Typo3VersionUtility::isAtLeast('14.0')) {
    #[UpgradeWizard(identifier: 'foundation_listTypeToCType')]
    class ListTypeToCTypeUpdate extends AbstractListTypeToCTypeUpdate
    {
        private const NO_PLUGINS_PLACEHOLDER = '__foundation_no_plugins__';

        public function __construct(
            private readonly ExtensionInformationService $extensionInformationService,
            ConnectionPool                               $connectionPool,
        ) {
            parent::__construct($connectionPool);
        }

        protected function getListTypeToCTypeMapping(): array
        {
            $mapping = [];

            foreach ($this->extensionInformationService->getAllExtensionInformation() as $extensionInformation) {
                foreach ($extensionInformation->getPlugins() as $configuration) {
                    $listType = str_replace('_', '', $extensionInformation->getExtensionKey()) . '_' . mb_strtolower(
                        $configuration->getName()
                    );
                    $mapping[$listType] = $listType;
                }
            }

            /*
             * The parent class requires a non-empty mapping. When no plugin extension is installed, a placeholder keeps
             * the wizard inert; updateNecessary() returns false in that case.
             */
            if ([] === $mapping) {
                return [self::NO_PLUGINS_PLACEHOLDER => self::NO_PLUGINS_PLACEHOLDER];
            }

            return $mapping;
        }

        public function getTitle(): string
        {
            return 'Convert plugin content elements from list_type to CType';
        }

        public function getDescription(): string
        {
            return 'TYPO3 v14 registers plugins as content types. This wizard converts the content elements of all PSBits'
                . ' plugins from CType "list" with the corresponding list_type to the new CType and updates the affected'
                . ' backend user group permissions.';
        }

        public function updateNecessary(): bool
        {
            if (false === $this->hasPlugins()) {
                return false;
            }

            return parent::updateNecessary();
        }

        private function hasPlugins(): bool
        {
            foreach ($this->extensionInformationService->getAllExtensionInformation() as $extensionInformation) {
                if ([] !== $extensionInformation->getPlugins()) {
                    return true;
                }
            }

            return false;
        }
    }
} else {
    /**
     * Placeholder for TYPO3 v13, where the core does not provide the upgrade wizard infrastructure. The class
     * exists only to satisfy the DI container's class scan; no wizard is registered on that major.
     */
    class ListTypeToCTypeUpdate
    {
    }
}
