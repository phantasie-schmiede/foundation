<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\ViewHelpers\GlobalVariables;

use Exception;
use PSBits\Foundation\Service\GlobalVariableService;
use PSBits\Foundation\ViewHelpers\GlobalVariablesViewHelper;

/**
 * Class AbstractGlobalVariablesViewHelper
 *
 * @package PSBits\Foundation\ViewHelpers\GlobalVariables
 */
abstract class AbstractGlobalVariablesViewHelper extends GlobalVariablesViewHelper
{
    /**
     * @throws Exception
     */
    protected static function getVariable(string $baseKey, array $arguments): mixed
    {
        if (!empty($arguments['path'])) {
            $baseKey .= '.' . $arguments['path'];
        }

        return GlobalVariableService::get($baseKey, $arguments['strict'], $arguments['fallback']);
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();

        // Override this argument to make it optional as the extending ViewHelpers define a base path.
        $this->overrideArgument('path', 'string', 'path segments must be separated by dots');
    }
}
