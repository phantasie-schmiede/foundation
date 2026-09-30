<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Tests\Functional;

use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PSBits\Foundation\Service\FrontendUrlService;
use PSBits\Foundation\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Class FrontendUrlServiceTest
 *
 * @package PSBits\Foundation\Tests\Functional
 */
class FrontendUrlServiceTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    private const int ROOT_PAGE_ID   = 1;
    private const int TARGET_PAGE_ID = 2;

    protected array $testExtensionsToLoad = [
        'typo3conf/ext/psbits/foundation',
    ];

    #[DataProvider('buildPageUrlDataProvider')]
    #[Test]
    public function buildPageUrlHandlesAllScenarios(
        int $pageId,
        array $parameters,
        string $fragment,
        ?int $languageId,
        bool $absolute,
        ?string $expectedStartsWith,
        ?string $expectedEndsWith,
        array $expectedContains,
        ?string $expectedException
    ): void {
        $service = GeneralUtility::makeInstance(FrontendUrlService::class);

        if (null !== $expectedException) {
            $this->expectException($expectedException);
            $service->buildPageUrl($pageId, $parameters, $fragment, $languageId, $absolute);

            return;
        }

        $url = $service->buildPageUrl($pageId, $parameters, $fragment, $languageId, $absolute);

        self::assertIsString($url);

        if (null !== $expectedStartsWith) {
            self::assertStringStartsWith($expectedStartsWith, $url);
        }

        foreach ($expectedContains as $expectedPart) {
            self::assertStringContainsString($expectedPart, $url);
        }

        if (null !== $expectedEndsWith) {
            self::assertStringEndsWith($expectedEndsWith, $url);
        }
    }

    public static function buildPageUrlDataProvider(): Generator
    {
        yield 'absolute url with query and fragment' => [
            self::TARGET_PAGE_ID,
            ['foo' => 'bar'],
            'details',
            null,
            true,
            'https://example.org/target-page',
            '#details',
            ['foo=bar'],
            null,
        ];

        yield 'absolute path with query and fragment' => [
            self::TARGET_PAGE_ID,
            ['foo' => 'bar'],
            'details',
            null,
            false,
            '/target-page',
            '#details',
            ['foo=bar'],
            null,
        ];

        yield 'absolute path without query and fragment' => [
            self::TARGET_PAGE_ID,
            [],
            '',
            null,
            false,
            '/target-page',
            null,
            [],
            null,
        ];

        yield 'absolute url with explicit default language id' => [
            self::TARGET_PAGE_ID,
            [],
            '',
            0,
            true,
            'https://example.org/target-page',
            null,
            [],
            null,
        ];

        yield 'throws site not found exception for missing page' => [
            999999,
            [],
            '',
            null,
            true,
            null,
            null,
            [],
            SiteNotFoundException::class,
        ];

        yield 'throws invalid argument exception for missing language' => [
            self::TARGET_PAGE_ID,
            [],
            '',
            999,
            true,
            null,
            null,
            [],
            InvalidArgumentException::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');
        $this->writeSiteConfiguration(
            'frontend_url_service_test',
            $this->buildSiteConfiguration(self::ROOT_PAGE_ID, 'https://example.org/')
        );
    }
}
