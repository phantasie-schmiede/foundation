<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Attribute\TCA\ColumnType;

use PSBits\Foundation\Attribute\AbstractAttribute;
use ReflectionException;

/**
 * Class AbstractColumnType
 *
 * @package PSBits\Foundation\Attribute\TCA\ColumnType
 */
abstract class AbstractColumnType extends AbstractAttribute implements ColumnTypeInterface
{
    /**
     * Returns the short class name (lower case) for ['config']['type'].
     */
    public function getType(): string
    {
        $classNameParts = explode('\\', static::class);

        return strtolower(array_pop($classNameParts));
    }

    /**
     * @throws ReflectionException
     */
    public function toArray(): array
    {
        $configuration = parent::toArray();
        $configuration['type'] = $this->getType();

        return $configuration;
    }
}
