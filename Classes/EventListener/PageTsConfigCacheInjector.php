<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Event\BeforeLoadedPageTsConfigEvent;

/**
 * Class PageTsConfigCacheInjector
 *
 * Injects the generated cache configuration TSconfig into the global page TSconfig.
 *
 * @package PSBits\Foundation\EventListener
 */
#[AsEventListener(identifier: 'foundation_pageTsConfigCacheInjector')]
final readonly class PageTsConfigCacheInjector
{
    public function __invoke(BeforeLoadedPageTsConfigEvent $event): void
    {
        $filePath = Environment::getVarPath() . CacheConfigurationBuilder::FILE_PATHS['TSCONFIG'];

        if (!file_exists($filePath)) {
            return;
        }

        $fileContents = (string)file_get_contents($filePath);

        if (!empty(trim($fileContents))) {
            $event->addTsConfig($fileContents);
        }
    }
}
