<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Tests\Unit\ViewHelpers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PSBits\Foundation\Service\GlobalVariableProviders\EarlyAccessConstantsProvider;
use PSBits\Foundation\Service\GlobalVariableProviders\RequestParameterProvider;
use PSBits\Foundation\Service\GlobalVariableProviders\SiteConfigurationProvider;
use PSBits\Foundation\Service\GlobalVariableService;
use PSBits\Foundation\ViewHelpers\GlobalVariables\AbstractGlobalVariablesViewHelper;
use PSBits\Foundation\ViewHelpers\GlobalVariables\EarlyAccessConstantsViewHelper;
use PSBits\Foundation\ViewHelpers\GlobalVariables\RequestParameterViewHelper;
use PSBits\Foundation\ViewHelpers\GlobalVariables\SiteConfigurationViewHelper;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Class AbstractGlobalVariablesViewHelperTest
 *
 * Guards the base key handling. The subclasses used to prepend their provider class name inside
 * renderStatic(). After the migration to render() that prefix has to be applied by the abstract
 * parent, otherwise every lookup would silently start at the wrong path and - depending on the
 * strictness setting - either return the fallback or throw.
 *
 * @package PSBits\Foundation\Tests\Unit\ViewHelpers
 */
class AbstractGlobalVariablesViewHelperTest extends UnitTestCase
{
    /**
     * @return array<string, array{0: class-string, 1: string}>
     */
    public static function viewHelperDataProvider(): array
    {
        return [
            'early access constants' => [
                EarlyAccessConstantsViewHelper::class,
                EarlyAccessConstantsProvider::class,
            ],
            'request parameters'     => [
                RequestParameterViewHelper::class,
                RequestParameterProvider::class,
            ],
            'site configuration'     => [
                SiteConfigurationViewHelper::class,
                SiteConfigurationProvider::class,
            ],
        ];
    }

    /**
     * @param class-string $viewHelperClass
     *
     * @throws ReflectionException
     */
    #[Test]
    #[DataProvider('viewHelperDataProvider')]
    public function eachViewHelperDeclaresItsProviderAsBaseKey(
        string $viewHelperClass,
        string $expectedBaseKey,
    ): void {
        $viewHelper = new $viewHelperClass();

        $method = new ReflectionMethod($viewHelper, 'getBaseKey');
        self::assertSame($expectedBaseKey, $method->invoke($viewHelper));
    }

    /**
     * getVariable() has to join the base key and the path with a dot. This is the exact composition
     * the render() migration relies on.
     *
     * @throws ReflectionException
     */
    #[Test]
    public function getVariableJoinsBaseKeyAndPathWithADot(): void
    {
        GlobalVariableService::clearCache();
        GlobalVariableService::registerGlobalVariableProvider(TestGlobalVariableProvider::class);

        $method = new ReflectionMethod(AbstractGlobalVariablesViewHelper::class, 'getVariable');

        self::assertSame(
            'The nested value',
            $method->invoke(null, TestGlobalVariableProvider::class, [
                'path'     => 'nested.key',
                'strict'   => true,
                'fallback' => null,
            ])
        );
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function getVariablePassesTheFallbackThroughForAnUnknownNestedKey(): void
    {
        GlobalVariableService::clearCache();
        GlobalVariableService::registerGlobalVariableProvider(TestGlobalVariableProvider::class);

        $method = new ReflectionMethod(AbstractGlobalVariablesViewHelper::class, 'getVariable');

        self::assertSame(
            'the-fallback',
            $method->invoke(null, TestGlobalVariableProvider::class, [
                'path'     => 'nested.doesNotExist',
                'strict'   => false,
                'fallback' => 'the-fallback',
            ])
        );
    }

    /**
     * Without a path the lookup stops at the base key, which is the provider class name. The service
     * keys its variables by that name, so the result is the provider's whole variable array.
     *
     * @throws ReflectionException
     */
    #[Test]
    public function getVariableReturnsTheWholeProviderSetWhenNoPathIsGiven(): void
    {
        GlobalVariableService::clearCache();
        GlobalVariableService::registerGlobalVariableProvider(TestGlobalVariableProvider::class);

        $method = new ReflectionMethod(AbstractGlobalVariablesViewHelper::class, 'getVariable');

        self::assertSame(
            (new TestGlobalVariableProvider())->getGlobalVariables(),
            $method->invoke(null, TestGlobalVariableProvider::class, [
                'path'     => '',
                'strict'   => true,
                'fallback' => null,
            ])
        );
    }

    /**
     * @param class-string $viewHelperClass
     *
     * @throws ReflectionException
     */
    #[Test]
    #[DataProvider('viewHelperDataProvider')]
    public function pathBecomesOptionalForTheSpecialisedViewHelpers(string $viewHelperClass): void
    {
        $viewHelper = new $viewHelperClass();
        $viewHelper->setRenderingContext($this->createStub(RenderingContextInterface::class));
        $viewHelper->initializeArguments();

        $property = new ReflectionProperty($viewHelper, 'argumentDefinitions');
        self::assertFalse(
            $property->getValue($viewHelper)['path']->isRequired(),
            'The specialised ViewHelpers define a base path themselves, so path must be optional.'
        );
    }

    protected function tearDown(): void
    {
        GlobalVariableService::clearCache();
        parent::tearDown();
    }
}
