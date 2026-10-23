<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\EventListener;

use PHPUnit\Framework\Attributes\Test;
use PSBits\Foundation\Data\ExtensionInformationInterface;
use PSBits\Foundation\Data\PluginConfiguration;
use PSBits\Foundation\Service\ExtensionInformationService;
use PSBits\Foundation\Tests\Examples\FakeLanguageServiceFactory;
use PSBits\Foundation\Utility\Localization\LoggingUtility;
use PSBits\Foundation\Utility\Typo3VersionUtility;
use ReflectionProperty;
use TYPO3\CMS\Backend\Controller\Event\ModifyNewContentElementWizardItemsEvent;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

use function array_filter;
use function array_keys;
use function array_search;

/**
 * Class ContentElementWizardPluginRegistrationTest
 *
 * @package PSBits\Foundation\EventListener
 */
class ContentElementWizardPluginRegistrationTest extends UnitTestCase
{
    private const string LIST_TYPE = 'poems_poem';

    private IconRegistry|null $iconRegistry            = null;
    protected bool            $resetSingletonInstances = true;

    #[Test]
    public function replacesTheTcaDerivedItemWithTheVendorGroupItem(): void
    {
        $event = $this->createEvent($this->getTcaDerivedItems());

        $this->createSubject(new PluginConfiguration(name: 'Poem'))($event);

        $items = $event->getWizardItems();

        self::assertArrayNotHasKey('plugins_' . self::LIST_TYPE, $items);
        self::assertArrayHasKey('psbits', $items);
        self::assertSame('', $items['psbits']['header']);

        $item = $items['psbits_' . self::LIST_TYPE];
        self::assertSame($this->getExpectedDefaultValues(), $item['defaultValues']);
        self::assertSame('Poem', $item['title']);
        self::assertSame('', $item['description']);
        self::assertSame('content-plugin', $item['iconIdentifier']);
    }

    #[Test]
    public function removesTheTcaDerivedItemWhenThePluginIsExcluded(): void
    {
        $event = $this->createEvent($this->getTcaDerivedItems());

        $this->createSubject(new PluginConfiguration(name: 'Poem', addToElementWizard: false))($event);

        $items = $event->getWizardItems();

        self::assertArrayNotHasKey('plugins_' . self::LIST_TYPE, $items);
        self::assertArrayNotHasKey('psbits', $items);
        self::assertArrayNotHasKey('psbits_' . self::LIST_TYPE, $items);
    }

    #[Test]
    public function addsTheVendorGroupItemWithoutAnExistingTcaItem(): void
    {
        $event = $this->createEvent([
            'plugins' => ['header' => 'Plugins'],
        ]);

        $this->createSubject(new PluginConfiguration(name: 'Poem'))($event);

        $items = $event->getWizardItems();

        self::assertArrayHasKey('psbits', $items);
        self::assertArrayHasKey('psbits_' . self::LIST_TYPE, $items);
        self::assertSame($this->getExpectedDefaultValues(), $items['psbits_' . self::LIST_TYPE]['defaultValues']);
    }

    #[Test]
    public function registersTheVendorGroupHeaderOnlyOnceForMultiplePlugins(): void
    {
        $event = $this->createEvent($this->getTcaDerivedItems());

        $this->createSubject(
            new PluginConfiguration(name: 'Poem'),
            new PluginConfiguration(name: 'PoemOfTheDay')
        )($event);

        $keys = array_keys($event->getWizardItems());

        self::assertCount(1, array_filter($keys, static fn(string $key): bool => 'psbits' === $key));
        self::assertArrayHasKey('psbits_poems_poem', $event->getWizardItems());
        self::assertArrayHasKey('psbits_poems_poemoftheday', $event->getWizardItems());
        self::assertLessThan(
            (int)array_search('psbits_poems_poemoftheday', $keys, true),
            (int)array_search('psbits_poems_poem', $keys, true)
        );
    }

    #[Test]
    public function usesTheConfiguredGroupAndIcon(): void
    {
        $this->iconRegistry = $this->createMock(IconRegistry::class);
        $this->iconRegistry->method('isRegistered')
            ->willReturnCallback(static fn(string $identifier): bool => 'poems-poem' === $identifier);

        $event = $this->createEvent($this->getTcaDerivedItems());

        $this->createSubject(
            new PluginConfiguration(
                name: 'Poem',
                group: 'custom',
                iconIdentifier: 'poems-poem'
            )
        )($event);

        $items = $event->getWizardItems();

        self::assertArrayNotHasKey('plugins_' . self::LIST_TYPE, $items);
        self::assertArrayHasKey('custom', $items);
        self::assertSame('poems-poem', $items['custom_' . self::LIST_TYPE]['iconIdentifier']);
    }

    private function createSubject(PluginConfiguration ...$plugins): ContentElementWizardPluginRegistration
    {
        $extensionInformationService = $this->createMock(ExtensionInformationService::class);
        $extensionInformationService->method('getAllExtensionInformation')
            ->willReturn([
                $this->createExtensionInformation(...$plugins),
            ]);

        if (null === $this->iconRegistry) {
            $this->iconRegistry = $this->createMock(IconRegistry::class);
            $this->iconRegistry->method('isRegistered')
                ->willReturn(false);
        }

        return new ContentElementWizardPluginRegistration($extensionInformationService, $this->iconRegistry);
    }

    /**
     * @param array<string, array<string, mixed>> $wizardItems
     */
    private function createEvent(array $wizardItems): ModifyNewContentElementWizardItemsEvent
    {
        return new ModifyNewContentElementWizardItemsEvent(
            $wizardItems,
            [],
            null,
            0,
            0,
            new ServerRequest()
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function getTcaDerivedItems(): array
    {
        return [
            'plugins'                    => [
                'header' => 'Plugins',
            ],
            'plugins_poems_other'        => [
                'title'          => 'Other Plugin',
                'description'    => '',
                'iconIdentifier' => 'content-plugin',
                'defaultValues'  => ['CType' => 'list', 'list_type' => 'poems_other'],
            ],
            'plugins_' . self::LIST_TYPE => [
                'title'          => 'TCA Label',
                'description'    => '',
                'iconIdentifier' => 'content-plugin',
                'defaultValues'  => $this->getExpectedDefaultValues(),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function getExpectedDefaultValues(): array
    {
        /*
         * On v14, plugins are registered as their own content type instead of list_type subtypes.
         */
        if (Typo3VersionUtility::isAtLeast('14.0')) {
            return ['CType' => self::LIST_TYPE];
        }

        return [
            'CType'     => 'list',
            'list_type' => self::LIST_TYPE,
        ];
    }

    private function createExtensionInformation(PluginConfiguration ...$plugins): ExtensionInformationInterface
    {
        $extensionInformation = $this->createMock(ExtensionInformationInterface::class);
        $extensionInformation->method('getPlugins')
            ->willReturn($plugins);
        $extensionInformation->method('getExtensionKey')
            ->willReturn('poems');
        $extensionInformation->method('getExtensionName')
            ->willReturn('Poems');
        $extensionInformation->method('getVendorName')
            ->willReturn('PSBits');

        return $extensionInformation;
    }

    protected function setUp(): void
    {
        parent::setUp();

        /*
         * Prevent the missing-translation logging from looking up the extension configuration, which is not
         * available in unit tests.
         */
        (new ReflectionProperty(LoggingUtility::class, 'logMissingLanguageLabels'))->setValue(null, false);

        /*
         * The language service factory is overwritten in ext_localconf.php, which does not run in unit tests.
         * Pre-seed the singleton so translations resolve without the core factory's dependencies.
         */
        $languageService = $this->createMock(LanguageService::class);
        $languageService->method('translate')
            ->willReturn(null);
        $languageService->method('getLocale')
            ->willReturn(new Locale('en'));

        GeneralUtility::setSingletonInstance(
            LanguageServiceFactory::class,
            new FakeLanguageServiceFactory($languageService)
        );

        $this->iconRegistry = null;
    }
}
