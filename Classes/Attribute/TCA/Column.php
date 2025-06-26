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
     * @param string|null       $databaseDefinition Use this property to override the automatically generated
     *                                              definition.
     * @param mixed             $default            https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/CommonProperties/Default.html
     * @param string|null       $description        https://docs.typo3.org/m/typo3/reference-tca/main/en-us/Columns/Properties/Description.html#example
     * @param string|array|null $displayCond        https://docs.typo3.org/m/typo3/reference-tca/main/en-us/Columns/Properties/DisplayCond.html
     * @param bool|null         $exclude            https://docs.typo3.org/m/typo3/reference-tca/main/en-us/Columns/Properties/Exclude.html
     * @param string|null       $l10nDisplay        https://docs.typo3.org/m/typo3/reference-tca/main/en-us/Columns/Properties/L10nDisplay.html
     * @param string|null       $l10nMode           https://docs.typo3.org/m/typo3/reference-tca/main/en-us/Columns/Properties/L10nMode.html
     * @param string            $label              https://docs.typo3.org/m/typo3/reference-tca/main/en-us/Columns/Properties/Label.html
     * @param bool|null         $nullable           https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Datetime/Properties/Nullable.html
     * @param string|null       $onChange           https://docs.typo3.org/m/typo3/reference-tca/main/en-us/Columns/Properties/OnChange.html
     * @param string            $position
     * @param bool|null         $readOnly           https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/CommonProperties/ReadOnly.html
     * @param bool|null         $required           https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/CommonProperties/Required.html
     * @param string            $typeList
     */
    public function __construct(
        protected bool              $addDatabaseKey = false,
        protected ?string           $databaseDefinition = null,
        protected mixed             $default = null,
        protected ?string           $description = null,
        protected string|array|null $displayCond = null,
        protected ?bool             $exclude = null,
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
        $properties = parent::toArray();
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

        if (null !== $this->default) {
            $configuration['config']['default'] = $this->default;
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
