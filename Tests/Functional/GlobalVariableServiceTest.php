<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use PSBits\Foundation\Service\GlobalVariableProviders\EarlyAccessConstantsProvider;
use PSBits\Foundation\Service\GlobalVariableProviders\RequestParameterProvider;
use PSBits\Foundation\Service\GlobalVariableProviders\SiteConfigurationProvider;
use PSBits\Foundation\Service\GlobalVariableService;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Class GlobalVariableServiceTest
 *
 * @package PSBits\Foundation\Tests\Functional
 */
class GlobalVariableServiceTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    public const int ROOT_PAGE_ID = 1;
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/psbits/foundation',
    ];

    #[Test]
    public function earlyAccessConstantsProviderIsAccessible(): void
    {
        self::assertTrue(GlobalVariableService::has(EarlyAccessConstantsProvider::class));
    }

    #[Test]
    public function requestParameterProviderIsAccessible(): void
    {
        self::assertTrue(GlobalVariableService::has(RequestParameterProvider::class));
    }

    #[Test]
    public function siteConfigurationProviderIsAccessible(): void
    {
        self::assertTrue(GlobalVariableService::has(SiteConfigurationProvider::class));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');
        $this->mockRequest();
        $this->mockSiteConfiguration();
        $this->mockTsfe();
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TSFE'], $GLOBALS['TYPO3_REQUEST']);
        parent::tearDown();
    }

    private function mockRequest(): void
    {
        $request = new ServerRequest(
            'http://example.com/en/', 'GET', null, [], [
                'HTTP_HOST'   => 'example.com',
                'REQUEST_URI' => '/en/',
            ]
        );
        $GLOBALS['TYPO3_REQUEST'] = $request->withQueryParams(['id' => self::ROOT_PAGE_ID])
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
    }

    private function mockSiteConfiguration(): void
    {
        $this->writeSiteConfiguration('tree_page_layout_test', $this->buildSiteConfiguration(self::ROOT_PAGE_ID, '/'));
    }

    private function mockTsfe(): void
    {
        $GLOBALS['TSFE'] = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['TSFE']->id = 1;
    }
}
