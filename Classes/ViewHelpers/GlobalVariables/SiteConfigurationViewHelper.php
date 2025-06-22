<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\ViewHelpers\GlobalVariables;

use Closure;
use Exception;
use PSBits\Foundation\Service\GlobalVariableProviders\SiteConfigurationProvider;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Class SiteConfigurationViewHelper
 *
 * @package PSBits\Foundation\ViewHelpers\GlobalVariables
 */
class SiteConfigurationViewHelper extends AbstractGlobalVariablesViewHelper
{
    /**
     * @throws Exception
     */
    public static function renderStatic(
        array                     $arguments,
        Closure                   $renderChildrenClosure,
        RenderingContextInterface $renderingContext,
    ): mixed {
        return parent::getVariable(SiteConfigurationProvider::class, $arguments);
    }
}
