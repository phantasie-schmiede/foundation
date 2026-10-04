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
use PSBits\Foundation\ViewHelpers\TranslateViewHelper;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionException;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Class TranslateViewHelperTest
 *
 * Renders the view helper against the real localization stack: the language
 * files, the language service installed by the extension and the plural form
 * handling all run for real, which a unit test cannot do without faking the
 * result. The unit test in Tests/Unit covers the parts that need no running
 * instance: argument registration and the request resolution.
 *
 * @package PSBits\Foundation\Tests\Functional
 */
class TranslateViewHelperTest extends FunctionalTestCase
{
    public const int    ROOT_PAGE_ID          = 1;
    public const string FIXTURE_LANGUAGE_FILE = 'Tests/Functional/Fixtures/Language/fixture.xlf';

    protected array $testExtensionsToLoad = [
        'typo3conf/ext/psbits/foundation',
    ];
    private ServerRequest $request;

    #[Test]
    public function aLabelIsResolvedFromTheDefaultLanguageFile(): void
    {
        $this->bootstrapRequest(0);

        self::assertSame(
            'Test message',
            $this->render(['id' => self::fixtureKey('message')])
        );
    }

    #[Test]
    public function aLabelIsResolvedForTheRequestLanguage(): void
    {
        $this->bootstrapRequest(1);

        self::assertSame(
            'Testnachricht',
            $this->render(['id' => self::fixtureKey('message')])
        );
    }

    #[Test]
    public function pluralFormsAreResolvedWithTheQuantityArgument(): void
    {
        $this->bootstrapRequest(1);

        self::assertSame(
            '1 Tag',
            $this->render([
                'id'          => self::fixtureKey('day'),
                'languageKey' => 'de',
                'arguments'   => ['quantity' => 1],
            ])
        );
        self::assertSame(
            '3 Tage',
            $this->render([
                'id'          => self::fixtureKey('day'),
                'languageKey' => 'de',
                'arguments'   => ['quantity' => 3],
            ])
        );
    }

    #[Test]
    public function namedArgumentsAreReplacedInTheLabel(): void
    {
        $this->bootstrapRequest(0);

        self::assertSame(
            'Hello Jane!',
            $this->render([
                'id'        => self::fixtureKey('withArgument'),
                'arguments' => ['name' => 'Jane'],
            ])
        );
    }

    #[Test]
    public function anExcludedSiteLanguageReturnsNull(): void
    {
        $this->bootstrapRequest(2);

        self::assertNull(
            $this->render([
                'id'                => self::fixtureKey('message'),
                'excludedLanguages' => ['da'],
            ]),
            'A language listed in excludedLanguages must return null instead of the label.'
        );
    }

    #[Test]
    public function aMissingLabelFallsBackToTheDefault(): void
    {
        $this->bootstrapRequest(0);

        self::assertSame(
            'Hello Jane',
            $this->render([
                'id'        => self::fixtureKey('doesNotExist'),
                'default'   => 'Hello %s',
                'arguments' => ['Jane'],
            ])
        );
    }

    #[Test]
    public function theKeyArgumentResolvesTheLabel(): void
    {
        $this->bootstrapRequest(1);

        self::assertSame(
            'Testnachricht',
            $this->render(['key' => self::fixtureKey('message')])
        );
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);
        parent::tearDown();
    }

    /**
     * languageId 0 is the default language (locale en, which maps to the "default"
     * language key of the xlf source language), 1 is German, 2 is Danish.
     */
    private function bootstrapRequest(int $languageId): void
    {
        $this->request = (new ServerRequest(
            'http://example.com/',
            'GET',
            null,
            [],
            [
                'HTTP_HOST'   => 'example.com',
                'REQUEST_URI' => '/',
            ]
        ))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('language', $this->createSiteLanguage($languageId));

        $GLOBALS['TYPO3_REQUEST'] = $this->request;
    }

    private function createSiteLanguage(int $languageId): SiteLanguage
    {
        // A string locale on purpose: the supported majors accept a string (v13 also a Locale).
        return new SiteLanguage(
            $languageId,
            [0       => 'en', 1 => 'de', 2 => 'da'][$languageId],
            new Uri('/'),
            ['title' => 'Test language']
        );
    }

    /**
     * Instantiates the real view helper with a real rendering context. The view
     * itself is bypassed - argument registration and conversion are covered by
     * the unit test, everything from resolveRequest() down runs for real.
     *
     * @param array<string, mixed> $arguments
     *
     * @throws AspectNotFoundException
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    private function render(array $arguments): mixed
    {
        $context = GeneralUtility::makeInstance(RenderingContextFactory::class)->create();

        /*
         * The request is exposed as a rendering context attribute; older Fluid versions
         * set it via setAttribute(), newer ones via set().
         */
        if (method_exists($context, 'set')) {
            $context->set(ServerRequestInterface::class, $this->request);
        } else {
            $context->setAttribute(ServerRequestInterface::class, $this->request);
        }

        $viewHelper = new TranslateViewHelper();
        $viewHelper->setRenderingContext($context);
        $viewHelper->initializeArguments();

        /*
         * prepareArguments() returns ArgumentDefinition objects, not values, so the defaults have
         * to be unwrapped the way ViewHelperNode::evaluate() does before render() is called.
         * Without them every optional argument would be an undefined array key inside render().
         */
        $defaults = array_map(static function($definition) {
            return $definition->getDefaultValue();
        }, $viewHelper->prepareArguments());

        $viewHelper->setArguments($arguments + $defaults);
        $viewHelper->setRenderChildrenClosure(static fn() => null);

        return $viewHelper->render();
    }

    private static function fixtureKey(string $key): string
    {
        return 'LLL:EXT:foundation/' . self::FIXTURE_LANGUAGE_FILE . ':' . $key;
    }
}
