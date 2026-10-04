<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Tests\Unit\Data;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PSBits\Foundation\Data\MainModuleConfiguration;
use PSBits\Foundation\Data\ModuleConfiguration;
use PSBits\Foundation\Data\PageTypeConfiguration;
use PSBits\Foundation\Data\PluginConfiguration;
use PSBits\Foundation\Enum\ContentType;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class PluginConfigurationTest
 *
 * Getter coverage for the configuration data classes.
 *
 * @package PSBits\Foundation\Tests\Unit\Data
 */
class PluginConfigurationTest extends UnitTestCase
{
    /**
     * @return array<string, array{string, bool, array, string, string, string, string, int, bool, ContentType, bool}>
     */
    public static function pluginConfigurationDataProvider(): array
    {
        return [
            'full configuration' => [
                'news',
                false,
                ['PSBits\Some\Controller\NewsController'],
                'news',
                'content',
                'extension-news',
                'News',
                42,
                true,
                ContentType::JSON,
                false,
            ],
            'defaults'           => [
                'news',
                true,
                [],
                '',
                '',
                '',
                '',
                0,
                false,
                ContentType::HTML,
                true,
            ],
        ];
    }

    #[Test]
    #[DataProvider('pluginConfigurationDataProvider')]
    public function pluginConfigurationReturnsConstructorValues(
        string     $name,
        bool       $addToElementWizard,
        array      $controllers,
        string     $flexForm,
        string     $group,
        string     $iconIdentifier,
        string     $title,
        int        $typeNum,
        bool       $typeNumCacheable,
        ContentType $typeNumContentType,
        bool       $typeNumDisableAllHeaderCode,
    ): void {
        $configuration = new PluginConfiguration(
            name: $name,
            addToElementWizard: $addToElementWizard,
            controllers: $controllers,
            flexForm: $flexForm,
            group: $group,
            iconIdentifier: $iconIdentifier,
            title: $title,
            typeNum: $typeNum,
            typeNumCacheable: $typeNumCacheable,
            typeNumContentType: $typeNumContentType,
            typeNumDisableAllHeaderCode: $typeNumDisableAllHeaderCode,
        );

        self::assertSame($name, $configuration->getName());
        self::assertSame($addToElementWizard, $configuration->isAddToElementWizard());
        self::assertSame($controllers, $configuration->getControllers());
        self::assertSame($flexForm, $configuration->getFlexForm());
        self::assertSame($group, $configuration->getGroup());
        self::assertSame($iconIdentifier, $configuration->getIconIdentifier());
        self::assertSame($title, $configuration->getTitle());
        self::assertSame($typeNum, $configuration->getTypeNum());
        self::assertSame($typeNumCacheable, $configuration->isTypeNumCacheable());
        self::assertSame($typeNumContentType, $configuration->getTypeNumContentType());
        self::assertSame($typeNumDisableAllHeaderCode, $configuration->isTypeNumDisableAllHeaderCode());
    }

    /**
     * @return array<string, array{string, string|null, string|null, string|null, array|null, bool, string|null}>
     */
    public static function mainModuleConfigurationDataProvider(): array
    {
        return [
            'full configuration' => [
                'mymain',
                'module-mymain',
                'LLL:EXT:core/Resources/Private/Language/locallang_mod_sys1.xlf:mymain',
                'TYPO3/CMS/Some/Navigation',
                ['after' => 'tools'],
                false,
                'live',
            ],
            'defaults'           => [
                'mymain',
                null,
                null,
                null,
                null,
                true,
                null,
            ],
        ];
    }

    #[Test]
    #[DataProvider('mainModuleConfigurationDataProvider')]
    public function mainModuleConfigurationReturnsConstructorValues(
        string  $key,
        ?string $iconIdentifier,
        ?string $labels,
        ?string $navigationComponent,
        ?array  $position,
        bool    $renderInModuleMenu,
        ?string $workspaces,
    ): void {
        $configuration = new MainModuleConfiguration(
            key: $key,
            iconIdentifier: $iconIdentifier,
            labels: $labels,
            navigationComponent: $navigationComponent,
            position: $position,
            renderInModuleMenu: $renderInModuleMenu,
            workspaces: $workspaces,
        );

        self::assertSame($key, $configuration->getKey());
        self::assertSame($iconIdentifier, $configuration->getIconIdentifier());
        self::assertSame($labels, $configuration->getLabels());
        self::assertSame($navigationComponent, $configuration->getNavigationComponent());
        self::assertSame($position, $configuration->getPosition());
        self::assertSame($renderInModuleMenu, $configuration->getRenderInModuleMenu());
        self::assertSame($workspaces, $configuration->getWorkspaces());
    }

    /**
     * @return array<string, array{string, string, array, string|null, string|null, string|null, string, array|null, bool, string|null}>
     */
    public static function moduleConfigurationDataProvider(): array
    {
        return [
            'full configuration' => [
                'mychild',
                'group',
                ['PSBits\Some\Controller\ChildController'],
                'module-mychild',
                'Child label',
                'TYPO3/CMS/Some/ChildNavigation',
                'mymain',
                ['before' => 'system'],
                false,
                'live, disabled',
            ],
            'defaults'           => [
                'mychild',
                'group, user',
                [],
                null,
                null,
                null,
                'web',
                null,
                true,
                null,
            ],
        ];
    }

    #[Test]
    #[DataProvider('moduleConfigurationDataProvider')]
    public function moduleConfigurationReturnsConstructorValues(
        string  $key,
        string  $access,
        array   $controllers,
        ?string $iconIdentifier,
        ?string $labels,
        ?string $navigationComponent,
        string  $parentModule,
        ?array  $position,
        bool    $renderInModuleMenu,
        ?string $workspaces,
    ): void {
        $configuration = new ModuleConfiguration(
            key: $key,
            access: $access,
            controllers: $controllers,
            iconIdentifier: $iconIdentifier,
            labels: $labels,
            navigationComponent: $navigationComponent,
            parentModule: $parentModule,
            position: $position,
            renderInModuleMenu: $renderInModuleMenu,
            workspaces: $workspaces,
        );

        self::assertSame($key, $configuration->getKey());
        self::assertSame($access, $configuration->getAccess());
        self::assertSame($controllers, $configuration->getControllers());
        self::assertSame($iconIdentifier, $configuration->getIconIdentifier());
        self::assertSame($labels, $configuration->getLabels());
        self::assertSame($navigationComponent, $configuration->getNavigationComponent());
        self::assertSame($parentModule, $configuration->getParentModule());
        self::assertSame($position, $configuration->getPosition());
        self::assertSame($renderInModuleMenu, $configuration->getRenderInModuleMenu());
        self::assertSame($workspaces, $configuration->getWorkspaces());
    }

    /**
     * @return array<string, array{int, string, array, string|null, string|null}>
     */
    public static function pageTypeConfigurationDataProvider(): array
    {
        return [
            'full configuration' => [
                99,
                'special_page',
                ['tt_content', 'news'],
                'page-type-special',
                'Special page',
            ],
            'defaults'           => [
                100,
                'simple_page',
                [],
                null,
                null,
            ],
        ];
    }

    #[Test]
    #[DataProvider('pageTypeConfigurationDataProvider')]
    public function pageTypeConfigurationReturnsConstructorValues(
        int         $doktype,
        string      $name,
        array       $allowedTables,
        ?string     $iconIdentifier,
        ?string     $label,
    ): void {
        $configuration = new PageTypeConfiguration(
            doktype: $doktype,
            name: $name,
            allowedTables: $allowedTables,
            iconIdentifier: $iconIdentifier,
            label: $label,
        );

        self::assertSame($doktype, $configuration->getDoktype());
        self::assertSame($name, $configuration->getName());
        self::assertSame($allowedTables, $configuration->getAllowedTables());
        self::assertSame($iconIdentifier, $configuration->getIconIdentifier());
        self::assertSame($label, $configuration->getLabel());
    }
}
