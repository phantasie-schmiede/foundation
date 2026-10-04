<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace PSBits\Foundation\Service\Typo3;

use PSBits\Foundation\Utility\Typo3VersionUtility;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory as Typo3LanguageServiceFactory;
use TYPO3\CMS\Core\Localization\Locale;

/**
 * Class LanguageServiceFactory
 *
 * Overwrites the core factory so that it produces our {@see LanguageService}, which respects
 * plural forms and logs label access.
 *
 * The core factory is a `readonly class` on v14 but a plain class on v13, and PHP requires a child
 * to carry the same readonly status as its parent. The class is therefore declared conditionally
 * so that it matches the running core on every supported major.
 *
 * @package PSBits\Foundation\Service\Typo3
 */
if (Typo3VersionUtility::isAtLeast('14.0')) {
    readonly class LanguageServiceFactory extends Typo3LanguageServiceFactory
    {
        /**
         * Factory method to create a language service object.
         *
         * @param Locale|string $locale the locale
         */
        public function create(Locale|string $locale): LanguageService
        {
            $obj = new LanguageService($this->locales, $this->localizationFactory, $this->runtimeCache);
            $obj->init($locale instanceof Locale ? $locale : $this->locales->createLocale($locale));

            return $obj;
        }
    }
} else {
    class LanguageServiceFactory extends Typo3LanguageServiceFactory
    {
        /**
         * Factory method to create a language service object.
         *
         * @param Locale|string $locale the locale
         */
        public function create(Locale|string $locale): LanguageService
        {
            $obj = new LanguageService($this->locales, $this->localizationFactory, $this->runtimeCache);
            $obj->init($locale instanceof Locale ? $locale : $this->locales->createLocale($locale));

            return $obj;
        }
    }
}
