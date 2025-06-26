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

use TYPO3\CMS\Core\Localization\LanguageServiceFactory as Typo3LanguageServiceFactory;
use TYPO3\CMS\Core\Localization\Locale;

/**
 * Class LanguageServiceFactory
 *
 * Overwrites the original class to load custom LanguageService.
 *
 * @package PSBits\Foundation\Service\Typo3
 * @TODO    Check original file on TYPO3 update!
 */
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
