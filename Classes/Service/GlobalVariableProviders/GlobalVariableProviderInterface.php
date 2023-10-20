<?php
declare(strict_types=1);

/*
 * This file is part of PSB Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbFoundation\Service\GlobalVariableProviders;

/**
 * Interface GlobalVariableProviderInterface
 *
 * @package PSB\PsbFoundation\Service\GlobalVariableProviders
 */
interface GlobalVariableProviderInterface
{
    /**
     * @return mixed
     */
    public function getGlobalVariables(): mixed;

    /**
     * When returned data may change during the request, set function's return value to false. This function is
     * called after getGlobalVariables().
     *
     * @return bool
     */
    public function isCacheable(): bool;
}
