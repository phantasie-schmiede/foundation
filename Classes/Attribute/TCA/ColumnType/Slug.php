<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Attribute\TCA\ColumnType;

use Attribute;

/**
 * Class Slug
 *
 * @package PSBits\Foundation\Attribute\TCA\ColumnType
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Slug extends AbstractColumnType
{
    /**
     * @param string $eval              https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Slug/Properties/Eval.html
     * @param string $fallbackCharacter https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Slug/Properties/FallbackCharacter.html
     * @param array  $generatorOptions  https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Slug/Properties/GeneratorOptions.html
     */
    public function __construct(
        protected string $eval = 'uniqueInSite',
        protected string $fallbackCharacter = '-',
        protected array  $generatorOptions = [
            'fields'               => [
                'title',
                'nav_title',
            ],
            'fieldSeparator'       => '/',
            'prefixParentPageSlug' => true,
            'replacements'         => [
                '/' => '',
            ],
        ],
    ) {
    }

    /**
     * Database field for type datetime is added by TYPO3 automatically.
     */
    public function getDatabaseDefinition(): string
    {
        return '';
    }

    public function getEval(): string
    {
        return $this->eval;
    }

    public function getFallbackCharacter(): string
    {
        return $this->fallbackCharacter;
    }

    public function getGeneratorOptions(): array
    {
        return $this->generatorOptions;
    }
}
