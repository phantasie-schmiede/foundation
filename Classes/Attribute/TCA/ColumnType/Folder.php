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
use PSBits\Foundation\Enum\Relationship;
use PSBits\Foundation\Utility\Database\DefinitionUtility;

/**
 * Class Folder
 *
 * @package PSBits\Foundation\Attribute\TCA\ColumnType
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Folder extends AbstractColumnType
{
    /**
     * @param int|null     $autoSizeMax               https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Folder/Index.html#confval-folder-autosizemax
     * @param array|null   $elementBrowserEntryPoints https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Folder/Index.html#confval-folder-elementbrowserentrypoints
     * @param bool         $hideDeleteIcon            https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Folder/Index.html#confval-folder-hidedeleteicon
     * @param bool         $hideMoveIcons             https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Folder/Index.html#confval-folder-hidemoveicons
     * @param int|null     $maxItems                  https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Folder/Index.html#confval-folder-maxitems
     * @param int|null     $minItems                  https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Folder/Index.html#confval-folder-minitems
     * @param bool         $multiple                  https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Folder/Index.html#confval-folder-multiple
     * @param Relationship $relationship              https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Folder/Index.html#confval-folder-relationship
     * @param int|null     $size                      https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Folder/Index.html#confval-folder-size
     */
    public function __construct(
        protected ?int         $autoSizeMax = null,
        protected ?array       $elementBrowserEntryPoints = null,
        protected ?bool        $hideDeleteIcon = null,
        protected ?bool        $hideMoveIcons = null,
        protected ?int         $maxItems = null,
        protected ?int         $minItems = null,
        protected ?bool        $multiple = null,
        protected Relationship $relationship = Relationship::manyToMany,
        protected ?int         $size = null,
    ) {
    }

    public function getAutoSizeMax(): ?int
    {
        return $this->autoSizeMax;
    }

    public function getDatabaseDefinition(): string
    {
        return DefinitionUtility::text();
    }

    public function getElementBrowserEntryPoints(): ?array
    {
        return $this->elementBrowserEntryPoints;
    }

    public function getMaxItems(): ?int
    {
        return $this->maxItems;
    }

    public function getMinItems(): ?int
    {
        return $this->minItems;
    }

    public function getRelationship(): string
    {
        return $this->relationship->value;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function isHideDeleteIcon(): ?bool
    {
        return $this->hideDeleteIcon;
    }

    public function isHideMoveIcons(): ?bool
    {
        return $this->hideMoveIcons;
    }

    public function isMultiple(): ?bool
    {
        return $this->multiple;
    }
}
