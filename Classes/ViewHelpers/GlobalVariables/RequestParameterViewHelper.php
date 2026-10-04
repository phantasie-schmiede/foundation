<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\ViewHelpers\GlobalVariables;

use PSBits\Foundation\Service\GlobalVariableProviders\RequestParameterProvider;

/**
 * Class RequestParameterViewHelper
 *
 * @package PSBits\Foundation\ViewHelpers\GlobalVariables
 */
class RequestParameterViewHelper extends AbstractGlobalVariablesViewHelper
{
    protected function getBaseKey(): string
    {
        return RequestParameterProvider::class;
    }
}
