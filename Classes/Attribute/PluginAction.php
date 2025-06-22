<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Attribute;

use Attribute;

/**
 * Class PluginAction
 *
 * Use this attribute for methods in a plugin controller.
 *
 * @package PSBits\Foundation\Attribute
 */
#[Attribute(Attribute::TARGET_METHOD)]
class PluginAction extends AbstractAttribute
{
    public function __construct(
        /** Marks the default action of the controller (executed, when no specific action is given in a request). */
        protected bool $default = false,
        /** Add this action to the list of uncached actions */
        protected bool $uncached = false,
    ) {
    }

    public function isDefault(): bool
    {
        return $this->default;
    }

    public function isUncached(): bool
    {
        return $this->uncached;
    }
}
