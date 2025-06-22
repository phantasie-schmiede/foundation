<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Utility\Database;

/**
 * Class DefinitionUtility
 *
 * @package PSBits\Foundation\Utility\Database
 */
class DefinitionUtility
{
    public static function bigint(int $default = 0, bool $unsigned = false): string
    {
        return self::integerType('bigint', $default, $unsigned);
    }

    public static function char(int $length, string $default = ''): string
    {
        return 'char(' . $length . ') DEFAULT \'' . $default . '\'';
    }

    public static function decimal(int $precision, int $scale, float $default = 0): string
    {
        return 'decimal(' . $precision . ',' . $scale . ') DEFAULT ' . $default;
    }

    public static function double(float $default = 0): string
    {
        return 'double DEFAULT ' . $default;
    }

    public static function float(float $default = 0): string
    {
        return 'float DEFAULT ' . $default;
    }

    public static function int(int $default = 0, bool $unsigned = false): string
    {
        return self::integerType('int', $default, $unsigned);
    }

    public static function smallint(int $default = 0, bool $unsigned = false): string
    {
        return self::integerType('smallint', $default, $unsigned);
    }

    public static function text(): string
    {
        return 'text';
    }

    public static function tinyint(int $default = 0, bool $unsigned = false): string
    {
        return self::integerType('tinyint', $default, $unsigned);
    }

    public static function varchar(int $length, string $default = ''): string
    {
        return 'varchar(' . $length . ') DEFAULT \'' . $default . '\'';
    }

    private static function integerType(string $type, int $default = 0, bool $unsigned = false): string
    {
        return $type . ($unsigned ? ' unsigned' : '') . ' DEFAULT ' . $default;
    }
}
