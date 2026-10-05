<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Attribute\TCA;

use Attribute;
use PSBits\Foundation\Attribute\TCA\ColumnType\ColumnTypeInterface;
use PSBits\Foundation\Utility\Configuration\TcaUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function str_contains;

/**
 * Class Column
 *
 * @package PSBits\Foundation\Attribute\TCA
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Column extends AbstractTcaAttribute
{
    public const array COLUMN_FIELDS = [
        'description',
        'displayCond',
        'exclude',
        'l10nDisplay',
        'l10nMode',
        'label',
        'onChange',
    ];
    public const array CONFIGURATION_IDENTIFIERS = [
        'DATABASE_DEFINITION' => 'databaseDefinition',
        'DATABASE_KEY'        => 'databaseKey',
    ];
    public const array POSITIONS = [
        'AFTER'   => 'after',
        'BEFORE'  => 'before',
        'PALETTE' => 'palette',
        'REPLACE' => 'replace',
        'TAB'     => 'tab',
    ];

    // If you don't want a field to be shown in backend at all, set this value for typeList.
    public const string TYPE_LIST_NONE = 'none';

    protected ?ColumnTypeInterface $configuration = null;

    /**
     * @param bool              $addDatabaseKey     Set to true to add this field as simple key like
     *                                              "KEY my_field (my_field)".
     * @param bool|null         $allowLanguageSynchronization https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Input/Index.html#confval-input-behaviour-allowlanguagesynchronization
     * @param string|null       $databaseDefinition Use this property to override the automatically generated
     *                                              definition.
     * @param mixed             $default            https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Input/Index.html#confval-input-default
     * @param string|null       $description        https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/Columns/Index.html#confval-columns-description
     * @param string|array|null $displayCond        https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/Columns/Index.html#confval-columns-displaycond
     * @param bool|null         $exclude            https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/Columns/Index.html#confval-columns-exclude
     * @param array|null        $fieldControl       https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/CommonProperties/FieldControl/Index.html#confval-fieldcontrol
     * @param array|null        $fieldInformation   https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/CommonProperties/FieldInformation/Index.html#confval-fieldinformation
     * @param array|null        $fieldWizard        https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/CommonProperties/FieldWizard/Index.html#confval-fieldwizard
     * @param string|null       $l10nDisplay        https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/Columns/Index.html#confval-columns-l10n-display
     * @param string|null       $l10nMode           https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/Columns/Index.html#confval-columns-l10n-mode
     * @param string            $label              https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/Columns/Index.html#confval-columns-label
     * @param bool|null         $nullable           https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Datetime/Index.html#confval-datetime-nullable
     * @param string|null       $onChange           https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/Columns/Index.html#confval-columns-onchange
     * @param string            $position
     * @param bool|null         $readOnly           https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Input/Index.html#confval-input-readonly
     * @param bool|null         $required           https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Input/Index.html#confval-input-required
     * @param string            $typeList           Comma separated list of record types (values of the TCA
     *                                              type field) for which the field is added to showitem.
     */
    public function __construct(
        protected bool              $addDatabaseKey = false,
        protected ?bool             $allowLanguageSynchronization = null,
        protected ?string           $databaseDefinition = null,
        protected mixed             $default = null,
        protected ?string           $description = null,
        protected string|array|null $displayCond = null,
        protected ?bool             $exclude = null,
        protected ?array            $fieldControl = null,
        protected ?array            $fieldInformation = null,
        protected ?array            $fieldWizard = null,
        protected ?string           $l10nDisplay = null,
        protected ?string           $l10nMode = null,
        protected string            $label = '',
        protected ?bool             $nullable = null,
        protected ?string           $onChange = null,
        /**
         * Usage: 'key:propertyName'
         * You can use the keys 'after', 'before', 'palette', 'replace' and 'tab'.
         * If the referenced field belongs to a palette, there are also the options 'newLineAfter' and 'newLineBefore',
         * which will create a line break between this field and the referenced one.
         */
        protected string            $position = '',
        protected ?bool             $readOnly = null,
        protected ?bool             $required = null,
        protected string            $typeList = '',
    ) {
        parent::__construct();
    }

    public function getAllowLanguageSynchronization(): ?bool
    {
        return $this->allowLanguageSynchronization;
    }

    public function getConfiguration(): ColumnTypeInterface
    {
        return $this->configuration;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getDisplayCond(): array|string|null
    {
        return $this->displayCond;
    }

    public function getFieldControl(): ?array
    {
        return $this->fieldControl;
    }

    public function getFieldInformation(): ?array
    {
        return $this->fieldInformation;
    }

    public function getFieldWizard(): ?array
    {
        return $this->fieldWizard;
    }

    public function getL10nDisplay(): ?string
    {
        return $this->l10nDisplay;
    }

    public function getL10nMode(): ?string
    {
        return $this->l10nMode;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getOnChange(): ?string
    {
        return $this->onChange;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function getPosition(): string
    {
        if (empty($this->position)) {
            return '';
        }

        [
            $key,
            $location,
        ] = GeneralUtility::trimExplode(':', $this->position, false, 2);

        // Check if $location is NOT a palette name.
        if (!str_contains($location, '-')) {
            $location = $this->tcaService->convertPropertyNameToColumnName($location);
        }

        return $key . ':' . $location;
    }

    public function getTypeList(): string
    {
        return $this->typeList;
    }

    public function isExclude(): ?bool
    {
        return $this->exclude;
    }

    public function isNullable(): ?bool
    {
        return $this->nullable;
    }

    public function isReadOnly(): ?bool
    {
        return $this->readOnly;
    }

    public function isRequired(): ?bool
    {
        return $this->required;
    }

    public function setConfiguration(ColumnTypeInterface $configuration): void
    {
        $this->configuration = $configuration;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    /**
     * @throws ReflectionException
     */
    public function toArray(): array
    {
        $properties    = parent::toArray();
        $configuration = [];

        foreach (self::COLUMN_FIELDS as $key) {
            if (!empty($properties[$key])) {
                $configuration[TcaUtility::convertKey($key)] = $properties[$key];
            }
        }

        $config = $this->getConfiguration()
            ->toArray();
        $databaseDefinition = $this->databaseDefinition ?? $this->getConfiguration()
            ->getDatabaseDefinition();

        foreach ($config as $key => $value) {
            $configuration['config'][TcaUtility::convertKey($key)] = $value;
        }

        if (null !== $this->allowLanguageSynchronization) {
            $configuration['config']['behaviour']['allowLanguageSynchronization'] = $this->allowLanguageSynchronization;
        }

        if (null !== $this->default) {
            $configuration['config']['default'] = $this->default;
        }

        if (null !== $this->fieldControl) {
            $configuration['config']['fieldControl'] = $this->fieldControl;
        }

        if (null !== $this->fieldInformation) {
            $configuration['config']['fieldInformation'] = $this->fieldInformation;
        }

        if (null !== $this->fieldWizard) {
            $configuration['config']['fieldWizard'] = $this->fieldWizard;
        }

        if (null !== $this->nullable) {
            $configuration['config']['nullable'] = $this->nullable;
        }

        if (!empty($databaseDefinition)) {
            if (!$this->nullable && !str_ends_with($databaseDefinition, ' NULL')) {
                $databaseDefinition .= ' NOT NULL';
            }

            $configuration['config']['EXT']['foundation'][self::CONFIGURATION_IDENTIFIERS['DATABASE_DEFINITION']] = $databaseDefinition;
        }

        if ($this->addDatabaseKey) {
            $configuration['config']['EXT']['foundation'][self::CONFIGURATION_IDENTIFIERS['DATABASE_KEY']] = true;
        }

        if (null !== $this->readOnly) {
            $configuration['config']['readOnly'] = $this->readOnly;
        }

        if (null !== $this->required) {
            $configuration['config']['required'] = $this->required;
        }

        return $configuration;
    }
}
