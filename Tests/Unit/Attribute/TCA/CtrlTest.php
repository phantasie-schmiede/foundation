<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Tests\Unit\Attribute\TCA;

use Closure;
use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PSBits\Foundation\Attribute\TCA\Ctrl;
use ReflectionClass;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class CtrlTest
 *
 * @package PSBits\Foundation\Tests\Unit\Attribute\TCA
 */
class CtrlTest extends UnitTestCase
{
    public static function coreFieldGroupDataProvider(): Generator
    {
        yield 'only language fields remain' => [
            'language',
            static function(Ctrl $ctrl): array {
                return [
                    $ctrl->getLanguageField(),
                    $ctrl->getTransOrigPointerField(),
                    $ctrl->getTransOrigDiffSourceField(),
                    $ctrl->getTranslationSource(),
                    $ctrl->getEnableColumns(),
                    $ctrl->getCrdate(),
                    $ctrl->getTstamp(),
                ];
            },
            [
                'sys_language_uid',
                'l10n_parent',
                'l10n_diffsource',
                'l10n_source',
                null,
                null,
                null,
            ],
        ];
        yield 'only enable columns remain' => [
            'enableColumns',
            static function(Ctrl $ctrl): array {
                return [
                    $ctrl->getLanguageField(),
                    $ctrl->getTransOrigPointerField(),
                    $ctrl->getTransOrigDiffSourceField(),
                    $ctrl->getTranslationSource(),
                    $ctrl->getEnableColumns(),
                    $ctrl->getCrdate(),
                    $ctrl->getTstamp(),
                ];
            },
            [
                null,
                null,
                null,
                null,
                Ctrl::ENABLE_COLUMNS,
                null,
                null,
            ],
        ];
        yield 'only timestamps remain' => [
            'timestamps',
            static function(Ctrl $ctrl): array {
                return [
                    $ctrl->getLanguageField(),
                    $ctrl->getTransOrigPointerField(),
                    $ctrl->getTransOrigDiffSourceField(),
                    $ctrl->getTranslationSource(),
                    $ctrl->getEnableColumns(),
                    $ctrl->getCrdate(),
                    $ctrl->getTstamp(),
                ];
            },
            [
                null,
                null,
                null,
                null,
                null,
                'crdate',
                'tstamp',
            ],
        ];
        yield 'list of groups keeps the selected groups' => [
            [
                'language',
                'timestamps',
            ],
            static function(Ctrl $ctrl): array {
                return [
                    $ctrl->getLanguageField(),
                    $ctrl->getTransOrigPointerField(),
                    $ctrl->getTransOrigDiffSourceField(),
                    $ctrl->getTranslationSource(),
                    $ctrl->getEnableColumns(),
                    $ctrl->getCrdate(),
                    $ctrl->getTstamp(),
                ];
            },
            [
                'sys_language_uid',
                'l10n_parent',
                'l10n_diffsource',
                'l10n_source',
                null,
                'crdate',
                'tstamp',
            ],
        ];
    }

    #[Test]
    public function coreFieldGroupPropertiesMatchConstructorDefaults(): void
    {
        $constructorDefaults = [];

        foreach ((new ReflectionClass(Ctrl::class))->getConstructor()
                     ->getParameters() as $parameter) {
            $constructorDefaults[$parameter->getName()] = $parameter->isDefaultValueAvailable(
            ) ? $parameter->getDefaultValue() : null;
        }

        self::assertSame('sys_language_uid', $constructorDefaults['languageField']);
        self::assertSame('l10n_diffsource', $constructorDefaults['transOrigDiffSourceField']);
        self::assertSame('l10n_parent', $constructorDefaults['transOrigPointerField']);
        self::assertSame('l10n_source', $constructorDefaults['translationSource']);
        self::assertSame(Ctrl::ENABLE_COLUMNS, $constructorDefaults['enableColumns']);
        self::assertSame('crdate', $constructorDefaults['crdate']);
        self::assertSame('tstamp', $constructorDefaults['tstamp']);
    }

    #[Test]
    #[DataProvider('coreFieldGroupDataProvider')]
    public function coreFieldSelectionKeepsOnlySelectedGroups(
        string|array $coreFields,
        Closure      $actualValues,
        array        $expectedValues,
    ): void {
        $ctrl = new Ctrl(coreFields: $coreFields);

        self::assertSame($expectedValues, $actualValues($ctrl));
    }

    #[Test]
    public function coreFieldsNoneDisablesAllCoreFields(): void
    {
        $ctrl = new Ctrl(coreFields: 'none');

        self::assertNull($ctrl->getEnableColumns());
        self::assertNull($ctrl->getLanguageField());
        self::assertNull($ctrl->getTransOrigPointerField());
        self::assertNull($ctrl->getTransOrigDiffSourceField());
        self::assertNull($ctrl->getTranslationSource());
        self::assertNull($ctrl->getCrdate());
        self::assertNull($ctrl->getTstamp());

        // Fields outside of the core field groups keep their defaults:
        self::assertSame('deleted', $ctrl->getDelete());
        self::assertSame('t3_origuid', $ctrl->getOrigUid());
    }

    #[Test]
    public function coreFieldsParameterIsNotSerializedByObjectToArray(): void
    {
        /*
         * ObjectUtility::toArray() only serializes properties with a getter. coreFields must not have one,
         * otherwise it would end up as an invalid ctrl property.
         */
        $reflection = new ReflectionClass(Ctrl::class);

        self::assertFalse($reflection->hasMethod('get' . ucfirst(Ctrl::CORE_FIELDS_PARAMETER)));
        self::assertFalse($reflection->hasMethod('is' . ucfirst(Ctrl::CORE_FIELDS_PARAMETER)));
    }

    #[Test]
    public function defaultConfigurationKeepsAllCoreFields(): void
    {
        $ctrl = new Ctrl();

        self::assertSame(Ctrl::ENABLE_COLUMNS, $ctrl->getEnableColumns());
        self::assertSame('sys_language_uid', $ctrl->getLanguageField());
        self::assertSame('l10n_parent', $ctrl->getTransOrigPointerField());
        self::assertSame('l10n_diffsource', $ctrl->getTransOrigDiffSourceField());
        self::assertSame('l10n_source', $ctrl->getTranslationSource());
        self::assertSame('crdate', $ctrl->getCrdate());
        self::assertSame('tstamp', $ctrl->getTstamp());

        // Fields outside of the core field groups:
        self::assertSame('deleted', $ctrl->getDelete());
        self::assertSame('t3_origuid', $ctrl->getOrigUid());
    }

    #[Test]
    public function explicitArgumentsTakePrecedenceOverCoreFieldSelection(): void
    {
        $ctrl = new Ctrl(
            coreFields: 'none',
            enableColumns: [
                'starttime' => 'starttime',
            ]
        );

        self::assertSame([
            'starttime' => 'starttime',
        ], $ctrl->getEnableColumns());
        self::assertNull($ctrl->getLanguageField());
        self::assertNull($ctrl->getCrdate());

        $ctrl = new Ctrl(coreFields: 'none', crdate: null);

        self::assertNull($ctrl->getCrdate());
        self::assertNull($ctrl->getTstamp());
    }

    #[Test]
    public function explicitNullValueIsKept(): void
    {
        $ctrl = new Ctrl(crdate: null);

        self::assertNull($ctrl->getCrdate());
        self::assertSame('tstamp', $ctrl->getTstamp());

        $ctrl = new Ctrl(
            coreFields: 'all',
            tstamp: null
        );

        self::assertNull($ctrl->getTstamp());
        self::assertSame('crdate', $ctrl->getCrdate());
    }

    #[Test]
    public function resolveActiveCoreFieldGroupsReturnsExpectedGroups(): void
    {
        self::assertSame([
            'language',
            'enableColumns',
            'timestamps',
        ], Ctrl::resolveActiveCoreFieldGroups('all'));
        self::assertSame([], Ctrl::resolveActiveCoreFieldGroups('none'));
        self::assertSame([
            'language',
        ], Ctrl::resolveActiveCoreFieldGroups('language'));
        self::assertSame(
            [
            'language',
            'timestamps',
        ],
            Ctrl::resolveActiveCoreFieldGroups([
                'language',
                'timestamps',
                'language',
            ])
        );
    }

    #[Test]
    public function unknownCoreFieldGroupInListThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Unknown core field group "unknownGroup"/');

        new Ctrl(coreFields: [
            'language',
            'unknownGroup',
        ]);
    }

    #[Test]
    public function unknownCoreFieldGroupThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Unknown core field group "unknownGroup"/');

        new Ctrl(coreFields: 'unknownGroup');
    }
}
