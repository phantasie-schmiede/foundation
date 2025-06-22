<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Utility;

use RuntimeException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function array_key_exists;
use function is_array;
use function is_object;

/**
 * Class VariableUtility
 *
 * @package PSBits\Foundation\Utility
 */
class VariableUtility
{
    /**
     * @param object|array $variable
     * @param string       $path
     * @param bool         $strict If set to false this function will return null if path does not exist.
     * @param string       $delimiter
     *
     * @return mixed
     */
    public static function getValueByPath(
        object|array $variable,
        string       $path,
        bool         $strict = true,
        string       $delimiter = '.',
    ): mixed {
        $pathSegments = GeneralUtility::trimExplode($delimiter, $path);
        $value = $variable;

        foreach ($pathSegments as $pathSegment) {
            if (is_array($value) && array_key_exists($pathSegment, $value)) {
                $value = $value[$pathSegment];
            } elseif (is_object($value)) {
                $getterMethod = 'get' . ucfirst($pathSegment);
                $value = $value->$getterMethod();
            } else {
                if (false === $strict) {
                    return null;
                }

                throw new RuntimeException(
                    __CLASS__ . ': Path "' . $path . '" does not exist in array or object!', 1614066725
                );
            }
        }

        return $value;
    }
}
