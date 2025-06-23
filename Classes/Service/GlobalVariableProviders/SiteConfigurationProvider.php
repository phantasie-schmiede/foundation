<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Service\GlobalVariableProviders;

use PSBits\Foundation\Utility\ValidationUtility;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;

/**
 * Class SiteConfigurationProvider
 *
 * @package PSBits\Foundation\Service\GlobalVariableProviders
 */
class SiteConfigurationProvider extends AbstractProvider
{
    public function __construct(
        protected readonly SiteFinder $siteFinder,
    ) {
    }

    /**
     * @throws SiteNotFoundException
     */
    public function getGlobalVariables(): Site
    {
        ValidationUtility::requiresFrontendContext();
        ValidationUtility::requiresTypoScriptLoaded();

        return $this->siteFinder->getSiteByPageId($GLOBALS['TSFE']->id);
    }
}
