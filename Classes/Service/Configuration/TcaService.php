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
use PSBits\Foundation\Attribute\TCA\Column;
use PSBits\Foundation\Attribute\TCA\ColumnType\ColumnTypeInterface;
use PSBits\Foundation\Attribute\TCA\ColumnType\ColumnTypeWithItemsInterface;
use PSBits\Foundation\Attribute\TCA\Ctrl;
use PSBits\Foundation\Attribute\TCA\Mapping\Field;
use PSBits\Foundation\Attribute\TCA\Mapping\Table;
use PSBits\Foundation\Attribute\TCA\Palette;
use PSBits\Foundation\Attribute\TCA\Tab;
use PSBits\Foundation\Exceptions\ImplementationException;
use PSBits\Foundation\Exceptions\MisconfiguredTcaException;
use PSBits\Foundation\Service\ExtensionInformationService;
use PSBits\Foundation\Utility\ArrayUtility;
use PSBits\Foundation\Utility\Configuration\TcaUtility;
use PSBits\Foundation\Utility\LocalizationUtility;
use PSBits\Foundation\Utility\ReflectionUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use RuntimeException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\ArrayUtility as Typo3ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\DomainObject\AbstractValueObject;
use TYPO3\CMS\Extbase\Persistence\ClassesConfiguration;
use function array_slice;
use function get_class;
use function in_array;
use function is_array;

/**
 * Class TcaService
 *
 * @package PSBits\Foundation\Service\Configuration
 */
class TcaService
{
    public const    array PALETTE_IDENTIFIERS      = [
        'LANGUAGE'         => 'language',
        'TIME_RESTRICTION' => 'timeRestriction',
    ];
    public const    string UNSET_KEYWORD           = 'UNSET';
    protected const array CLASS_TABLE_MAPPING_KEYS = [
        'TCA_OVERRIDES' => 'tcaOverrides',
        'TCA'           => 'tca',
    ];
    protected const array PROTECTED_COLUMNS        = [
        'crdate',
        'pid',
        'tstamp',
        'uid',
    ];

    protected static bool           $allowCaching         = true;
    protected static array          $classTableMapping    = [];
    protected ?ClassesConfiguration $classesConfiguration = null;
    protected string                $defaultLabelPath     = '';

    /**
     * @var Palette[]
     */
    protected array  $palettes  = [];
    protected string $tableName = '';

    /**
     * @var Tab[]
     */
    protected array $tabs = [];

    public function __construct(
        protected readonly ExtensionInformationService $extensionInformationService,
        protected readonly PackageManager              $packageManager,
    ) {
    }

    public function addColumnConfiguration(string $columnName, array $columnConfiguration): void
    {
        $this->checkIfTableNameIsSet();
        ExtensionManagementUtility::addTCAcolumns($this->tableName, [$columnName => $columnConfiguration]);
    }

    public function addToPalette(string $identifier, array $fieldNames, string $position = ''): void
    {
        $this->checkIfTableNameIsSet();

        if ('' !== $position && str_contains($position, ':')) {
            [
                $keyword,
                $referenceField,
            ] = GeneralUtility::trimExplode(':', $position);

            switch ($keyword) {
                case Palette::SPECIAL_POSITIONS['NEW_LINE_AFTER']:
                    $position = Column::POSITIONS['AFTER'] . ':' . $referenceField;
                    array_unshift($fieldNames, Palette::SPECIAL_FIELDS['LINE_BREAK']);
                    break;
                case Palette::SPECIAL_POSITIONS['NEW_LINE_BEFORE']:
                    $position = Column::POSITIONS['BEFORE'] . ':' . $referenceField;
                    $fieldNames[] = Palette::SPECIAL_FIELDS['LINE_BREAK'];
                    break;
            }
        }

        ExtensionManagementUtility::addFieldsToPalette(
            $this->tableName,
            $identifier,
            implode(', ', $fieldNames),
            $position
        );
    }

    /**
     * This function will be executed when the core builds the TCA, but as it does not return an array there will be no
     * entry for the required file, instead this function expands the TCA on its own by scanning through the domain
     * models of all registered extensions (extensions which provide an ExtensionInformation class, see
     * \PSBits\Foundation\Data\ExtensionInformationInterface).
     * Transient domain models (those without a corresponding table in the database) will be skipped.
     *
     * @param bool $overrideMode If set to false, the configuration of all original domain models (not extending other
     *                           domain models) is added to the TCA.
     *                           If set to true, the configuration of all extending domain models is added to the TCA.
     *
     * @return void
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws ImplementationException
     * @throws JsonException
     * @throws MisconfiguredTcaException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function buildTca(bool $overrideMode): void
    {
        $this->getClassesTableMapping();

        if ($overrideMode) {
            $key = self::CLASS_TABLE_MAPPING_KEYS['TCA_OVERRIDES'];
        } else {
            $key = self::CLASS_TABLE_MAPPING_KEYS['TCA'];
        }

        if (isset(self::$classTableMapping[$key])) {
            foreach (self::$classTableMapping[$key] as $fullQualifiedClassName => $tableName) {
                $this->setTableName($tableName);
                $this->buildFromAttributes($fullQualifiedClassName, $overrideMode);
            }
        }
    }

    public function checkIfTableNameIsSet(): void
    {
        if ('' === $this->tableName) {
            throw new RuntimeException(__CLASS__ . ': You have to specify a table with setTable() first!', 1646899798);
        }
    }

    /**
     * Checks the ClassesConfiguration of TYPO3 if already available (it is not during bootstrapping). Otherwise, the
     * value from mapping attributes is returned. As last fallback, the class name is converted according to the
     * naming convention.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function convertClassNameToTableName(string $className): string
    {
        if ($this->checkClassesConfiguration() && $this->classesConfiguration->hasClass($className)) {
            $configuration = $this->classesConfiguration->getConfigurationFor($className);

            if (!empty($configuration['tableName'])) {
                return $configuration['tableName'];
            }
        }

        $tableMapping = ReflectionUtility::getAttributeInstance(Table::class, $className);

        if ($tableMapping instanceof Table) {
            return $tableMapping->getName();
        }

        $classNameParts = explode('\\', $className);

        // Skip vendor and product name for core classes
        if (str_starts_with($className, 'TYPO3\\CMS\\')) {
            $classPartsToSkip = 2;
        } else {
            $classPartsToSkip = 1;
        }

        return 'tx_' . strtolower(implode('_', array_slice($classNameParts, $classPartsToSkip)));
    }

    /**
     * Checks the ClassesConfiguration of TYPO3 if already available (it is not during bootstrapping). Otherwise, the
     * value from mapping attributes is returned. As last fallback, the property name is converted according to the
     * naming convention.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function convertPropertyNameToColumnName(string $propertyName, string $className = null): string
    {
        if (!empty($className)) {
            if ($this->checkClassesConfiguration() && $this->classesConfiguration->hasClass($className)) {
                $configuration = $this->classesConfiguration->getConfigurationFor($className);

                if (!empty($configuration['properties'][$propertyName]['fieldName'])) {
                    return $configuration['properties'][$propertyName]['fieldName'];
                }
            }

            $propertyReflection = new ReflectionProperty($className, $propertyName);
            $fieldMapping = ReflectionUtility::getAttributeInstance(Field::class, $propertyReflection);

            if ($fieldMapping instanceof Field) {
                return $fieldMapping->getName();
            }
        }

        return GeneralUtility::camelCaseToLowerCaseUnderscored($propertyName);
    }

    /**
     * This uses the internal mapping of class names to table names to do a reverse lookup.
     * The result is an array of class names which are mapped to the given table name.
     * If no class is found, an empty array is returned.
     *
     * @throws ContainerExceptionInterface
     * @throws ImplementationException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function convertTableNameToClassNames(string $tableName): array
    {
        $searchResults = ArrayUtility::inArrayRecursive(
            $this->getClassesTableMapping(),
            $tableName
        );

        if (!empty($searchResults)) {
            // Explode each result path and keep only last part.
            return array_map(static function($item) {
                $arrayPathParts = explode('.', $item);

                return array_pop($arrayPathParts);
            }, $searchResults);
        }

        return [];
    }

    /**
     * An existing palette with given identifier would be overwritten!
     *
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function createPalette(string $identifier, string $label = '', string $description = ''): void
    {
        $this->checkIfTableNameIsSet();
        $paletteConfiguration = [];

        if ('' !== $label) {
            if (true === LocalizationUtility::validateLabel($label)) {
                $paletteConfiguration['label'] = $label;
            } else {
                $defaultLabel = $this->defaultLabelPath . 'palette.' . $identifier . '.label';

                if (LocalizationUtility::translationExists($defaultLabel)) {
                    $paletteConfiguration['label'] = $defaultLabel;
                }
            }
        }

        if ('' !== $description) {
            if (true === LocalizationUtility::validateLabel($description)) {
                $paletteConfiguration['description'] = $description;
            } else {
                $defaultDescription = $this->defaultLabelPath . 'palette.' . $identifier . '.description';

                if (LocalizationUtility::translationExists($defaultDescription)) {
                    $paletteConfiguration['description'] = $defaultDescription;
                }
            }
        }

        $GLOBALS['TCA'][$this->tableName]['palettes'][$identifier] = $paletteConfiguration;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ImplementationException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function getClassesTableMapping(): array
    {
        if (false === self::$allowCaching || empty(self::$classTableMapping)) {
            $this->buildClassesTableMapping();
        }

        return self::$classTableMapping;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function getConfigurationForPropertyOfDomainModel(AbstractEntity $domainModel, string $property): array
    {
        $tableName = $this->convertClassNameToTableName(get_class($domainModel));
        $column = $this->convertPropertyNameToColumnName($property);

        return $GLOBALS['TCA'][$tableName]['columns'][$column] ?? throw new RuntimeException(
            __CLASS__ . ': "' . $column . '" is not defined for table "' . $tableName . '"!', 1660914340
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function setTableName(string $classOrTableName): void
    {
        if (str_contains($classOrTableName, '\\')) {
            $classOrTableName = $this->convertClassNameToTableName($classOrTableName);
        }

        $this->tableName = $classOrTableName;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ImplementationException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    protected function buildClassesTableMapping(): void
    {
        self::$classTableMapping = [];
        $allExtensionInformation = $this->extensionInformationService->getAllExtensionInformation();

        foreach ($allExtensionInformation as $extensionInformation) {
            $classNames = $this->extensionInformationService->getDomainModelClassNames($extensionInformation);

            foreach ($classNames as $className) {
                $reflectionClass = new ReflectionClass($className);

                if ($reflectionClass->isAbstract() || $reflectionClass->isInterface()) {
                    continue;
                }

                $tableName = $this->convertClassNameToTableName($className);

                if (str_starts_with($tableName, 'tx_' . mb_strtolower($extensionInformation->getExtensionName()))) {
                    self::$classTableMapping[self::CLASS_TABLE_MAPPING_KEYS['TCA']][$className] = $tableName;
                } else {
                    self::$classTableMapping[self::CLASS_TABLE_MAPPING_KEYS['TCA_OVERRIDES']][$className] = $tableName;
                }
            }
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws JsonException
     * @throws MisconfiguredTcaException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    protected function buildFromAttributes(string $className, bool $overrideMode): void
    {
        $reflection = new ReflectionClass($className);

        /** @var Ctrl|null $ctrl */
        $ctrl = ReflectionUtility::getAttributeInstance(Ctrl::class, $reflection);

        if (!$overrideMode && null === $ctrl) {
            // @TODO: emit warning?
            return;
        }

        $extensionKey = $this->extensionInformationService->extractExtensionInformationFromClassName(
            $className
        )['extensionKey'];
        $this->defaultLabelPath = 'LLL:EXT:' . $extensionKey . '/Resources/Private/Language/Backend/Configuration/TCA/';

        if (isset($GLOBALS['TCA'][$this->tableName])) {
            $this->defaultLabelPath .= 'Overrides/';
        }

        $this->defaultLabelPath .= lcfirst($reflection->getShortName()) . '.xlf:';
        $this->palettes = [];

        foreach ($reflection->getAttributes(Palette::class) as $paletteAttribute) {
            /** @var Palette $paletteConfiguration */
            $paletteConfiguration = $paletteAttribute->newInstance();
            $this->palettes[$paletteConfiguration->getIdentifier()] = $paletteConfiguration;
        }

        /** @var Palette $palette */
        foreach ($this->palettes as $palette) {
            $this->createPalette($palette->getIdentifier(), $palette->getLabel(), $palette->getDescription());
        }

        $this->tabs = [];

        foreach ($reflection->getAttributes(Tab::class) as $tabAttribute) {
            $tabConfiguration = $tabAttribute->newInstance();
            $this->tabs[$tabConfiguration->getIdentifier()] = $tabConfiguration;
        }

        $properties = $reflection->getProperties();

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

            $columnName = $this->convertPropertyNameToColumnName($property->getName(), $className);

            if (empty($columnAttribute->getDescription())) {
                $label = $this->defaultLabelPath . $property->getName() . '.description';

                if (LocalizationUtility::translationExists($label, false)) {
                    $columnAttribute->setDescription($label);
                }
            }

            if ('' === $columnAttribute->getLabel()) {
                $label = $this->defaultLabelPath . $property->getName();
                LocalizationUtility::translationExists($label);
                $columnAttribute->setLabel($label);
            }

            if ($columnTypeAttribute instanceof ColumnTypeWithItemsInterface) {
                $columnTypeAttribute->processItems(
                    $this->defaultLabelPath . $property->getName() . '.'
                );
            }

            $columnAttribute->setConfiguration($columnTypeAttribute);
            $columnConfigurations[$columnName] = $columnAttribute;
        }

        if ([] === $columnConfigurations) {
            // No annotated properties found in class. Do nothing.
            return;
        }

        if (!$overrideMode) {
            $this->initializeDummyConfiguration($ctrl, $this->tableName);

            // default title may be overwritten by Ctrl-attribute in next block
            $title = $this->defaultLabelPath . 'ctrl.title';
            LocalizationUtility::translationExists($title);
            $GLOBALS['TCA'][$this->tableName]['ctrl']['title'] = $title;
        }

        if (null !== $ctrl) {
            if ($overrideMode) {
                $ctrlProperties = [];
                $setArguments = $reflection->getAttributes(Ctrl::class)[0]->getArguments();

                foreach ($setArguments as $key => $value) {
                    $ctrlProperties[TcaUtility::convertKey($key)] = $value;
                }
            } else {
                $ctrlProperties = $ctrl->toArray();
            }

            foreach ($ctrlProperties as $property => $value) {
                if (self::UNSET_KEYWORD === $value) {
                    unset($GLOBALS['TCA'][$this->tableName]['ctrl'][$property]);
                } else {
                    $GLOBALS['TCA'][$this->tableName]['ctrl'][$property] = $value;
                }
            }
        }

        $this->initializeTypes($columnConfigurations);

        while (!empty($columnConfigurations)) {
            $newColumnAddedToTypes = false;

            foreach ($columnConfigurations as $columnName => $configuration) {
                $columnHasBeenAdded = $this->addFieldIfAlreadyPossible($configuration, $columnName);

                if (true === $columnHasBeenAdded) {
                    $newColumnAddedToTypes = true;
                }

                if (true === $columnHasBeenAdded || Column::TYPE_LIST_NONE === $configuration->getTypeList()) {
                    $columnConfiguration = $configuration->toArray();
                    ExtensionManagementUtility::addTCAcolumns($this->tableName, [$columnName => $columnConfiguration]);
                    unset($columnConfigurations[$columnName]);
                }
            }

            if (false === $newColumnAddedToTypes) {
                throw new RuntimeException(
                    __CLASS__ . ': Position relations create a loop! Please remove unnecessary specifications. The combination fieldA:position="before:fieldB" and fieldB:position="after:fieldA" would cause this error. The unresolved fields are: ' . implode(
                        ', ',
                        array_keys($columnConfigurations)
                    ), 1646995607
                );
            }
        }

        /*
         * Add default fields at the end of showitems for all types.
         * Drawback: These fields can't be used as position reference.
         */
        if (true === $this->paletteExists(self::PALETTE_IDENTIFIERS['LANGUAGE'])) {
            $this->addTabToShowItems(TcaUtility::CORE_TAB_LABELS['LANGUAGE']);
            $this->addPaletteToShowItems(self::PALETTE_IDENTIFIERS['LANGUAGE']);
        }

        if (null !== $ctrl && is_array($ctrl->getEnablecolumns())) {
            $disabledColumn = $ctrl->getEnablecolumns()[Ctrl::ENABLE_COLUMN_IDENTIFIERS['DISABLED']];
        }

        if (isset($disabledColumn) || true === $this->paletteExists(self::PALETTE_IDENTIFIERS['TIME_RESTRICTION'])) {
            $this->addTabToShowItems(TcaUtility::CORE_TAB_LABELS['ACCESS']);
        }

        if (isset($disabledColumn)) {
            ExtensionManagementUtility::addToAllTCAtypes($this->tableName, $disabledColumn);
        }

        if (true === $this->paletteExists(self::PALETTE_IDENTIFIERS['TIME_RESTRICTION'])) {
            $this->addPaletteToShowItems(self::PALETTE_IDENTIFIERS['TIME_RESTRICTION']);
        }

        $this->validateConfiguration($this->tableName);
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
    private function addFieldIfAlreadyPossible(Column $attribute, string $columnName): bool
    {
        $fieldCanBeAdded = false;
        $newPaletteIdentifier = null;
        $newTabIdentifier = null;
        $position = $attribute->getPosition();
        $types = $GLOBALS['TCA'][$this->tableName]['types'];

        if ('' !== $attribute->getTypeList()) {
            $typeList = GeneralUtility::trimExplode(',', $attribute->getTypeList());
            $types = array_filter($types, static function($typeIdentifier) use ($typeList) {
                return in_array($typeIdentifier, $typeList, true);
            }, ARRAY_FILTER_USE_KEY);
        }

        if ('' === $position) {
            $fieldCanBeAdded = true;
        } else {
            [
                $keyword,
                $referenceField,
            ] = GeneralUtility::trimExplode(':', $attribute->getPosition());

            switch ($keyword) {
                case Column::POSITIONS['PALETTE']:
                    $newPaletteIdentifier = $referenceField;

                    if (!isset($this->palettes[$referenceField]) || '' === $this->palettes[$referenceField]->getPosition(
                        )) {
                        // Palette has no specified position: field and palette can be added without problems.
                        if (!isset($GLOBALS['TCA'][$this->tableName]['palettes'][$referenceField])) {
                            $this->createPalette($referenceField);
                        }

                        $fieldCanBeAdded = true;
                        break;
                    }

                    [
                        $paletteKeyword,
                        $referenceField,
                    ] = GeneralUtility::trimExplode(
                        ':',
                        $this->palettes[$referenceField]->getPosition()
                    );

                    if (Column::POSITIONS['TAB'] === $paletteKeyword) {
                        $newTabIdentifier = $referenceField;

                        if (!isset($this->tabs[$referenceField]) || '' === $this->tabs[$referenceField]->getPosition(
                            )) {
                            // Tab has no specified position: palette and tab can be added without problems.
                            $fieldCanBeAdded = true;
                            break;
                        }

                        [
                            ,
                            $referenceField,
                        ] = GeneralUtility::trimExplode(
                            ':',
                            $this->tabs[$referenceField]->getPosition()
                        );
                    }

                    break;
                case Column::POSITIONS['TAB']:
                    $newTabIdentifier = $referenceField;

                    if (!isset($this->tabs[$referenceField]) || '' === $this->tabs[$referenceField]->getPosition()) {
                        // Tab has no specified position: field and tab can be added without problems.
                        $fieldCanBeAdded = true;
                        break;
                    }

                    [
                        ,
                        $referenceField,
                    ] = GeneralUtility::trimExplode(':', $this->tabs[$referenceField]->getPosition());
                    break;
                case Palette::SPECIAL_POSITIONS['NEW_LINE_AFTER']:
                    $position = Column::POSITIONS['AFTER'] . ':' . $referenceField;
                    $columnName = Palette::SPECIAL_FIELDS['LINE_BREAK'] . ',' . $columnName;
                    break;
                case Palette::SPECIAL_POSITIONS['NEW_LINE_BEFORE']:
                    $position = Column::POSITIONS['BEFORE'] . ':' . $referenceField;
                    $columnName .= ',' . Palette::SPECIAL_FIELDS['LINE_BREAK'];
                    break;
            }

            if (false === $fieldCanBeAdded) {
                // Check if $referenceField is located inside a palette
                $containingPalettes = [];

                foreach ($GLOBALS['TCA'][$this->tableName]['palettes'] as $paletteIdentifier => $paletteConfiguration) {
                    $fieldList = GeneralUtility::trimExplode(',', $paletteConfiguration['showitem']);
                    array_walk($fieldList, static function(&$item) {
                        $item = explode(';', $item)[0];
                    });

                    if (in_array($referenceField, $fieldList, true)) {
                        // @TODO: Palettes may define a label between ;;. Consider this case, too!
                        $containingPalettes[] = '--palette--;;' . $paletteIdentifier;
                    }
                }

                foreach ($types as $typeConfiguration) {
                    $fieldList = GeneralUtility::trimExplode(',', $typeConfiguration['showitem'] ?? '');

                    if (in_array($referenceField, $fieldList, true)) {
                        $fieldCanBeAdded = true;
                        break;
                    }

                    foreach ($containingPalettes as $palette) {
                        if (in_array($palette, $fieldList, true)) {
                            $fieldCanBeAdded = true;
                            break 2;
                        }
                    }
                }
            }
        }

        if (true === $fieldCanBeAdded) {
            if (null !== $newTabIdentifier) {
                $tabDefinition = $this->addTabToShowItems($newTabIdentifier, $attribute->getTypeList());
                $position = Column::POSITIONS['AFTER'] . ':' . $tabDefinition;
            }

            if (null !== $newPaletteIdentifier) {
                $this->addToPalette($newPaletteIdentifier, [$columnName]);
                $this->addPaletteToShowItems($newPaletteIdentifier, $attribute->getTypeList());
            } else {
                ExtensionManagementUtility::addToAllTCAtypes(
                    $this->tableName,
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
    private function addPaletteToShowItems(string $paletteIdentifier, string $typeList = ''): void
    {
        if (isset($this->palettes[$paletteIdentifier])) {
            $palettePosition = $this->palettes[$paletteIdentifier]->getPosition();
        }

        ExtensionManagementUtility::addToAllTCAtypes(
            $this->tableName,
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
    private function addTabToShowItems(string $identifier, string $typeList = ''): string
    {
        if (isset($this->tabs[$identifier])) {
            $label = $this->tabs[$identifier]->getLabel();
            $tabPosition = $this->tabs[$identifier]->getPosition();
        }

        if (false === LocalizationUtility::validateLabel($label ?? $identifier)) {
            $defaultLabel = $this->defaultLabelPath . 'tab.' . $identifier . '.label';

            if (LocalizationUtility::translationExists($defaultLabel)) {
                $label = $defaultLabel;
            } else {
                $label = $identifier;
            }
        }

        $tabDefinition = '--div--;' . ($label ?? $identifier);
        ExtensionManagementUtility::addToAllTCAtypes($this->tableName, $tabDefinition, $typeList, $tabPosition ?? '');

        return $tabDefinition;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function checkClassesConfiguration(): bool
    {
        if (null === $this->classesConfiguration) {
            /*
             * Copied from TYPO3\CMS\Extbase\Persistence\ClassesConfigurationFactory because instantiation of that class
             * would throw an exception (e. g. CacheManager not available, dependency injection not ready).
             */
            $classes = [];

            foreach ($this->packageManager->getActivePackages() as $activePackage) {
                $persistenceClassesFile = $activePackage->getPackagePath(
                    ) . 'Configuration/Extbase/Persistence/Classes.php';

                if (file_exists($persistenceClassesFile)) {
                    $definedClasses = require $persistenceClassesFile;

                    if (is_array($definedClasses)) {
                        Typo3ArrayUtility::mergeRecursiveWithOverrule(
                            $classes,
                            $definedClasses,
                            true,
                            false
                        );
                    }
                }
            }

            $classes = $this->inheritPropertiesFromParentClasses($classes);
            $this->classesConfiguration = GeneralUtility::makeInstance(ClassesConfiguration::class, $classes);
        }

        return $this->classesConfiguration instanceof ClassesConfiguration;
    }

    /**
     * Copied from TYPO3\CMS\Extbase\Persistence\ClassesConfigurationFactory because instantiation of that class would
     * throw an exception (e. g. CacheManager not available, dependency injection not ready).
     */
    private function inheritPropertiesFromParentClasses(array $classes): array
    {
        foreach (array_keys($classes) as $className) {
            if (!isset($classes[$className]['properties'])) {
                $classes[$className]['properties'] = [];
            }

            /*
             * At first we need to clean the list of parent classes.
             * This methods is expected to be called for models that either inherit
             * AbstractEntity or AbstractValueObject, therefore we want to know all
             * parents of $className until one of these parents.
             */
            $relevantParentClasses = [];
            $parentClasses = class_parents($className) ?: [];
            while (null !== $parentClass = array_shift($parentClasses)) {
                if (in_array(
                    $parentClass,
                    [
                        AbstractEntity::class,
                        AbstractValueObject::class,
                    ],
                    true
                )) {
                    break;
                }

                $relevantParentClasses[] = $parentClass;
            }

            /*
             * Once we found all relevant parent classes of $class, we can check their
             * property configuration and merge theirs with the current one. This is necessary
             * to get the property configuration of parent classes in the current one to not
             * miss data in the model later on.
             */
            foreach ($relevantParentClasses as $currentClassName) {
                if (null === $properties = $classes[$currentClassName]['properties'] ?? null) {
                    continue;
                }

                // Merge new properties over existing ones.
                $classes[$className]['properties'] = array_replace_recursive(
                    $properties,
                    $classes[$className]['properties'] ?? []
                );
            }
        }

        return $classes;
    }

    private function initializeDummyConfiguration(Ctrl $ctrl, string $tableName): void
    {
        $GLOBALS['TCA'][$this->tableName] = [
            'types'    => [
                0 => ['showitem' => ''],
            ],
            'palettes' => [],
            'columns'  => [],
        ];

        $enableColumns = $ctrl->getEnableColumns();

        if (is_array($enableColumns)) {
            if (isset($enableColumns[Ctrl::ENABLE_COLUMN_IDENTIFIERS['DISABLED']])) {
                $this->addColumnConfiguration(
                    $enableColumns[Ctrl::ENABLE_COLUMN_IDENTIFIERS['DISABLED']],
                    TcaUtility::getDefaultConfigurationForDisabledField()
                );
            }

            if (isset($enableColumns[Ctrl::ENABLE_COLUMN_IDENTIFIERS['STARTTIME']])) {
                $this->addColumnConfiguration(
                    $enableColumns[Ctrl::ENABLE_COLUMN_IDENTIFIERS['STARTTIME']],
                    TcaUtility::getDefaultConfigurationForStartTimeField()
                );
                $this->addToPalette(
                    self::PALETTE_IDENTIFIERS['TIME_RESTRICTION'],
                    [$enableColumns[Ctrl::ENABLE_COLUMN_IDENTIFIERS['STARTTIME']]]
                );
            }

            if (isset($enableColumns[Ctrl::ENABLE_COLUMN_IDENTIFIERS['ENDTIME']])) {
                $this->addColumnConfiguration(
                    $enableColumns[Ctrl::ENABLE_COLUMN_IDENTIFIERS['ENDTIME']],
                    TcaUtility::getDefaultConfigurationForEndTimeField()
                );
                $this->addToPalette(
                    self::PALETTE_IDENTIFIERS['TIME_RESTRICTION'],
                    [$enableColumns[Ctrl::ENABLE_COLUMN_IDENTIFIERS['ENDTIME']]]
                );
            }
        }

        if (!empty($ctrl->getLanguageField())) {
            $this->addColumnConfiguration(
                $ctrl->getLanguageField(),
                TcaUtility::getDefaultConfigurationForLanguageField()
            );
            $this->addToPalette(self::PALETTE_IDENTIFIERS['LANGUAGE'], [$ctrl->getLanguageField()]);
        }

        if (!empty($ctrl->getTransOrigPointerField())) {
            $this->addColumnConfiguration(
                $ctrl->getTransOrigPointerField(),
                TcaUtility::getDefaultConfigurationForTransOrigPointerField($tableName)
            );
            $this->addToPalette(self::PALETTE_IDENTIFIERS['LANGUAGE'], [$ctrl->getTransOrigPointerField()]);
        }

        if (!empty($ctrl->getTransOrigDiffSourceField())) {
            $this->addColumnConfiguration(
                $ctrl->getTransOrigDiffSourceField(),
                TcaUtility::getDefaultConfigurationForTransOrigDiffSourceField()
            );
        }

        if (!empty($ctrl->getTranslationSource())) {
            $this->addColumnConfiguration(
                $ctrl->getTranslationSource(),
                TcaUtility::getDefaultConfigurationForTranslationSourceField()
            );
        }
    }

    private function initializeTypes(array $columnConfigurations): void
    {
        foreach ($columnConfigurations as $configuration) {
            $typeList = $configuration->getTypeList();
            $types = GeneralUtility::trimExplode(',', $typeList);

            foreach ($types as $type) {
                if (!isset($GLOBALS['TCA'][$this->tableName]['types'][$type])) {
                    $GLOBALS['TCA'][$this->tableName]['types'][$type] = ['showitem' => ''];
                }
            }
        }
    }

    private function paletteExists(string $identifier): bool
    {
        return isset($GLOBALS['TCA'][$this->tableName]['palettes'][$identifier]);
    }

    /**
     * @throws MisconfiguredTcaException
     */
    private function validateConfiguration(string $tableName): void
    {
        $configuration = $GLOBALS['TCA'][$tableName];

        if (isset($configuration['ctrl']['sortby'])) {
            if (isset($configuration['ctrl']['default_sortby'])) {
                throw new MisconfiguredTcaException(
                    $tableName . ': You have to decide whether to use sortby or default_sortby. Your current configuration defines both of them.',
                    1541107594
                );
            }

            if (in_array($configuration['ctrl']['sortby'], self::PROTECTED_COLUMNS, true)) {
                throw new MisconfiguredTcaException(
                    $tableName . ': Your current configuration would overwrite a reserved system column with sorting values!',
                    1541107601
                );
            }
        }
    }
}
