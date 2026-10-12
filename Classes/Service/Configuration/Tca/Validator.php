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
    }
}
