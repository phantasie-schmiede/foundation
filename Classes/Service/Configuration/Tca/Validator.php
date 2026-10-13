<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Service\Configuration\Tca;

use PSBits\Foundation\Exceptions\MisconfiguredTcaException;

use function in_array;
use function is_string;

/**
 * Class Validator
 *
 * Validates the TCA configuration of a table.
 *
 * @package PSBits\Foundation\Service\Configuration\Tca
 */
class Validator
{
    private const array PROTECTED_COLUMNS = [
        'crdate',
        'pid',
        'tstamp',
        'uid',
    ];

    /**
     * The valid "relationship" values per column type, according to the TCA reference.
     *
     * "manyToMany" is only valid with an intermediate "MM" table, except for the
     * category type, where the core adds "sys_category_record_mm" automatically.
     */
    private const array RELATIONSHIP_RULES = [
        'category' => [
            'allowed'              => [
                'manyToMany',
                'oneToMany',
                'oneToOne',
            ],
            'manyToManyRequiresMm' => false,
        ],
        'file'     => [
            'allowed' => [
                'manyToOne',
                'oneToMany',
                'oneToOne',
            ],
        ],
        'folder'   => [
            'allowed' => [
                'manyToOne',
                'oneToMany',
                'oneToOne',
            ],
        ],
        'group'    => [
            'allowed'              => [
                'manyToMany',
                'manyToOne',
                'oneToMany',
                'oneToOne',
            ],
            'manyToManyRequiresMm' => true,
        ],
        'inline'   => [
            'allowed'              => [
                'manyToMany',
                'manyToOne',
                'oneToMany',
                'oneToOne',
            ],
            'manyToManyRequiresMm' => true,
        ],
        'select'   => [
            'allowed'                    => [
                'manyToMany',
                'manyToOne',
                'oneToMany',
                'oneToOne',
            ],
            'manyToManyRequiresMm'       => true,
            'selectRequiresForeignTable' => true,
        ],
    ];

    /**
     * @throws MisconfiguredTcaException
     */
    public function validate(string $tableName): void
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

        $this->validateRelationships($tableName, $configuration['columns'] ?? []);
    }

    /**
     * Validates the "relationship" option of all column types that support it.
     *
     * @param array<string, array> $columns
     *
     * @throws MisconfiguredTcaException
     */
    private function validateRelationships(string $tableName, array $columns): void
    {
        foreach ($columns as $columnName => $column) {
            $config = $column['config'] ?? [];
            $type   = $config['type'] ?? null;

            if (!is_string($type) || !isset(self::RELATIONSHIP_RULES[$type])) {
                continue;
            }

            $relationship = $config['relationship'] ?? null;

            if (!is_string($relationship) || '' === $relationship) {
                continue;
            }

            $rule = self::RELATIONSHIP_RULES[$type];

            if (!in_array($relationship, $rule['allowed'], true)) {
                throw new MisconfiguredTcaException(
                    $tableName . '.' . $columnName . ': The type "' . $type . '" does not support the relationship "' . $relationship . '". Allowed values are: "' . implode(
                        '", "',
                        $rule['allowed']
                    ) . '".',
                    1791849601
                );
            }

            if ('manyToMany' === $relationship && true === ($rule['manyToManyRequiresMm'] ?? false) && !isset($config['MM'])) {
                throw new MisconfiguredTcaException(
                    $tableName . '.' . $columnName . ': The relationship "manyToMany" requires an intermediate table. Please configure the "MM" option for this column.',
                    1791849602
                );
            }

            if (true === ($rule['selectRequiresForeignTable'] ?? false) && !isset($config['foreign_table'])) {
                throw new MisconfiguredTcaException(
                    $tableName . '.' . $columnName . ': The "relationship" option has no effect for a select column without a "foreign_table" option. Please remove the "relationship" option or configure a "foreign_table".',
                    1791849603
                );
            }
        }
    }
}
