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
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
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
    public function earlyAccessConstantsProviderIsAccessibleInBackendContext(): void
    {
        $this->mockBackendRequest();
        self::assertTrue(GlobalVariableService::has(EarlyAccessConstantsProvider::class));
    }

    #[Test]
    public function earlyAccessConstantsProviderIsAccessibleInFrontendContext(): void
    {
        $this->mockFrontendRequest();
        self::assertTrue(GlobalVariableService::has(EarlyAccessConstantsProvider::class));
    }

    #[Test]
    public function requestParameterProviderIsAccessibleInBackendContext(): void
    {
        $this->mockBackendRequest();
        self::assertTrue(GlobalVariableService::has(RequestParameterProvider::class));
    }

    #[Test]
    public function requestParameterProviderIsAccessibleInFrontendContext(): void
    {
        $this->mockFrontendRequest();
        self::assertTrue(GlobalVariableService::has(RequestParameterProvider::class));
    }

    #[Test]
    public function siteConfigurationProviderIsAccessibleInFrontendContext(): void
    {
        $this->mockFrontendRequest();
        self::assertTrue(GlobalVariableService::has(SiteConfigurationProvider::class));
    }

    #[Test]
    public function siteConfigurationProviderIsNotAccessibleInBackendContext(): void
    {
        $this->mockBackendRequest();
        self::assertFalse(GlobalVariableService::has(SiteConfigurationProvider::class));
    }

    protected function tearDown(): void
    {
        GlobalVariableService::clearCache();
        unset($GLOBALS['TSFE'], $GLOBALS['TYPO3_REQUEST']);
        parent::tearDown();
    }

    private function mockBackendRequest(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())->withAttribute(
            'applicationType',
            SystemEnvironmentBuilder::REQUESTTYPE_BE
        );
    }

    private function mockFrontendRequest(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $request = new ServerRequest(
            'http://example.com/en/', 'GET', null, [], [
                'HTTP_HOST'   => 'example.com',
                'REQUEST_URI' => '/en/',
            ]
        );
        $GLOBALS['TYPO3_REQUEST'] = $request->withQueryParams(['id' => self::ROOT_PAGE_ID])
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);

        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');
        $this->mockSiteConfiguration();
        $this->mockTsfe();
    }

    private function mockSiteConfiguration(): void
    {
        $this->writeSiteConfiguration('tree_page_layout_test', $this->buildSiteConfiguration(self::ROOT_PAGE_ID, '/'));
    }

    private function mockTsfe(): void
    {
        $GLOBALS['TSFE'] = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $GLOBALS['TSFE']->id = self::ROOT_PAGE_ID;
    }
}
