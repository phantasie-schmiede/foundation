<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Traits;

use InvalidArgumentException;
use PSBits\Foundation\Utility\StringUtility;
use ReflectionClass;
use ReflectionMethod;
use TYPO3\CMS\Core\Utility\GeneralUtility;

trait AutoFillPropertiesTrait
{
    /**
     * @param array<string, mixed> $properties
     * @throws InvalidArgumentException
     */
    public function fillProperties(array $properties): void
    {
        $reflectionClass = new ReflectionClass($this);

        foreach ($properties as $property => $value) {
            $property         = StringUtility::sanitizePropertyName($property);
            $setterMethodName = 'set' . ucfirst($property);

            if ($reflectionClass->hasMethod($setterMethodName)) {
                $reflectionMethod = GeneralUtility::makeInstance(ReflectionMethod::class, $this, $setterMethodName);
                $reflectionMethod->invoke($this, $value);
            }
        }
    }
}
