<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Tests\Unit\Service\Typo3;

use PHPUnit\Framework\Attributes\Test;
use PSBits\Foundation\Service\Typo3\LanguageService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class LanguageServiceFactoryTest
 *
 * @package PSBits\Foundation\Tests\Unit\Service\Typo3
 */
class LanguageServiceFactoryTest extends UnitTestCase
{
    private const CORE_CLASS = 'TYPO3\\CMS\\Core\\Localization\\LanguageServiceFactory';

    /**
     * The class names are plain strings on purpose. Referring to them with the ::class constant
     * would let the analyser and the autoloader resolve them at analysis time, so the test must
     * never name them as real symbols.
     */
    private const FACTORY_CLASS = 'PSBits\\Foundation\\Service\\Typo3\\LanguageServiceFactory';

    #[Test]
    public function theFactoryExtendsTheCoreFactoryAndReturnsTheCustomLanguageService(): void
    {
        self::assertTrue(
            is_subclass_of(self::FACTORY_CLASS, self::CORE_CLASS),
            self::FACTORY_CLASS . ' must extend the core factory it replaces.'
        );

        self::assertSame(
            LanguageService::class,
            (string)(new \ReflectionMethod(self::FACTORY_CLASS, 'create'))->getReturnType(),
            'create() must return the custom LanguageService, that is the whole point of the override.'
        );
    }
}
