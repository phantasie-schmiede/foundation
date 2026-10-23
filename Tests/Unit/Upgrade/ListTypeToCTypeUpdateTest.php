<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Upgrade;

use PHPUnit\Framework\Attributes\Test;
use PSBits\Foundation\Data\ExtensionInformationInterface;
use PSBits\Foundation\Data\PluginConfiguration;
use PSBits\Foundation\Service\ExtensionInformationService;
use ReflectionMethod;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Upgrades\AbstractListTypeToCTypeUpdate;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

use function array_key_first;
use function array_values;

/**
 * Class ListTypeToCTypeUpdateTest
 *
 * The upgrade wizard is only functional on TYPO3 v14, where the core provides the upgrade wizard infrastructure, so
 * the tests are skipped on v13.
 *
 * @package PSBits\Foundation\Upgrade
 */
class ListTypeToCTypeUpdateTest extends UnitTestCase
{
    private ListTypeToCTypeUpdate $subject;

    #[Test]
    public function mapsAllPluginsOfAllInstalledExtensions(): void
    {
        $subject = $this->createSubject(
            $this->createExtensionInformation('poems', [
                new PluginConfiguration(name: 'Poem'),
                new PluginConfiguration(name: 'PoemOfTheDay'),
            ]),
            $this->createExtensionInformation('song_book', [
                new PluginConfiguration(name: 'Song'),
            ])
        );

        $mapping = $this->getMapping($subject);

        self::assertSame([
            'poems_poem'         => 'poems_poem',
            'poems_poemoftheday' => 'poems_poemoftheday',
            'songbook_song'      => 'songbook_song',
        ], $mapping);
    }

    #[Test]
    public function providesAPlaceholderWhenNoPluginIsInstalled(): void
    {
        $subject = $this->createSubject($this->createExtensionInformation('xlfEditor', []));

        $mapping = $this->getMapping($subject);

        self::assertCount(1, $mapping);
        self::assertSame(array_key_first($mapping), array_values($mapping)[0]);
    }

    #[Test]
    public function providesTitleAndDescription(): void
    {
        self::assertNotSame('', $this->subject->getTitle());
        self::assertNotSame('', $this->subject->getDescription());
    }

    /**
     * @param PluginConfiguration[] $plugins
     */
    private function createExtensionInformation(string $extensionKey, array $plugins): ExtensionInformationInterface
    {
        $extensionInformation = $this->createMock(ExtensionInformationInterface::class);
        $extensionInformation->method('getPlugins')
            ->willReturn($plugins);
        $extensionInformation->method('getExtensionKey')
            ->willReturn($extensionKey);

        return $extensionInformation;
    }

    /**
     * @return array<string, string>
     */
    private function getMapping(ListTypeToCTypeUpdate $subject): array
    {
        return (new ReflectionMethod(ListTypeToCTypeUpdate::class, 'getListTypeToCTypeMapping'))->invoke($subject);
    }

    private function createSubject(ExtensionInformationInterface ...$extensionInformation): ListTypeToCTypeUpdate
    {
        $service = $this->createMock(ExtensionInformationService::class);
        $service->method('getAllExtensionInformation')
            ->willReturn($extensionInformation);

        return new ListTypeToCTypeUpdate($service, new ConnectionPool());
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (false === class_exists(AbstractListTypeToCTypeUpdate::class)) {
            self::markTestSkipped('The upgrade wizard is only available on TYPO3 v14.');
        }

        $this->subject = $this->createSubject(
            $this->createExtensionInformation('poems', [new PluginConfiguration(name: 'Poem')])
        );
    }
}
