<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Traits;

use PSBits\Foundation\Utility\ObjectUtility;
use ReflectionException;

/**
 * Trait ObjectToArrayTrait
 *
 * @package PSBits\Foundation\Traits
 */
trait ObjectToArrayTrait
{
    /**
     * @throws ReflectionException
     */
    public function toArray(): array
    {
        return ObjectUtility::toArray($this);
    }
}
