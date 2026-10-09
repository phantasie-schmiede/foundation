<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Service\Configuration\Tca;

use JsonException;
use PSBits\Foundation\Attribute\TCA\Column;
use PSBits\Foundation\Attribute\TCA\Palette;
use PSBits\Foundation\Attribute\TCA\Tab;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function in_array;

/**
 * Class PositionResolver
 *
 * Resolves the position-dependencies of TCA fields (e.g. "after:myField", "palette:myPalette", "tab:myTab") and
 * adds the fields, palettes and tabs to the showitems as soon as all requirements are met.
 *
 * @package PSBits\Foundation\Service\Configuration\Tca
 */
readonly class PositionResolver
{
    /**
     * @param Palette[] $palettes
     * @param Tab[]     $tabs
     */
    public function __construct(
        private TcaTable $table,
        private string   $defaultLabelPath,
        private array    $palettes,
        private array    $tabs,
    ) {
    }

    /**
     * This method resolves position-dependencies and only adds the field (and palette or tab) if all requirements are
     * met.
     *
     * @param Column $attribute
     * @param string $columnName
     *
     * @return bool returns true if the field could be added to TCA
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function addFieldIfAlreadyPossible(Column $attribute, string $columnName): bool
    {
        $fieldCanBeAdded      = false;
        $newPaletteIdentifier = null;
        $newTabIdentifier     = null;
        $position             = $attribute->getPosition();
        $types                = $this->table->getTypes();

        if ('' !== $attribute->getTypeList()) {
            $typeList = GeneralUtility::trimExplode(',', $attribute->getTypeList());
            $types    = array_filter($types, static function($typeIdentifier) use ($typeList) {
                return in_array($typeIdentifier, $typeList, true);
            }, ARRAY_FILTER_USE_KEY);
        }

        if ('' === $position) {
            $fieldCanBeAdded = true;
        } else {
            [
                'position'   => $position,
                'fieldNames' => $columnNames,
            ]               = Position::applyLineBreaks($position, [$columnName]);
            $columnName     = implode(',', $columnNames);
            $parsedPosition = Position::fromString($position);
            $referenceField = $parsedPosition->getReference();

            switch ($parsedPosition->getKeyword()) {
                case Column::POSITIONS['PALETTE']:
                    $newPaletteIdentifier = $referenceField;

                    if (!isset($this->palettes[$referenceField]) || '' === $this->palettes[$referenceField]->getPosition(
                    )) {
                        // Palette has no specified position: field and palette can be added without problems.
                        if (!$this->table->hasPalette($referenceField)) {
                            $this->table->createPalette($referenceField, defaultLabelPath: $this->defaultLabelPath);
                        }

                        $fieldCanBeAdded = true;

                        break;
                    }

                    $palettePosition = Position::fromString($this->palettes[$referenceField]->getPosition());

                    if (Column::POSITIONS['TAB'] === $palettePosition->getKeyword()) {
                        $newTabIdentifier = $palettePosition->getReference();

                        if (!isset($this->tabs[$newTabIdentifier]) || '' === $this->tabs[$newTabIdentifier]->getPosition(
                        )) {
                            // Tab has no specified position: palette and tab can be added without problems.
                            $fieldCanBeAdded = true;

                            break;
                        }

                        $referenceField = Position::fromString(
                            $this->tabs[$newTabIdentifier]->getPosition()
                        )
                            ->getReference();
                    }

                    break;
                case Column::POSITIONS['TAB']:
                    $newTabIdentifier = $referenceField;

                    if (!isset($this->tabs[$referenceField]) || '' === $this->tabs[$referenceField]->getPosition()) {
                        // Tab has no specified position: field and tab can be added without problems.
                        $fieldCanBeAdded = true;

                        break;
                    }

                    $referenceField = Position::fromString(
                        $this->tabs[$referenceField]->getPosition()
                    )
                        ->getReference();

                    break;
            }

            if (false === $fieldCanBeAdded) {
                // Check if $referenceField is located inside a palette
                $containingPalettes = [];

                foreach ($this->table->getPalettes() as $paletteIdentifier => $paletteConfiguration) {
                    $showItemList = ShowItemList::fromString($paletteConfiguration['showitem'] ?? '');

                    if ($showItemList->containsField($referenceField)) {
                        $containingPalettes[] = (string)$paletteIdentifier;
                    }
                }

                foreach ($types as $typeConfiguration) {
                    $showItemList = ShowItemList::fromString($typeConfiguration['showitem'] ?? '');

                    if ($showItemList->containsField($referenceField)) {
                        $fieldCanBeAdded = true;

                        break;
                    }

                    foreach ($containingPalettes as $paletteIdentifier) {
                        if ($showItemList->containsPaletteReference($paletteIdentifier)) {
                            $fieldCanBeAdded = true;

                            break 2;
                        }
                    }
                }
            }
        }

        if (true === $fieldCanBeAdded) {
            if (null !== $newTabIdentifier) {
                $tabDefinition = $this->addTabToShowItems(
                    identifier: $newTabIdentifier,
                    typeList  : $attribute->getTypeList()
                );
                $position = Column::POSITIONS['AFTER'] . ':' . $tabDefinition;
            }

            if (null !== $newPaletteIdentifier) {
                $this->table->addFieldsToPalette($newPaletteIdentifier, [$columnName]);
                $this->addPaletteToShowItems($newPaletteIdentifier, $attribute->getTypeList());
            } else {
                $this->table->addFieldsToAllTypes(
                    $columnName,
                    $attribute->getTypeList(),
                    $position
                );
            }
        }

        return $fieldCanBeAdded;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function addPaletteToShowItems(string $paletteIdentifier, string $typeList = ''): void
    {
        if (isset($this->palettes[$paletteIdentifier])) {
            $palettePosition = $this->palettes[$paletteIdentifier]->getPosition();
        }

        $this->table->addFieldsToAllTypes(
            '--palette--;;' . $paletteIdentifier,
            $typeList,
            $palettePosition ?? ''
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function addTabToShowItems(string $identifier, string $label = '', string $typeList = ''): string
    {
        if (isset($this->tabs[$identifier])) {
            $label       = $this->tabs[$identifier]->getLabel();
            $tabPosition = $this->tabs[$identifier]->getPosition();
        }

        $label = LabelResolver::resolveLabel(
            $label,
            $this->defaultLabelPath . 'tab.' . $identifier . '.label',
            $identifier
        );

        $tabDefinition = '--div--;' . $label;
        $this->table->addFieldsToAllTypes($tabDefinition, $typeList, $tabPosition ?? '');

        return $tabDefinition;
    }
}
