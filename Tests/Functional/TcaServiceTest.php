<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use PSBits\Foundation\Service\Configuration\TcaService;
use PSBits\Foundation\Tests\Examples\Domain\Model\AllTcaAttributesModel;
use ReflectionException;
use ReflectionMethod;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class TcaServiceTest extends FunctionalTestCase
{
    private const string TABLE_NAME = 'tx_foundation_all_tca_attributes';

    protected array $testExtensionsToLoad = [
        'typo3conf/ext/psbits/foundation',
    ];

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function buildFromAttributesCreatesExpectedTcaForExampleModelWithAllAttributes(): void
    {
        unset($GLOBALS['TCA'][self::TABLE_NAME]);

        $tcaService = GeneralUtility::makeInstance(TcaService::class);
        $tableName = $tcaService->convertClassNameToTableName(AllTcaAttributesModel::class);
        self::assertSame(self::TABLE_NAME, $tableName);
        $tcaService->setTableName($tableName);

        $buildFromAttributesMethod = new ReflectionMethod(TcaService::class, 'buildFromAttributes');
        $buildFromAttributesMethod->setAccessible(true);
        $buildFromAttributesMethod->invoke($tcaService, AllTcaAttributesModel::class, false);

        $baseLabelPath = 'LLL:EXT:foundation/Resources/Private/Language/Backend/Configuration/TCA/allTcaAttributesModel.xlf:';
        $expectedTcaSubset = [
            'ctrl'     => [
                'label'         => 'mapped_field',
                'search_fields' => 'mapped_field, text_field',
                'title'         => $baseLabelPath . 'ctrl.title',
            ],
            'types'    => [
                0 => [
                    'showitem' => '--palette--;;main_palette,--div--;Extra Tab,text_field,number_field,check_field,select_field,group_field,inline_field,category_field,file_field,datetime_field,link_field,slug_field,color_field,enum_field,pass_through_field,user_field',
                ],
            ],
            'palettes' => [
                'main_palette' => [
                    'showitem' => 'mapped_field',
                ],
            ],
            'columns'  => [
                'mapped_field'      => [
                    'label'  => $baseLabelPath . 'mappedField',
                    'config' => [
                        'type' => 'input',
                        'eval' => 'trim',
                    ],
                ],
                'text_field'        => [
                    'label'  => $baseLabelPath . 'textField',
                    'config' => [
                        'type' => 'text',
                        'eval' => 'trim',
                    ],
                ],
                'number_field'      => [
                    'label'  => $baseLabelPath . 'numberField',
                    'config' => [
                        'type'   => 'number',
                        'format' => 'integer',
                    ],
                ],
                'check_field'       => [
                    'label'  => $baseLabelPath . 'checkField',
                    'config' => [
                        'type'               => 'check',
                        'cols'               => 1,
                        'invertStateDisplay' => false,
                    ],
                ],
                'select_field'      => [
                    'label'  => $baseLabelPath . 'selectField',
                    'config' => [
                        'type'       => 'select',
                        'renderType' => 'selectSingle',
                        'items'      => [
                            [
                                'label' => 'One',
                                'value' => 1,
                            ],
                            [
                                'label' => 'Two',
                                'value' => 2,
                            ],
                        ],
                    ],
                ],
                'group_field'       => [
                    'label'  => $baseLabelPath . 'groupField',
                    'config' => [
                        'type'          => 'group',
                        'foreign_table' => 'sys_category',
                    ],
                ],
                'inline_field'      => [
                    'label'  => $baseLabelPath . 'inlineField',
                    'config' => [
                        'type'          => 'inline',
                        'foreign_table' => 'sys_category',
                    ],
                ],
                'category_field'    => [
                    'label'  => $baseLabelPath . 'categoryField',
                    'config' => [
                        'type'         => 'category',
                        'relationship' => 'manyToMany',
                    ],
                ],
                'file_field'        => [
                    'label'  => $baseLabelPath . 'fileField',
                    'config' => [
                        'type'    => 'file',
                        'allowed' => 'common-image-types',
                    ],
                ],
                'datetime_field'    => [
                    'label'  => $baseLabelPath . 'datetimeField',
                    'config' => [
                        'type'   => 'datetime',
                        'format' => 'datetime',
                    ],
                ],
                'link_field'        => [
                    'label'  => $baseLabelPath . 'linkField',
                    'config' => [
                        'type'         => 'link',
                        'autocomplete' => false,
                    ],
                ],
                'slug_field'        => [
                    'label'  => $baseLabelPath . 'slugField',
                    'config' => [
                        'type'             => 'slug',
                        'eval'             => 'uniqueInSite',
                        'fallbackCharacter' => '-',
                    ],
                ],
                'color_field'       => [
                    'label'  => $baseLabelPath . 'colorField',
                    'config' => [
                        'type'        => 'color',
                        'valuePicker' => [
                            'items' => [],
                        ],
                    ],
                ],
                'enum_field'        => [
                    'label'  => $baseLabelPath . 'enumField',
                    'config' => [
                        'type'  => 'select',
                        'items' => [
                            [
                                'label' => 'alpha',
                                'value' => 'alpha',
                            ],
                            [
                                'label' => 'beta',
                                'value' => 'beta',
                            ],
                        ],
                    ],
                ],
                'pass_through_field' => [
                    'label'  => $baseLabelPath . 'passThroughField',
                    'config' => [
                        'type' => 'passthrough',
                    ],
                ],
                'user_field'         => [
                    'label'  => $baseLabelPath . 'userField',
                    'config' => [
                        'type'       => 'user',
                        'renderType' => 'testUserRenderType',
                    ],
                ],
            ],
        ];

        $actualTca = $GLOBALS['TCA'][self::TABLE_NAME] ?? [];
        self::assertSame($expectedTcaSubset, $this->extractByExpectedStructure($actualTca, $expectedTcaSubset));
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TCA'][self::TABLE_NAME]);
        parent::tearDown();
    }

    private function extractByExpectedStructure(array $actual, array $expected): array
    {
        $result = [];

        foreach ($expected as $key => $value) {
            $actualValue = $actual[$key] ?? null;

            if (is_array($value)) {
                $result[$key] = is_array($actualValue) ? $this->extractByExpectedStructure($actualValue, $value) : [];
                continue;
            }

            $result[$key] = $actualValue;
        }

        return $result;
    }
}

