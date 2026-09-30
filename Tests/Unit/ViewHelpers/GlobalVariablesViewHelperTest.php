<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Tests\Unit\ViewHelpers;

use Exception;
use PHPUnit\Framework\Attributes\Test;
use PSBits\Foundation\Service\GlobalVariableProviders\GlobalVariableProviderInterface;
use PSBits\Foundation\Service\GlobalVariableService;
use PSBits\Foundation\ViewHelpers\GlobalVariablesViewHelper;
use ReflectionException;
use ReflectionProperty;
use RuntimeException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Class GlobalVariablesViewHelperTest
 *
 * The ViewHelper used to implement renderStatic(). Fluid v5 made render() abstract, so the class was
 * migrated to render(). This guards that the migration kept the argument handling and the delegation
 * to GlobalVariableService intact.
 *
 * @package PSBits\Foundation\Tests\Unit\ViewHelpers
 */
class GlobalVariablesViewHelperTest extends UnitTestCase
{
    /**
     * GlobalVariableService keys its variables by provider class name, so a path always starts with it.
     */
    private const string PROVIDER_KEY = TestGlobalVariableProvider::class;

    #[Test]
    public function pathIsRegisteredAsARequiredArgument(): void
    {
        $definitions = $this->initializedArgumentDefinitions();

        self::assertArrayHasKey('path', $definitions);
        self::assertTrue(
            $definitions['path']->isRequired(),
            'path must be required on the base ViewHelper.'
        );
    }

    /**
     * An unknown top level key is rejected by the service before the strictness is even considered.
     */
    #[Test]
    public function renderRejectsAnUnregisteredTopLevelKeyEvenWhenNotStrict(): void
    {
        $viewHelper = $this->createInitializedViewHelper();
        $this->setArguments(
            $viewHelper,
            [
                'path'     => 'unregistered.key',
                'strict'   => false,
                'fallback' => 'the-fallback',
            ]
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(1622575130);

        $viewHelper->render();
    }

    /**
     * The value of the Fluid v5 migration: render() has to exist, be public and delegate to the
     * service, otherwise the ViewHelper is abstract on Fluid v5 and unusable.
     */
    #[Test]
    public function renderResolvesTheValueFromTheGlobalVariableService(): void
    {
        GlobalVariableService::registerGlobalVariableProvider(TestGlobalVariableProvider::class);

        $viewHelper = $this->createInitializedViewHelper();
        $this->setArguments(
            $viewHelper,
            [
                'path'     => self::PROVIDER_KEY . '.nested.key',
                'strict'   => true,
                'fallback' => null,
            ]
        );

        self::assertSame(
            'The nested value',
            $viewHelper->render()
        );
    }

    /**
     * @throws Exception
     * @throws ReflectionException
     */
    #[Test]
    public function renderReturnsTheFallbackForAnUnknownPathWhenNotStrict(): void
    {
        GlobalVariableService::registerGlobalVariableProvider(TestGlobalVariableProvider::class);

        $viewHelper = $this->createInitializedViewHelper();
        $this->setArguments(
            $viewHelper,
            [
                'path'     => self::PROVIDER_KEY . '.nested.doesNotExist',
                'strict'   => false,
                'fallback' => 'the-fallback',
            ]
        );

        self::assertSame('the-fallback', $viewHelper->render());
    }

    /**
     * @throws Exception
     * @throws ReflectionException
     */
    #[Test]
    public function renderThrowsForAnUnknownPathWhenStrict(): void
    {
        GlobalVariableService::registerGlobalVariableProvider(TestGlobalVariableProvider::class);

        $viewHelper = $this->createInitializedViewHelper();
        $this->setArguments(
            $viewHelper,
            [
                'path'     => self::PROVIDER_KEY . '.nested.doesNotExist',
                'strict'   => true,
                'fallback' => 'the-fallback',
            ]
        );

        $this->expectException(RuntimeException::class);

        $viewHelper->render();
    }

    #[Test]
    public function strictDefaultsToTrueAndFallbackIsOptional(): void
    {
        $definitions = $this->initializedArgumentDefinitions();

        self::assertFalse($definitions['strict']->isRequired());
        self::assertTrue($definitions['strict']->getDefaultValue());
        self::assertFalse($definitions['fallback']->isRequired());
    }

    protected function setUp(): void
    {
        parent::setUp();
        GlobalVariableService::clearCache();
    }

    protected function tearDown(): void
    {
        GlobalVariableService::clearCache();
        parent::tearDown();
    }

    private function createInitializedViewHelper(): GlobalVariablesViewHelper
    {
        $viewHelper = new GlobalVariablesViewHelper();
        $viewHelper->setRenderingContext($this->createStub(RenderingContextInterface::class));
        $viewHelper->setRenderChildrenClosure(static fn() => '');
        $viewHelper->initializeArguments();

        return $viewHelper;
    }

    private function initializedArgumentDefinitions(): array
    {
        $viewHelper = $this->createInitializedViewHelper();
        $property   = new ReflectionProperty($viewHelper, 'argumentDefinitions');

        return $property->getValue($viewHelper);
    }

    /**
     * @throws ReflectionException
     */
    private function setArguments(object $viewHelper, array $arguments): void
    {
        $property = new ReflectionProperty($viewHelper, 'arguments');
        $property->setValue($viewHelper, $arguments);
    }
}

/**
 * Minimal provider for the tests above. The real providers all need a container, which is not
 * available in a unit test, but the service only ever calls the two interface methods.
 */
class TestGlobalVariableProvider implements GlobalVariableProviderInterface
{
    public function getGlobalVariables(): mixed
    {
        return [
            'topLevel' => 'The top level value',
            'nested'   => [
                'key' => 'The nested value',
            ],
        ];
    }

    public function isCacheable(): bool
    {
        return true;
    }
}
