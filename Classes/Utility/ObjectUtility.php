<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Utility;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Class ObjectUtility
 *
 * @package PSBits\Foundation\Utility
 */
class ObjectUtility
{
    public const NAMESPACE_FALLBACK_KEY = '__fallback';

    public static function getFullQualifiedClassName(string $className, array $namespaces): bool|string
    {
        if (class_exists($className)) {
            return $className;
        }

        [
            $alias,
            $appendix,
        ] = GeneralUtility::trimExplode('\\', $className, true, 2);

        if (isset($namespaces[$alias])) {
            return $namespaces[$alias] . ($appendix ? ('\\' . $appendix) : '');
        }

        if (isset($namespaces[self::NAMESPACE_FALLBACK_KEY])) {
            $fallbackClassName = $namespaces[self::NAMESPACE_FALLBACK_KEY] . '\\' . $className;

            if (class_exists($fallbackClassName)) {
                return $fallbackClassName;
            }
        }

        return false;
    }

    public static function getRepositoryClassName(AbstractEntity|string $modelInstanceOrModelClassName): string
    {
        if ($modelInstanceOrModelClassName instanceof AbstractEntity) {
            $modelClassName = $modelInstanceOrModelClassName::class;
        } else {
            $modelClassName = $modelInstanceOrModelClassName;
        }

        return str_replace('\Model\\', '\Repository\\', $modelClassName) . 'Repository';
    }

    /**
     * @throws ReflectionException
     */
    public static function toArray(object $object): array
    {
        $arrayRepresentation = [];
        $reflectionClass = new ReflectionClass($object);
        $properties = $reflectionClass->getProperties();

        foreach ($properties as $property) {
            $getterMethodName = 'get' . GeneralUtility::underscoredToUpperCamelCase($property->getName());

            if (!$reflectionClass->hasMethod($getterMethodName)) {
                $getterMethodName = 'is' . GeneralUtility::underscoredToUpperCamelCase($property->getName());
            }

            if ($reflectionClass->hasMethod($getterMethodName)) {
                $reflectionMethod = GeneralUtility::makeInstance(ReflectionMethod::class, $object, $getterMethodName);
                $value = $reflectionMethod->invoke($object);

                if (null !== $value) {
                    $arrayRepresentation[$property->getName()] = $value;
                }
            }
        }

        return $arrayRepresentation;
    }
}
