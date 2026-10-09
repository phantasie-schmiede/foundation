<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Tests\Unit\ViewHelpers;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PSBits\Foundation\ViewHelpers\TranslateViewHelper;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception as ViewHelperException;

/**
 * Class TranslateViewHelperTest
 *
 * The view helper used to be a renderStatic() implementation. These tests lock in the parts of
 * render() that only work as an instance method and need no running instance: the argument
 * wiring, which Fluid v5 changed, and the request resolution (rendering context attribute).
 * Label resolution against the real language stack is covered by the functional test in
 * Tests/Functional/TranslateViewHelperTest.php.
 *
 * @package PSBits\Foundation\Tests\Unit\ViewHelpers
 */
class TranslateViewHelperTest extends UnitTestCase
{
    private TranslateViewHelper         $subject;
    private RenderingContext&MockObject $renderingContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject          = new TranslateViewHelper();
        $this->renderingContext = $this->createMock(RenderingContext::class);
        $this->subject->setRenderingContext($this->renderingContext);
        $this->subject->initializeArguments();
    }

    #[Test]
    public function allArgumentsAreRegistered(): void
    {
        $argumentDefinitions = $this->subject->prepareArguments();

        foreach (
            [
                'arguments',
                'default',
                'excludedLanguages',
                'extensionName',
                'id',
                'key',
                'languageKey',
            ] as $name
        ) {
            self::assertArrayHasKey($name, $argumentDefinitions, $name . ' is not registered');
        }
    }

    #[Test]
    public function aMissingIdOrKeyIsReported(): void
    {
        $this->expectException(ViewHelperException::class);
        $this->expectExceptionCode(1682312266);

        // The registered defaults have to be in place, otherwise render() reads undefined array keys
        // long before it can complain about the missing label.
        $this->render([]);
    }

    /**
     * The core exposes the request as a rendering context attribute, which resolveRequest() reads.
     */
    #[Test]
    public function theRequestIsReadFromTheRenderingContextAttribute(): void
    {
        $request = $this->createRequest('de');

        $this->renderingContext->method('hasAttribute')
            ->with(ServerRequestInterface::class)
            ->willReturn(true);
        $this->renderingContext->method('getAttribute')
            ->with(ServerRequestInterface::class)
            ->willReturn($request);

        $reflection = new \ReflectionMethod($this->subject, 'resolveRequest');
        self::assertSame($request, $reflection->invoke($this->subject));
    }

    /**
     * An id is mandatory, but a key alone has to keep working - an empty key is not a valid label
     * either, so the same exception is expected.
     */
    #[Test]
    public function anEmptyKeyIsReported(): void
    {
        $this->expectException(ViewHelperException::class);
        $this->expectExceptionCode(1682312266);

        $this->render(['key' => '']);
    }

    /**
     * @param array<string, mixed> $arguments
     */
    private function render(array $arguments): mixed
    {
        // prepareArguments() returns ArgumentDefinition objects, not values, so the defaults have
        // to be unwrapped the way ViewHelperNode::evaluate() does before render() is called.
        // Without them every optional argument would be an undefined array key inside render().
        $defaults = [];

        foreach ($this->subject->prepareArguments() as $name => $definition) {
            $defaults[$name] = $definition->getDefaultValue();
        }

        $this->subject->setArguments($arguments + $defaults);
        $this->subject->setRenderChildrenClosure(static fn() => null);

        return $this->subject->render();
    }

    /**
     * The frontend middleware puts the resolved SiteLanguage into the "language" request attribute,
     * which is what render() reads the locale from - a SiteLanguage, not a Context.
     */
    private function createRequest(string $locale): ServerRequestInterface
    {
        $language = new SiteLanguage(
            0,
            $locale,
            $this->createMock(UriInterface::class),
            ['title' => 'Test language']
        );

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')
            ->with('language')
            ->willReturn($language);

        return $request;
    }
}
