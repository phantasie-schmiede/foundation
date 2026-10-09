<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Tests\Unit\Service\Configuration\Tca;

use PHPUnit\Framework\Attributes\Test;
use PSBits\Foundation\Service\Configuration\Tca\DefaultFieldDefinition;
use PSBits\Foundation\Service\Configuration\Tca\DefaultFieldRegistry;
use PSBits\Foundation\Service\Configuration\Tca\TcaTable;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class DefaultFieldRegistryTest
 *
 * @package PSBits\Foundation\Tests\Unit\Service\Configuration\Tca
 */
class DefaultFieldRegistryTest extends UnitTestCase
{
    private const string TABLE_NAME = 'default_field_registry_test';

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TCA'][self::TABLE_NAME] = [
            'columns'  => [],
            'palettes' => [],
            'types'    => [
                '0' => ['showitem' => 'base_field'],
            ],
        ];
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TCA'][self::TABLE_NAME]);

        parent::tearDown();
    }

    #[Test]
    public function findByReferenceReturnsMatchingDefinition(): void
    {
        $registry = $this->createRegistry();

        self::assertSame('language', $registry->findByReference('language')?->getReference());
        self::assertSame('hidden', $registry->findByReference('hidden')?->getReference());
        self::assertSame('timeRestriction', $registry->findByReference('timeRestriction')?->getReference());
    }

    #[Test]
    public function findByReferenceMatchesUnderscoredVariant(): void
    {
        $registry = $this->createRegistry();

        self::assertSame(
            DefaultFieldDefinition::REFERENCE_LANGUAGE_PALETTE,
            $registry->findByReference('language_palette')?->getReference()
        );
        self::assertSame('timeRestriction', $registry->findByReference('time_restriction')?->getReference());
    }

    #[Test]
    public function findByReferenceReturnsNullForUnknownReference(): void
    {
        self::assertNull($this->createRegistry()->findByReference('unknownReference'));
    }

    #[Test]
    public function materializeBlockAddsItemsInDefinitionOrder(): void
    {
        $registry = $this->createRegistry();
        $registry->materializeBlock(DefaultFieldDefinition::BLOCK_LANGUAGE);
        $registry->materializeBlock(DefaultFieldDefinition::BLOCK_ACCESS);

        $showitem = $GLOBALS['TCA'][self::TABLE_NAME]['types']['0']['showitem'];

        self::assertSame(
            'base_field, --div--;Language, --palette--;;language, --div--;Access, hidden, --palette--;;timeRestriction',
            $showitem
        );
    }

    #[Test]
    public function materializeBlockIsIdempotent(): void
    {
        $registry = $this->createRegistry();
        $registry->materializeBlock(DefaultFieldDefinition::BLOCK_ACCESS);
        $registry->materializeBlock(DefaultFieldDefinition::BLOCK_ACCESS);
        $registry->materializeBlock(DefaultFieldDefinition::BLOCK_ACCESS);

        $showitem = $GLOBALS['TCA'][self::TABLE_NAME]['types']['0']['showitem'];

        self::assertSame('base_field, --div--;Access, hidden, --palette--;;timeRestriction', $showitem);
        self::assertTrue($registry->isBlockMaterialized(DefaultFieldDefinition::BLOCK_ACCESS));
        self::assertFalse($registry->isBlockMaterialized(DefaultFieldDefinition::BLOCK_LANGUAGE));
    }

    /**
     * @return DefaultFieldRegistry
     */
    private function createRegistry(): DefaultFieldRegistry
    {
        return new DefaultFieldRegistry(new TcaTable(self::TABLE_NAME), [
            new DefaultFieldDefinition(
                'language',
                DefaultFieldDefinition::BLOCK_LANGUAGE,
                DefaultFieldDefinition::TYPE_TAB,
                'language',
                'Language'
            ),
            new DefaultFieldDefinition(
                DefaultFieldDefinition::REFERENCE_LANGUAGE_PALETTE,
                DefaultFieldDefinition::BLOCK_LANGUAGE,
                DefaultFieldDefinition::TYPE_PALETTE,
                'language'
            ),
            new DefaultFieldDefinition(
                'access',
                DefaultFieldDefinition::BLOCK_ACCESS,
                DefaultFieldDefinition::TYPE_TAB,
                'access',
                'Access'
            ),
            new DefaultFieldDefinition(
                'hidden',
                DefaultFieldDefinition::BLOCK_ACCESS,
                DefaultFieldDefinition::TYPE_FIELD,
                'hidden'
            ),
            new DefaultFieldDefinition(
                'timeRestriction',
                DefaultFieldDefinition::BLOCK_ACCESS,
                DefaultFieldDefinition::TYPE_PALETTE,
                'timeRestriction'
            ),
        ]);
    }
}
