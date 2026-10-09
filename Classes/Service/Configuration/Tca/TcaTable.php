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
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Class TcaTable
 *
 * Central access to the TCA configuration of a single table inside $GLOBALS['TCA']. All reads and writes of the
 * TCA of a table have to go through this class.
 *
 * @package PSBits\Foundation\Service\Configuration\Tca
 */
class TcaTable
{
    public const string UNSET_KEYWORD = 'UNSET';

    public function __construct(
        private readonly string $tableName,
    ) {
    }

    public function addColumnConfiguration(string $columnName, array $columnConfiguration): void
    {
        ExtensionManagementUtility::addTCAcolumns($this->tableName, [$columnName => $columnConfiguration]);
    }

    /**
     * Adds fields to the showitem of all types.
     */
    public function addFieldsToAllTypes(string $fields, string $typeList = '', string $position = ''): void
    {
        ExtensionManagementUtility::addToAllTCAtypes($this->tableName, $fields, $typeList, $position);
    }

    public function addFieldsToPalette(string $identifier, array $fieldNames, string $position = ''): void
    {
        [
            'position'   => $position,
            'fieldNames' => $fieldNames,
        ] = Position::applyLineBreaks($position, $fieldNames);

        ExtensionManagementUtility::addFieldsToPalette(
            $this->tableName,
            $identifier,
            implode(', ', $fieldNames),
            $position
        );
    }

    /**
     * Applies the given ctrl properties to the table. Properties with the value "UNSET" are removed.
     */
    public function applyCtrlProperties(array $ctrlProperties): void
    {
        foreach ($ctrlProperties as $property => $value) {
            if (self::UNSET_KEYWORD === $value) {
                unset($GLOBALS['TCA'][$this->tableName]['ctrl'][$property]);
            } else {
                $GLOBALS['TCA'][$this->tableName]['ctrl'][$property] = $value;
            }
        }
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
    public function createPalette(
        string $identifier,
        string $label = '',
        string $description = '',
        bool   $isHiddenPalette = false,
        string $defaultLabelPath = '',
    ): void {
        $paletteConfiguration = ['showitem' => ''];

        if (true === $isHiddenPalette) {
            $paletteConfiguration['isHiddenPalette'] = true;
        }

        if ('' !== $label) {
            $label = LabelResolver::resolveLabel(
                $label,
                $defaultLabelPath . 'palette.' . $identifier . '.label'
            );

            if ('' !== $label) {
                $paletteConfiguration['label'] = $label;
            }
        }

        if ('' !== $description) {
            $description = LabelResolver::resolveLabel(
                $description,
                $defaultLabelPath . 'palette.' . $identifier . '.description'
            );

            if ('' !== $description) {
                $paletteConfiguration['description'] = $description;
            }
        }

        $GLOBALS['TCA'][$this->tableName]['palettes'][$identifier] = $paletteConfiguration;
    }

    /**
     * Ensures that the given record type exists with an empty showitem.
     */
    public function ensureTypeConfiguration(string $recordType): void
    {
        if (!isset($GLOBALS['TCA'][$this->tableName]['types'][$recordType])) {
            $GLOBALS['TCA'][$this->tableName]['types'][$recordType] = ['showitem' => ''];
        }
    }

    public function getColumnConfiguration(string $columnName): array
    {
        return $GLOBALS['TCA'][$this->tableName]['columns'][$columnName] ?? throw new RuntimeException(
            __CLASS__ . ': "' . $columnName . '" is not defined for table "' . $this->tableName . '"!',
            1660914340
        );
    }

    public function getCtrlProperty(string $property): mixed
    {
        return $GLOBALS['TCA'][$this->tableName]['ctrl'][$property] ?? null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getPalettes(): array
    {
        return $GLOBALS['TCA'][$this->tableName]['palettes'] ?? [];
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getTypes(): array
    {
        return $GLOBALS['TCA'][$this->tableName]['types'] ?? [];
    }

    public function hasPalette(string $identifier): bool
    {
        return isset($GLOBALS['TCA'][$this->tableName]['palettes'][$identifier]);
    }

    /**
     * Sets the base configuration of the table. Existing configuration is overwritten.
     */
    public function setBaseConfiguration(array $configuration): void
    {
        $GLOBALS['TCA'][$this->tableName] = $configuration;
    }

    /**
     * Sets the configuration for a record type. In override mode, an existing configuration is extended recursively.
     */
    public function setTypeConfiguration(
        int|string $recordType,
        array      $typeConfigurationArray,
        bool       $overrideMode,
    ): void {
        if ($overrideMode && isset($GLOBALS['TCA'][$this->tableName]['types'][$recordType])) {
            $GLOBALS['TCA'][$this->tableName]['types'][$recordType] = array_replace_recursive(
                $GLOBALS['TCA'][$this->tableName]['types'][$recordType],
                $typeConfigurationArray
            );

            return;
        }

        $GLOBALS['TCA'][$this->tableName]['types'][$recordType] = $typeConfigurationArray;
    }
}
