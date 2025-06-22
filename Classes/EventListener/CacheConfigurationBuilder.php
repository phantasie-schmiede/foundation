<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\EventListener;

use Doctrine\DBAL\Exception as DbalException;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use PSBits\Foundation\Service\Configuration\PageCacheService;
use PSBits\Foundation\Utility\FileUtility;
use TYPO3\CMS\Core\Cache\Event\CacheFlushEvent;
use TYPO3\CMS\Core\Core\Environment;

/**
 * Class CacheConfigurationBuilder
 *
 * @package PSBits\Foundation\EventListener
 */
final readonly class CacheConfigurationBuilder
{
    public const array FILE_PATHS = [
        'TSCONFIG'   => '/cache/foundation/TSconfig/cacheConfiguration.tsconfig',
        'TYPOSCRIPT' => '/cache/foundation/TypoScript/cacheConfiguration.typoscript',
    ];

    public function __construct(
        protected PageCacheService $pageCacheService,
    ) {
    }

    /**
     * @throws DbalException
     * @throws Exception
     */
    #[NoReturn]
    public function __invoke(CacheFlushEvent $event): void
    {
        $basePath = Environment::getVarPath();
        FileUtility::write(
            $basePath . self::FILE_PATHS['TSCONFIG'],
            $this->pageCacheService->buildTSconfig(),
        );
        FileUtility::write(
            $basePath . self::FILE_PATHS['TYPOSCRIPT'],
            $this->pageCacheService->buildTypoScript(),
        );
    }
}
