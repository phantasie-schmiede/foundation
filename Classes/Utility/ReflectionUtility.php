<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Utility;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use Reflector;
use function count;
use function is_string;

/**
 * Class ReflectionUtility
 *
 * @package PSBits\Foundation\Utility
 */
class ReflectionUtility
{
    /**
     * @param string           $attributeClass
     * @param Reflector|string $reflection Can be a reflection or a full qualified class name.
     *
     * @return object|null
     * @throws ReflectionException
     */
    public static function getAttributeInstance(
        string           $attributeClass,
        Reflector|string $reflection,
    ): ?object {
        if (is_string($reflection)) {
            $reflection = new ReflectionClass($reflection);
        }

        $attributes = $reflection->getAttributes($attributeClass, ReflectionAttribute::IS_INSTANCEOF);

        return 0 < count($attributes) ? $attributes[0]->newInstance() : null;
    }
}
