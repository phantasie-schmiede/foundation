<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Service;

use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Routing\RouterInterface;
use TYPO3\CMS\Core\Site\SiteFinder;

/**
 * Class FrontendUrlService
 *
 * @package PSBits\Foundation\Service
 */
readonly class FrontendUrlService
{
    public function __construct(
        private SiteFinder $siteFinder,
    ) {
    }

    /**
     * @throws SiteNotFoundException
     */
    public function buildPageUrl(
        int    $pageId,
        array  $parameters = [],
        string $fragment = '',
        ?int   $languageId = null,
        bool   $absolute = false,
    ): ?string {
        $site = $this->siteFinder->getSiteByPageId($pageId);

        $router = $site->getRouter();

        if (null !== $languageId) {
            $parameters['_language'] = $site->getLanguageById($languageId);
        }

        $uriType = $absolute ? RouterInterface::ABSOLUTE_URL : RouterInterface::ABSOLUTE_PATH;

        $uri = $router->generateUri(
            $pageId,
            $parameters,
            $fragment,
            $uriType
        );

        return (string)$uri;
    }
}
