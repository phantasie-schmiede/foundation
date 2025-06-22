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
 * Enum SelectRenderType
 *
 * @package PSBits\Foundation\Enum
 */
enum SelectRenderType: string
{
    case selectCheckBox           = 'selectCheckBox';
    case selectMultipleSideBySide = 'selectMultipleSideBySide';
    case selectSingle             = 'selectSingle';
    case selectSingleBox          = 'selectSingleBox';
    case selectTree               = 'selectTree';
}
