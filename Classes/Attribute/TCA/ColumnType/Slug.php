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
     * The individual arguments override the corresponding generatorOptions keys.
     *
     * @param array|null $appearance        https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Slug/Index.html#confval-slug-appearance
     * @param string     $eval              https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Slug/Index.html#confval-slug-eval
     * @param string     $fallbackCharacter https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Slug/Index.html#confval-slug-fallbackcharacter
     * @param array|null $fields            https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Slug/Index.html#confval-slug-generatoroptions-fields
     * @param string     $fieldSeparator    https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Slug/Index.html#confval-slug-generatoroptions-fieldseparator
     * @param array      $generatorOptions  https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Slug/Index.html#confval-slug-generatoroptions
     * @param bool|null  $prefixParentPageSlug https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Slug/Index.html#confval-slug-generatoroptions-prefixparentpageslug
     * @param array|null $postModifiers     https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Slug/Index.html#confval-slug-generatoroptions-postmodifiers
     * @param bool|null  $prependSlash      https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Slug/Index.html#confval-slug-prependslash
     * @param array|null $replacements      https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Slug/Index.html#confval-slug-generatoroptions-replacements
     */
    public function __construct(
        protected ?array  $appearance = null,
        protected string  $eval = 'uniqueInSite',
        protected string  $fallbackCharacter = '-',
        protected ?array  $fields = null,
        protected ?string $fieldSeparator = null,
        protected array   $generatorOptions = [
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
        protected ?bool   $prefixParentPageSlug = null,
        protected ?array  $postModifiers = null,
        protected ?bool   $prependSlash = null,
        protected ?array  $replacements = null,
    ) {
    }

    /**
     * Database field for type slug is added by TYPO3 automatically.
     */
    public function getDatabaseDefinition(): string
    {
        return '';
    }

    public function getAppearance(): ?array
    {
        return $this->appearance;
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
        $generatorOptions = $this->generatorOptions;

        if (null !== $this->fields) {
            $generatorOptions['fields'] = $this->fields;
        }

        if (null !== $this->fieldSeparator) {
            $generatorOptions['fieldSeparator'] = $this->fieldSeparator;
        }

        if (null !== $this->prefixParentPageSlug) {
            $generatorOptions['prefixParentPageSlug'] = $this->prefixParentPageSlug;
        }

        if (null !== $this->postModifiers) {
            $generatorOptions['postModifiers'] = $this->postModifiers;
        }

        if (null !== $this->replacements) {
            $generatorOptions['replacements'] = $this->replacements;
        }

        return $generatorOptions;
    }

    public function getPrependSlash(): ?bool
    {
        return $this->prependSlash;
    }
}
