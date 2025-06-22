<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Service\Configuration\CacheConfigurationBuilder;

/**
 * Interface BuilderInterface
 *
 * @package PSBits\Foundation\Service\Configuration\CacheConfigurationBuilder
 */
interface BuilderInterface
{
    /**
     * This method must return an array whose keys are the page IDs and the values are arrays of the SysFolder IDs
     * defined as the record sources for this page.
     *
     * @return array<int, array<int>>
     */
    public function collectSourceRelations(): array;

    public function getPidCacheTagPrefix(): string;

    public function getTable(): string;
}
