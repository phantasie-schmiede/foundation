<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Enum;

/**
 * Enum CheckboxRenderType
 *
 * @package PSBits\Foundation\Enum
 */
enum CheckboxRenderType: string
{
    case checkboxLabeledToggle = 'checkboxLabeledToggle';
    case checkboxToggle        = 'checkboxToggle';
    case default               = '';
}
