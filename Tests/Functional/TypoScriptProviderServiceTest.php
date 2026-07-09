<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Tests\Functional;

use JsonException;
use PHPUnit\Framework\Attributes\Test;
use PSBits\Foundation\Service\TypoScriptProviderService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Class TypoScriptProviderServiceTest
 *
 * @package PSBits\Foundation\Tests\Functional
 */
class TypoScriptProviderServiceTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    private const int ROOT_PAGE_ID = 1;

    protected array $testExtensionsToLoad = [
        'typo3conf/ext/psbits/foundation',
    ];

    /**
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function defaultArgumentsReturnWholeTypoScriptInBackendContext(): void
    {
        $this->mockBackendRequest();
        $typoScriptProviderService = GeneralUtility::makeInstance(TypoScriptProviderService::class);
        $typoScript = $typoScriptProviderService->get();
        self::assertIsArray($typoScript);
        self::assertArrayHasKey('config', $typoScript);
        self::assertIsArray($typoScript['config']);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function defaultArgumentsReturnWholeTypoScriptInFrontendContext(): void
    {
        $this->mockFrontendRequest();
        $typoScriptProviderService = GeneralUtility::makeInstance(TypoScriptProviderService::class);
        $typoScript = $typoScriptProviderService->get();
        self::assertIsArray($typoScript);
        self::assertArrayHasKey('config', $typoScript);
        self::assertIsArray($typoScript['config']);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TSFE'], $GLOBALS['TYPO3_REQUEST']);
        parent::tearDown();
    }

    /**
     * @param int $applicationType Constant from SystemEnvironmentBuilder::REQUESTTYPE_*
     *
     * @return ServerRequestInterface
     */
    private function createRequest(int $applicationType): ServerRequestInterface
    {
        $request = new ServerRequest(
            'http://example.com/en/', 'GET', null, [], [
                'HTTP_HOST'   => 'example.com',
                'REQUEST_URI' => '/en/',
            ]
        );

        return $request->withQueryParams(['id' => self::ROOT_PAGE_ID])
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request))
            ->withAttribute('applicationType', $applicationType);
    }

    private function mockBackendRequest(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = $this->createRequest(SystemEnvironmentBuilder::REQUESTTYPE_BE);
    }

    private function mockFrontendRequest(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray(['config.' => []]);
        $request = new ServerRequest(
            'http://example.com/en/', 'GET', null, [], [
                'HTTP_HOST'   => 'example.com',
                'REQUEST_URI' => '/en/',
            ]
        );
        $GLOBALS['TYPO3_REQUEST'] = $this->createRequest(SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);

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
