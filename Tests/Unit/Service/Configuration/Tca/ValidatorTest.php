<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Tests\Unit\Service\Configuration\Tca;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PSBits\Foundation\Exceptions\MisconfiguredTcaException;
use PSBits\Foundation\Service\Configuration\Tca\Validator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class ValidatorTest
 *
 * @package PSBits\Foundation\Tests\Unit\Service\Configuration\Tca
 */
class ValidatorTest extends UnitTestCase
{
    private const string COLUMN_NAME = 'bad_field';
    private const string TABLE_NAME  = 'tx_validator_test';

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string, 2: int}>
     */
    public static function invalidRelationshipCombinations(): array
    {
        return [
            'select manyToMany without MM'              => [
                [
                    'foreign_table' => 'tx_foundation_test_foreign',
                    'relationship'  => 'manyToMany',
                    'renderType'    => 'selectSingle',
                    'type'          => 'select',
                ],
                '/^tx_validator_test\.bad_field: The relationship "manyToMany" requires an intermediate table\./',
                1791849602,
            ],
            'inline manyToMany without MM'              => [
                [
                    'foreign_field' => 'uid_parent',
                    'foreign_table' => 'tx_foundation_test_foreign',
                    'relationship'  => 'manyToMany',
                    'type'          => 'inline',
                ],
                '/The relationship "manyToMany" requires an intermediate table\./',
                1791849602,
            ],
            'group manyToMany without MM'               => [
                [
                    'allowed'      => 'tx_foundation_test_foreign',
                    'relationship' => 'manyToMany',
                    'type'         => 'group',
                ],
                '/The relationship "manyToMany" requires an intermediate table\./',
                1791849602,
            ],
            'select relationship without foreign_table' => [
                [
                    'relationship' => 'oneToOne',
                    'renderType'   => 'selectSingle',
                    'type'         => 'select',
                ],
                '/The "relationship" option has no effect for a select column without a "foreign_table" option\./',
                1791849603,
            ],
            'category manyToOne'                        => [
                [
                    'relationship' => 'manyToOne',
                    'type'         => 'category',
                ],
                '/The type "category" does not support the relationship "manyToOne"\. Allowed values are: "manyToMany", "oneToMany", "oneToOne"\./',
                1791849601,
            ],
            'file manyToMany'                           => [
                [
                    'relationship' => 'manyToMany',
                    'type'         => 'file',
                ],
                '/The type "file" does not support the relationship "manyToMany"\./',
                1791849601,
            ],
            'folder manyToMany'                         => [
                [
                    'relationship' => 'manyToMany',
                    'type'         => 'folder',
                ],
                '/The type "folder" does not support the relationship "manyToMany"\./',
                1791849601,
            ],
            'unknown relationship value'                => [
                [
                    'foreign_table' => 'tx_foundation_test_foreign',
                    'relationship'  => 'bogus',
                    'renderType'    => 'selectSingle',
                    'type'          => 'select',
                ],
                '/The type "select" does not support the relationship "bogus"\./',
                1791849601,
            ],
        ];
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function validRelationshipCombinations(): array
    {
        return [
            'select manyToOne with foreign_table'         => [
                [
                    'foreign_table' => 'tx_foundation_test_foreign',
                    'relationship'  => 'manyToOne',
                    'renderType'    => 'selectSingle',
                    'type'          => 'select',
                ],
            ],
            'select oneToOne with foreign_table'          => [
                [
                    'foreign_table' => 'tx_foundation_test_foreign',
                    'relationship'  => 'oneToOne',
                    'renderType'    => 'selectSingle',
                    'type'          => 'select',
                ],
            ],
            'select oneToMany with foreign_table'         => [
                [
                    'foreign_table' => 'tx_foundation_test_foreign',
                    'relationship'  => 'oneToMany',
                    'renderType'    => 'selectMultipleSideBySide',
                    'type'          => 'select',
                ],
            ],
            'select manyToMany with MM'                   => [
                [
                    'MM'            => 'tx_foundation_test_mm',
                    'foreign_table' => 'tx_foundation_test_foreign',
                    'relationship'  => 'manyToMany',
                    'renderType'    => 'selectMultipleSideBySide',
                    'type'          => 'select',
                ],
            ],
            'category oneToOne'                           => [
                [
                    'relationship' => 'oneToOne',
                    'type'         => 'category',
                ],
            ],
            'category oneToMany'                          => [
                [
                    'relationship' => 'oneToMany',
                    'type'         => 'category',
                ],
            ],
            'category manyToMany without explicit MM'     => [
                [
                    'relationship' => 'manyToMany',
                    'type'         => 'category',
                ],
            ],
            'inline oneToOne'                             => [
                [
                    'foreign_field' => 'uid_parent',
                    'foreign_table' => 'tx_foundation_test_foreign',
                    'relationship'  => 'oneToOne',
                    'type'          => 'inline',
                ],
            ],
            'inline oneToMany'                            => [
                [
                    'foreign_field' => 'uid_parent',
                    'foreign_table' => 'tx_foundation_test_foreign',
                    'relationship'  => 'oneToMany',
                    'type'          => 'inline',
                ],
            ],
            'inline manyToOne'                            => [
                [
                    'foreign_field' => 'uid_parent',
                    'foreign_table' => 'tx_foundation_test_foreign',
                    'relationship'  => 'manyToOne',
                    'type'          => 'inline',
                ],
            ],
            'inline manyToMany with MM'                   => [
                [
                    'MM'            => 'tx_foundation_test_mm',
                    'foreign_table' => 'tx_foundation_test_foreign',
                    'relationship'  => 'manyToMany',
                    'type'          => 'inline',
                ],
            ],
            'file oneToOne'                               => [
                [
                    'relationship' => 'oneToOne',
                    'type'         => 'file',
                ],
            ],
            'file manyToOne'                              => [
                [
                    'relationship' => 'manyToOne',
                    'type'         => 'file',
                ],
            ],
            'file oneToMany'                              => [
                [
                    'relationship' => 'oneToMany',
                    'type'         => 'file',
                ],
            ],
            'folder oneToOne'                             => [
                [
                    'relationship' => 'oneToOne',
                    'type'         => 'folder',
                ],
            ],
            'folder manyToOne'                            => [
                [
                    'relationship' => 'manyToOne',
                    'type'         => 'folder',
                ],
            ],
            'folder oneToMany'                            => [
                [
                    'relationship' => 'oneToMany',
                    'type'         => 'folder',
                ],
            ],
            'group manyToOne'                             => [
                [
                    'allowed'      => 'tx_foundation_test_foreign',
                    'relationship' => 'manyToOne',
                    'type'         => 'group',
                ],
            ],
            'group oneToMany'                             => [
                [
                    'allowed'      => 'tx_foundation_test_foreign',
                    'relationship' => 'oneToMany',
                    'type'         => 'group',
                ],
            ],
            'group manyToMany with MM'                    => [
                [
                    'MM'           => 'tx_foundation_test_mm',
                    'allowed'      => 'tx_foundation_test_foreign',
                    'relationship' => 'manyToMany',
                    'type'         => 'group',
                ],
            ],
            'unrelated type with relationship is ignored' => [
                [
                    'relationship' => 'manyToMany',
                    'type'         => 'input',
                ],
            ],
            'missing relationship is ignored'             => [
                [
                    'foreign_table' => 'tx_foundation_test_foreign',
                    'renderType'    => 'selectSingle',
                    'type'          => 'select',
                ],
            ],
        ];
    }

    #[Test]
    public function validateAcceptsBothSortbySettingsButNotTogether(): void
    {
        $GLOBALS['TCA'][self::TABLE_NAME] = [
            'ctrl' => [
                'sortby'         => 'sorting',
                'default_sortby' => 'uid ASC',
            ],
        ];

        $this->expectException(MisconfiguredTcaException::class);
        $this->expectExceptionCode(1541107594);

        (new Validator())->validate(self::TABLE_NAME);
    }

    /**
     * @param array<string, mixed> $columnConfig
     *
     * @throws MisconfiguredTcaException
     */
    #[Test]
    #[DataProvider('validRelationshipCombinations')]
    public function validateAcceptsValidRelationships(array $columnConfig): void
    {
        $this->setColumnTca($columnConfig);

        (new Validator())->validate(self::TABLE_NAME);

        // The validator must not alter the column configuration:
        self::assertSame($columnConfig, $GLOBALS['TCA'][self::TABLE_NAME]['columns'][self::COLUMN_NAME]['config']);
    }

    /**
     * @param array<string, mixed> $columnConfig
     *
     * @throws MisconfiguredTcaException
     */
    #[Test]
    #[DataProvider('invalidRelationshipCombinations')]
    public function validateRejectsInvalidRelationships(array $columnConfig, string $messagePattern, int $code): void
    {
        $this->setColumnTca($columnConfig);

        $this->expectException(MisconfiguredTcaException::class);
        $this->expectExceptionCode($code);
        $this->expectExceptionMessageMatches($messagePattern);

        (new Validator())->validate(self::TABLE_NAME);
    }

    #[Test]
    public function validateRejectsSortbyOnProtectedColumn(): void
    {
        $GLOBALS['TCA'][self::TABLE_NAME] = [
            'ctrl' => [
                'sortby' => 'pid',
            ],
        ];

        $this->expectException(MisconfiguredTcaException::class);
        $this->expectExceptionCode(1541107601);

        (new Validator())->validate(self::TABLE_NAME);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TCA'][self::TABLE_NAME]);
        parent::tearDown();
    }

    /**
     * @param array<string, mixed> $columnConfig
     */
    private function setColumnTca(array $columnConfig): void
    {
        $GLOBALS['TCA'][self::TABLE_NAME] = [
            'columns' => [
                self::COLUMN_NAME => [
                    'config' => $columnConfig,
                ],
            ],
        ];
    }
}
