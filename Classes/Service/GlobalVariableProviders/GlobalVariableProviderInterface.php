<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Service\GlobalVariableProviders;

/**
 * Interface GlobalVariableProviderInterface
 *
 * @package PSBits\Foundation\Service\GlobalVariableProviders
 */
interface GlobalVariableProviderInterface
{
    public function getGlobalVariables(): mixed;

    /**
     * When returned data may change during the request, set function's return value to false. This function is
     * called after getGlobalVariables().
     */
    public function isCacheable(): bool;
}
