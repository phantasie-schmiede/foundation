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
use PSBits\Foundation\Attribute\TCA\Ctrl;
use PSBits\Foundation\Service\Configuration\Tca\CtrlInitializer;
use PSBits\Foundation\Service\Configuration\Tca\TcaTable;
use ReflectionClass;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class CtrlInitializerTest
 *
 * @package PSBits\Foundation\Tests\Unit\Service\Configuration\Tca
 */
class CtrlInitializerTest extends UnitTestCase
{
    private const string TABLE_NAME = 'tx_ctrl_initializer_test';

    #[Test]
    public function applyInOverrideModeDisablesCoreFieldsOfInactiveGroups(): void
    {
        $this->setBaseTca();

        $initializer = new CtrlInitializer(new TcaTable(self::TABLE_NAME));
        $initializer->apply(
            new Ctrl(coreFields: 'none', label: 'foo'),
            true,
            new ReflectionClass(
                CoreFieldsNoneCtrlModel::class
            )
        );

        $ctrl = $GLOBALS['TCA'][self::TABLE_NAME]['ctrl'];

        self::assertSame('foo', $ctrl['label']);
        self::assertNull($ctrl['enablecolumns']);
        self::assertNull($ctrl['languageField']);
        self::assertNull($ctrl['transOrigDiffSourceField']);
        self::assertNull($ctrl['transOrigPointerField']);
        self::assertNull($ctrl['translationSource']);
        self::assertNull($ctrl['crdate']);
        self::assertNull($ctrl['tstamp']);

        // The coreFields parameter itself is not a ctrl property:
        self::assertArrayNotHasKey(Ctrl::CORE_FIELDS_PARAMETER, $ctrl);
    }

    #[Test]
    public function applyInOverrideModeKeepsExplicitArguments(): void
    {
        $this->setBaseTca();

        $initializer = new CtrlInitializer(new TcaTable(self::TABLE_NAME));
        $initializer->apply(
            new Ctrl(coreFields: 'none', crdate: 'crdate'),
            true,
            new ReflectionClass(
                CoreFieldsNoneWithCrdateCtrlModel::class
            )
        );

        self::assertSame('crdate', $GLOBALS['TCA'][self::TABLE_NAME]['ctrl']['crdate']);
        self::assertNull($GLOBALS['TCA'][self::TABLE_NAME]['ctrl']['tstamp']);
        self::assertNull($GLOBALS['TCA'][self::TABLE_NAME]['ctrl']['enablecolumns']);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TCA'][self::TABLE_NAME]);
        parent::tearDown();
    }

    private function setBaseTca(): void
    {
        $GLOBALS['TCA'][self::TABLE_NAME] = [
            'columns' => [],
            'ctrl'    => [],
            'types'   => [
                '0' => [
                    'showitem' => '',
                ],
            ],
        ];
    }
}

/**
 * @internal
 */
#[Ctrl(coreFields: 'none', label: 'foo')]
class CoreFieldsNoneCtrlModel
{
}

/**
 * @internal
 */
#[Ctrl(coreFields: 'none', crdate: 'crdate')]
class CoreFieldsNoneWithCrdateCtrlModel
{
}
