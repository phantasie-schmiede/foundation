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
        private TcaTable             $table,
        private string               $defaultLabelPath,
        private array                $palettes,
        private array                $tabs,
        private DefaultFieldRegistry $defaultFields,
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

            /*
             * If the reference still can't be resolved as a column, check the showitem items that are not columns:
             * the default fields (which are added after this loop), the user-defined palettes and tabs.
             */
            if (false === $fieldCanBeAdded) {
                $resolvedPosition = $this->resolveShowItemReference($parsedPosition);

                if (null !== $resolvedPosition) {
                    $position = $resolvedPosition;
                    $fieldCanBeAdded = true;
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
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws JsonException
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
            $this->normalizeShowItemPosition($palettePosition ?? '')
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
        $this->table->addFieldsToAllTypes(
            $tabDefinition,
            $typeList,
            $this->normalizeShowItemPosition($tabPosition ?? '')
        );

        return $tabDefinition;
    }

    /**
     * Resolves the given position against the showitem items that are not columns: the default field anchors (which
     * are added to the showitems after the position-dependencies of the columns have been resolved) and the
     * user-defined palettes and tabs.
     *
     * If the position is "after" a default field, the block of the default field is materialized first, so the
     * position reference exists. If the position is "before" a not yet materialized default field, the field is
     * appended at the end of the current showitems and the default field block is added after it.
     *
     * Returns the position normalized to a form the core can match, or null if the reference can't be resolved.
     *
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    private function resolveShowItemReference(Position $parsedPosition): ?string
    {
        if (!in_array(
            $parsedPosition->getKeyword(),
            [Column::POSITIONS['BEFORE'], Column::POSITIONS['AFTER']],
            true
        )) {
            return null;
        }

        $reference  = $parsedPosition->getReference();
        $definition = $this->defaultFields->findByReference($reference);

        if (null !== $definition) {
            if (Column::POSITIONS['AFTER'] === $parsedPosition->getKeyword()) {
                $this->defaultFields->materializeBlock($definition->getBlock());
            }

            return $parsedPosition->withReference($definition->getItem())
                                  ->toString();
        }

        if (isset($this->palettes[$reference])) {
            foreach ($this->table->getTypes() as $typeConfiguration) {
                if (ShowItemList::fromString($typeConfiguration['showitem'] ?? '')
                                ->containsPaletteReference($reference)) {
                    return $parsedPosition->withReference('--palette--;;' . $reference)
                                          ->toString();
                }
            }
        }

        if (isset($this->tabs[$reference])) {
            $tabItem = '--div--;' . LabelResolver::resolveLabel(
                $this->tabs[$reference]->getLabel(),
                $this->defaultLabelPath . 'tab.' . $reference . '.label',
                $reference
            );

            foreach ($this->table->getTypes() as $typeConfiguration) {
                if (in_array(
                    $tabItem,
                    ShowItemList::fromString($typeConfiguration['showitem'] ?? '')->getItems(),
                    true
                )) {
                    return $parsedPosition->withReference($tabItem)
                                          ->toString();
                }
            }
        }

        return null;
    }

    /**
     * Normalizes the position of a palette or tab against the showitem items that are not columns (see
     * resolveShowItemReference).
     *
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    private function normalizeShowItemPosition(string $position): string
    {
        if ('' === $position) {
            return $position;
        }

        return $this->resolveShowItemReference(Position::fromString($position)) ?? $position;
    }
}
