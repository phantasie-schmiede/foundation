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
use PSBits\Foundation\Service\TypoScriptProviderService;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Class TypoScriptProviderServiceTest
 *
 * @package PSBits\Foundation\Tests\Functional
 */
class TypoScriptProviderServiceTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/psbits/foundation',
    ];

    #[Test]
    public function defaultArgumentsReturnWholeTypoScript(): void
    {
        $typoScriptProviderService = GeneralUtility::makeInstance(TypoScriptProviderService::class);
        $typoScript = $typoScriptProviderService->get();
        $this->assertIsArray($typoScript);
        $this->assertArrayHasKey('config', $typoScript);
        $this->assertIsArray($typoScript['config']);
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');
        $this->mockRequest();
    }

    public function tearDown(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);
        parent::tearDown();
    }

    /** @TODO: Test should pass in frontend context, too. */
    private function mockRequest(): void
    {
        $request = new ServerRequest('http://example.com/en/', 'GET', null, [],
            ['HTTP_HOST' => 'example.com', 'REQUEST_URI' => '/en/']);
        $GLOBALS['TYPO3_REQUEST'] = $request->withQueryParams(['id' => 1])
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
    }
}
