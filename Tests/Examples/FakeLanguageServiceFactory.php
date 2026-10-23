<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Tests\Examples;

use PSBits\Foundation\Utility\Typo3VersionUtility;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Class FakeLanguageServiceFactory
 *
 * Test double for the core language service factory, which requires a dependency tree that is not available in unit
 * tests. It is registered as singleton under the core class name.
 *
 * The core factory is a readonly class on v14 but a plain class on v13, and a child must carry the same readonly
 * status as its parent. The class is therefore declared conditionally so that it matches the running core on every
 * supported major.
 *
 * @package PSBits\Foundation\Tests\Examples
 */
if (Typo3VersionUtility::isAtLeast('14.0')) {
    readonly class FakeLanguageServiceFactory extends LanguageServiceFactory implements SingletonInterface
    {
        public function __construct(
            private LanguageService $languageService
        ) {
        }

        public function create(Locale|string $locale): LanguageService
        {
            return $this->languageService;
        }

        public function createFromUserPreferences(?AbstractUserAuthentication $user): LanguageService
        {
            return $this->languageService;
        }
    }
} else {
    class FakeLanguageServiceFactory extends LanguageServiceFactory implements SingletonInterface
    {
        public function __construct(
            private LanguageService $languageService
        ) {
        }

        public function create(Locale|string $locale): LanguageService
        {
            return $this->languageService;
        }

        public function createFromUserPreferences(?AbstractUserAuthentication $user): LanguageService
        {
            return $this->languageService;
        }
    }
}
