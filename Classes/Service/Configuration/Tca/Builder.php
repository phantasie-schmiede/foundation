<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Service\Configuration\Tca;

use InvalidArgumentException;
use JsonException;
use PSBits\Foundation\Attribute\TCA\Column;
use PSBits\Foundation\Attribute\TCA\ColumnType\ColumnTypeInterface;
use PSBits\Foundation\Attribute\TCA\ColumnType\ColumnTypeWithItemsInterface;
use PSBits\Foundation\Attribute\TCA\Ctrl;
use PSBits\Foundation\Attribute\TCA\Palette;
use PSBits\Foundation\Attribute\TCA\Tab;
use PSBits\Foundation\Attribute\TCA\Type;
use PSBits\Foundation\Exceptions\ImplementationException;
use PSBits\Foundation\Exceptions\MisconfiguredTcaException;
use PSBits\Foundation\Utility\Configuration\TcaUtility;
use PSBits\Foundation\Utility\ReflectionUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use RuntimeException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_keys;
use function is_array;

/**
 * Class Builder
 *
 * Builds the TCA of a domain model class from its attributes.
 *
 * @package PSBits\Foundation\Service\Configuration\Tca
 */
readonly class Builder
{
    public function __construct(
        private NameResolver $nameResolver,
    ) {
    }

    /**
     * This function will be executed when the core builds the TCA. It expands
     * the TCA on its own by scanning through the domain models of all
     * registered extensions (extensions which provide an ExtensionInformation
     * class, see \PSBits\Foundation\Data\ExtensionInformationInterface).
     * Transient domain models (those without a corresponding table in the
     * database) will be skipped.
     *
     * @param bool $overrideMode If set to false, the configuration of all original domain models (not extending other
     *                           domain models) is added to the TCA.
     *                           If set to true, the configuration of all extending domain models is added to the TCA.
     *
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws ImplementationException
     * @throws InvalidArgumentException
     * @throws JsonException
     * @throws MisconfiguredTcaException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function build(bool $overrideMode): void
    {
        foreach ($this->nameResolver->getMappedClassNames($overrideMode) as $fullQualifiedClassName => $tableName) {
            $this->buildFromAttributes($fullQualifiedClassName, $tableName, $overrideMode);
        }
    }

    /**
     * Builds the TCA of the given class from its attributes.
     *
     * @param string $className
     * @param string $tableName
     * @param bool   $overrideMode
     *
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws InvalidArgumentException
     * @throws JsonException
     * @throws MisconfiguredTcaException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function buildFromAttributes(string $className, string $tableName, bool $overrideMode): void
    {
        $table      = new TcaTable($tableName);
        $reflection = new ReflectionClass($className);

        /** @var Ctrl|null $ctrl */
        $ctrl = ReflectionUtility::getAttributeInstance(Ctrl::class, $reflection);

        if (!$overrideMode && null === $ctrl) {
            return;
        }

        $defaultLabelPath = LabelResolver::getDefaultLabelPath($className, $tableName);
        $properties       = $reflection->getProperties();

        if ($overrideMode) {
            /*
             * Filter out properties of parent class that are not overridden in current class to keep original
             * configuration!
             */
            $properties = array_filter($properties, static function($property) use ($reflection) {
                return $property->getDeclaringClass()
                        ->getName() === $reflection->getName();
            });
        }

        $columnConfigurations = $this->buildColumnConfigurations($properties, $className, $defaultLabelPath);

        if ([] === $columnConfigurations) {
            // No annotated properties found in class. Do nothing.
            return;
        }

        $ctrlInitializer = new CtrlInitializer($table);

        if (!$overrideMode) {
            $ctrlInitializer->initializeDummyConfiguration($ctrl);
        }

        $ctrlInitializer->apply($ctrl, $overrideMode, $reflection);
        $ctrlInitializer->ensureTitle($defaultLabelPath);

        $palettes = [];

        foreach ($reflection->getAttributes(Palette::class) as $paletteAttribute) {
            /** @var Palette $paletteConfiguration */
            $paletteConfiguration                             = $paletteAttribute->newInstance();
            $palettes[$paletteConfiguration->getIdentifier()] = $paletteConfiguration;
        }

        /** @var Palette $palette */
        foreach ($palettes as $palette) {
            $table->createPalette(
                $palette->getIdentifier(),
                $palette->getLabel(),
                $palette->getDescription(),
                $palette->isHiddenPalette(),
                $defaultLabelPath
            );
        }

        $tabs = [];

        foreach ($reflection->getAttributes(Tab::class) as $tabAttribute) {
            $tabConfiguration                         = $tabAttribute->newInstance();
            $tabs[$tabConfiguration->getIdentifier()] = $tabConfiguration;
        }

        foreach ($reflection->getAttributes(Type::class) as $typeAttribute) {
            /** @var Type $typeConfiguration */
            $typeConfiguration = $typeAttribute->newInstance();

            $this->createType($table, $typeConfiguration, $overrideMode);
        }

        /*
         * Initialize types for all columns that have a type set in their typeList property, that is not yet defined via
         * a Type attribute.
         */
        $this->initializeTypes($table, $columnConfigurations);

        $positionResolver = new PositionResolver($table, $defaultLabelPath, $palettes, $tabs);

        while (!empty($columnConfigurations)) {
            $newColumnAddedToTypes = false;

            foreach ($columnConfigurations as $columnName => $configuration) {
                $columnHasBeenAdded = $positionResolver->addFieldIfAlreadyPossible($configuration, $columnName);

                if (true === $columnHasBeenAdded) {
                    $newColumnAddedToTypes = true;
                }

                if (true === $columnHasBeenAdded || Column::TYPE_LIST_NONE === $configuration->getTypeList()) {
                    $columnConfiguration = $configuration->toArray();
                    $table->addColumnConfiguration($columnName, $columnConfiguration);
                    unset($columnConfigurations[$columnName]);
                }
            }

            if (false === $newColumnAddedToTypes) {
                throw new RuntimeException(
                    __CLASS__ . ': Position relations create a loop! Please remove unnecessary specifications. The combination fieldA:position="before:fieldB" and fieldB:position="after:fieldA" would cause this error. The unresolved fields are: ' . implode(
                        ', ',
                        array_keys($columnConfigurations)
                    ),
                    1646995607
                );
            }
        }

        /*
         * Add default fields at the end of showitems for all types.
         * Drawback: These fields can't be used as position reference.
         */
        if (true === $table->hasPalette(TcaUtility::CORE_PALETTE_IDENTIFIERS['LANGUAGE'])) {
            $positionResolver->addTabToShowItems(
                TcaUtility::CORE_TAB_IDENTIFIERS['LANGUAGE'],
                TcaUtility::CORE_TAB_LABELS['LANGUAGE']
            );
            $positionResolver->addPaletteToShowItems(TcaUtility::CORE_PALETTE_IDENTIFIERS['LANGUAGE']);
        }

        if (null !== $ctrl && is_array($ctrl->getEnableColumns())) {
            $disabledColumn = $ctrl->getEnableColumns()[Ctrl::ENABLE_COLUMN_IDENTIFIERS['DISABLED']];
        }

        if (isset($disabledColumn) || true === $table->hasPalette(
            TcaUtility::CORE_PALETTE_IDENTIFIERS['TIME_RESTRICTION']
        )) {
            $positionResolver->addTabToShowItems(
                TcaUtility::CORE_TAB_IDENTIFIERS['ACCESS'],
                TcaUtility::CORE_TAB_LABELS['ACCESS']
            );
        }

        if (isset($disabledColumn)) {
            $table->addFieldsToAllTypes($disabledColumn);
        }

        if (true === $table->hasPalette(TcaUtility::CORE_PALETTE_IDENTIFIERS['TIME_RESTRICTION'])) {
            $positionResolver->addPaletteToShowItems(TcaUtility::CORE_PALETTE_IDENTIFIERS['TIME_RESTRICTION']);
        }

        (new Validator())->validate($tableName);
    }

    /**
     * Collects the column configurations from the annotated properties of a class.
     *
     * @param ReflectionProperty[] $properties
     *
     * @return Column[]
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    private function buildColumnConfigurations(array $properties, string $className, string $defaultLabelPath): array
    {
        $columnConfigurations = [];

        foreach ($properties as $property) {
            $columnTypeAttribute = ReflectionUtility::getAttributeInstance(ColumnTypeInterface::class, $property);

            if (!$columnTypeAttribute instanceof ColumnTypeInterface) {
                continue;
            }

            $columnAttribute = ReflectionUtility::getAttributeInstance(
                Column::class,
                $property
            ) ?? GeneralUtility::makeInstance(Column::class);

            $columnName = $this->nameResolver->convertPropertyNameToColumnName($property->getName(), $className);

            if (empty($columnAttribute->getDescription())) {
                $description = LabelResolver::resolveLabel(
                    '',
                    $defaultLabelPath . $property->getName() . '.description',
                    '',
                    false
                );

                if ('' !== $description) {
                    $columnAttribute->setDescription($description);
                }
            }

            if ('' === $columnAttribute->getLabel()) {
                $label = $defaultLabelPath . $property->getName();
                $columnAttribute->setLabel(LabelResolver::resolveLabel('', $label, $label));
            }

            if ($columnTypeAttribute instanceof ColumnTypeWithItemsInterface) {
                $columnTypeAttribute->processItems(
                    $defaultLabelPath . $property->getName() . '.'
                );
            }

            $columnAttribute->setConfiguration($columnTypeAttribute);
            $columnConfigurations[$columnName] = $columnAttribute;
        }

        return $columnConfigurations;
    }

    private function createType(TcaTable $table, Type $typeConfiguration, bool $overrideMode): void
    {
        $typeConfigurationArray = [
            'showitem' => $typeConfiguration->getShowitem(),
        ];

        if (null !== $typeConfiguration->getColumnsOverrides()) {
            $typeConfigurationArray['columnsOverrides'] = $typeConfiguration->getColumnsOverrides();
        }

        if ([] !== $typeConfiguration->getCreationOptions()) {
            $typeConfigurationArray['creationOptions'] = $typeConfiguration->getCreationOptions();
        }

        if (null !== $typeConfiguration->getPreviewRenderer()) {
            $typeConfigurationArray['previewRenderer'] = $typeConfiguration->getPreviewRenderer();
        }

        $table->setTypeConfiguration($typeConfiguration->getRecordType(), $typeConfigurationArray, $overrideMode);
    }

    /**
     * @param Column[] $columnConfigurations
     */
    private function initializeTypes(TcaTable $table, array $columnConfigurations): void
    {
        foreach ($columnConfigurations as $configuration) {
            $typeList = $configuration->getTypeList();

            if ('' === $typeList) {
                continue;
            }

            $types = GeneralUtility::trimExplode(',', $typeList);

            foreach ($types as $type) {
                $table->ensureTypeConfiguration($type);
            }
        }
    }
}
