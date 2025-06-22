<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\EventListener;

use PSBits\Foundation\Attribute\TCA\Column;
use PSBits\Foundation\Utility\StringUtility;
use TYPO3\CMS\Core\Database\Event\AlterTableDefinitionStatementsEvent;
use function in_array;

/**
 * Class DatabaseEnricher
 *
 * @package PSBits\Foundation\EventListener
 */
class DatabaseEnricher
{
    protected const CREATE_TABLE_PHRASE = 'CREATE TABLE';
    protected const INDENTATION         = '    ';
    protected const KEY_DEFINITION      = 'KEY ';
    protected const SKIP_KEYWORDS       = [
        'PRIMARY',
        'UNIQUE',
    ];

    protected array $originalFields = [];

    public function __invoke(AlterTableDefinitionStatementsEvent $event): void
    {
        $additionalFields = [];
        $additionalKeys = [];
        $sql = implode(LF, $event->getSqlData());
        $this->originalFields = $this->generateDataFromRawSql($sql);

        foreach ($GLOBALS['TCA'] as $tableName => $tableConfiguration) {
            foreach ($tableConfiguration['columns'] as $columnName => $columnConfiguration) {
                if (!empty($columnConfiguration['config']['EXT']['foundation'][Column::CONFIGURATION_IDENTIFIERS['DATABASE_DEFINITION']]) && !$this->sqlHasFieldDefinition(
                        $columnName,
                        $tableName
                    )) {
                    $additionalFields[$tableName][$columnName] = $columnConfiguration['config']['EXT']['foundation'][Column::CONFIGURATION_IDENTIFIERS['DATABASE_DEFINITION']];
                }

                if (!empty($columnConfiguration['config']['EXT']['foundation'][Column::CONFIGURATION_IDENTIFIERS['DATABASE_KEY']]) && !$this->sqlHasKeyDefinition(
                        $columnName,
                        $tableName
                    )) {
                    $additionalKeys[$tableName][] = $columnName;
                }
            }
        }

        // Create SQL for field definitions.
        foreach ($additionalFields as $tableName => $fields) {
            $createStatement = self::CREATE_TABLE_PHRASE . ' ' . $tableName . ' (';

            foreach ($fields as $fieldName => $databaseDefinition) {
                $createStatement .= LF . self::INDENTATION . $fieldName . ' ' . $databaseDefinition . ',';
            }

            $createStatement = rtrim($createStatement, ',') . LF . ');';
            $event->addSqlData($createStatement);
        }

        // Create SQL for index key definitions.
        foreach ($additionalKeys as $tableName => $keyNames) {
            $createStatement = self::CREATE_TABLE_PHRASE . ' ' . $tableName . ' (';

            foreach ($keyNames as $keyName) {
                $createStatement .= LF . self::INDENTATION . self::KEY_DEFINITION . $keyName . ' (' . $keyName . '),';
            }

            $createStatement = rtrim($createStatement, ',') . LF . ');';
            $event->addSqlData($createStatement);
        }
    }

    private function generateDataFromRawSql(string $sql): array
    {
        $data = [];

        foreach (StringUtility::explodeByLineBreaks($sql) as $line) {
            $line = trim($line);

            // Skip empty lines and comments.
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            // Skip certain lines like defintion of keys.
            foreach (self::SKIP_KEYWORDS as $skipKeyword) {
                if (str_starts_with($line, $skipKeyword . ' ')) {
                    continue 2;
                }
            }

            // Detect end of table definition.
            if (str_contains($line, ';')) {
                $tableName = null;
                continue;
            }

            if (str_starts_with($line, self::CREATE_TABLE_PHRASE)) {
                // Remove 'CREATE TABLE'.
                $line = trim(substr($line, strlen(self::CREATE_TABLE_PHRASE)));
                $tableName = StringUtility::getFirstWord($line);
                continue;
            }

            if (!empty($tableName)) {
                if (str_starts_with($line, self::KEY_DEFINITION)) {
                    $keyName = StringUtility::getFirstWord(substr($line, strlen(self::KEY_DEFINITION)));
                    $data[$tableName]['keys'][] = $keyName;
                    continue;
                }

                /*
                 * Should be a field definition at this point!
                 * Remove backticks.
                 */
                $line = trim($line, '`');
                $fieldName = StringUtility::getFirstWord($line);
                $data[$tableName]['fields'][] = $fieldName;
            }
        }

        return $data;
    }

    private function sqlHasFieldDefinition(string $fieldName, string $tableName): bool
    {
        return in_array($fieldName, $this->originalFields[$tableName]['fields'] ?? [], true);
    }

    private function sqlHasKeyDefinition(string $keyName, string $tableName): bool
    {
        return in_array($keyName, $this->originalFields[$tableName]['keys'] ?? [], true);
    }
}
