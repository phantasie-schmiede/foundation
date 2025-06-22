<?php
declare(strict_types=1);

/*
 * This file is part of PSB Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbFoundation\Traits;

use TYPO3\CMS\Core\Cache\CacheDataCollector;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\CacheTag;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Trait ControllerCacheTrait
 *
 * @package PSB\PsbFoundation\Traits
 */
trait ControllerCacheTrait
{
    /**
     * @throws NoSuchCacheGroupException
     */
    private function deletePagesCache(string ...$cacheTags): void
    {
        /** @var CacheManager $cacheManager */
        $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        $cacheManager->flushCachesInGroupByTags('pages', $cacheTags);
    }

    private function setCacheTags(string ...$cacheTags): void
    {
        /** @var CacheDataCollector $cacheDataCollector */
        $cacheDataCollector = $GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.cache.collector');
        $cacheTagInstances = array_map(static fn(string $cacheTag) => new CacheTag($cacheTag), $cacheTags);
        $cacheDataCollector->addCacheTags(...$cacheTagInstances);
    }
}
