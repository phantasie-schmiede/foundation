<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with the source code.
 */

namespace PSBits\Foundation\Service\Typo3;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PSBits\Foundation\Utility\LocalizationUtility;
use ReflectionMethod;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class LanguageServiceTest
 *
 * @package PSBits\Foundation\Service\Typo3
 */
class LanguageServiceTest extends UnitTestCase
{
    private const array LOCAL_LANGUAGE = [
        'default' => [
            'plain.label'  => 'A plain label',
            'plural.label' => [
                [
                    'target' => 'No items',
                ],
                [
                    'target' => 'One item',
                ],
            ],
        ],
    ];

    public static function labelDataProvider(): Generator
    {
        yield 'plain label' => [
            'plain.label',
            'A plain label',
        ];
        yield 'plural form 0' => [
            'plural.label' . LocalizationUtility::PLURAL_FORM_MARKERS['BEGIN'] . '0' . LocalizationUtility::PLURAL_FORM_MARKERS['END'],
            'No items',
        ];
        yield 'plural form 1' => [
            'plural.label' . LocalizationUtility::PLURAL_FORM_MARKERS['BEGIN'] . '1' . LocalizationUtility::PLURAL_FORM_MARKERS['END'],
            'One item',
        ];
        yield 'plural form out of range falls back to 0' => [
            'plural.label' . LocalizationUtility::PLURAL_FORM_MARKERS['BEGIN'] . '7' . LocalizationUtility::PLURAL_FORM_MARKERS['END'],
            'No items',
        ];
        yield 'unknown label is an empty string' => [
            'does.not.exist',
            '',
        ];
    }

    #[Test]
    public function extensionSupportsTheRunningCoreVersion(): void
    {
        /*
         * getNumericTypo3Version() returns a dotted string such as "13.4.9"; the
         * first two digits are the major version.
         */
        self::assertGreaterThanOrEqual(
            12,
            (int)substr(VersionNumberUtility::getNumericTypo3Version(), 0, 2),
            'This test matrix no longer covers the minimum supported TYPO3 version.'
        );
    }

    #[Test]
    #[DataProvider('labelDataProvider')]
    public function getLLLResolvesLabelsAndPluralForms(string $index, string $expectedResult): void
    {
        self::assertSame($expectedResult, $this->invokeGetLLL($index, self::LOCAL_LANGUAGE));
    }

    private function createSubject(): LanguageService
    {
        /*
         * getLLL() itself only reads $lang and the passed array, so the three
         * constructor dependencies are never touched - mocks keep this a unit
         * test. The signature is identical across the supported majors.
         */
        return new LanguageService(
            $this->createStub(Locales::class),
            $this->createStub(LocalizationFactory::class),
            $this->createStub(FrontendInterface::class)
        );
    }

    private function invokeGetLLL(string $index, array $localLanguage): string
    {
        $method = new ReflectionMethod(LanguageService::class, 'getLLL');

        return $method->invoke($this->createSubject(), $index, $localLanguage);
    }
}
