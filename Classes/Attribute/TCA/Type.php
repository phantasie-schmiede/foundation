<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Attribute\TCA;

use Attribute;

/**
 * Class Type
 *
 * Use this attribute to define a record type (types section) of the table. A table can have multiple record types.
 * The record type of a record is stored in the field configured via ctrl.type.
 *
 * @link    https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/Types/Index.html
 * @package PSBits\Foundation\Attribute\TCA
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_CLASS)]
class Type extends AbstractTcaAttribute
{
    /**
     * The individual arguments override the corresponding creationOptions keys.
     *
     * @param array|null $columnsOverrides  https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/Types/Index.html#confval-types-columnsoverrides
     * @param array|null  $creationOptions  https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/Types/Index.html#confval-types-creationoptions
     * @param array|null  $defaultValues    https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/Types/Index.html#confval-types-creationoptions-defaultvalues
     * @param string|null $previewRenderer  https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/Types/Index.html#confval-types-previewrenderer
     * @param int|string  $recordType       Key of the type entry; value of the record type field configured via
     *                                      https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/Ctrl/Index.html#confval-ctrl-type
     * @param bool|null   $saveAndClose     https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/Types/Index.html#confval-types-creationoptions-saveandclose
     * @param string      $showitem         https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/Types/Index.html#confval-types-showitem
     */
    public function __construct(
        protected ?array     $columnsOverrides = null,
        protected ?array     $creationOptions = null,
        protected ?array     $defaultValues = null,
        protected ?string    $previewRenderer = null,
        protected int|string $recordType = 0,
        protected ?bool      $saveAndClose = null,
        protected string     $showitem = '',
    ) {
    }

    public function getColumnsOverrides(): ?array
    {
        return $this->columnsOverrides;
    }

    public function getCreationOptions(): array
    {
        $creationOptions = $this->creationOptions ?? [];

        if (null !== $this->saveAndClose) {
            $creationOptions['saveAndClose'] = $this->saveAndClose;
        }

        if (null !== $this->defaultValues) {
            $creationOptions['defaultValues'] = $this->defaultValues;
        }

        return $creationOptions;
    }

    public function getDefaultValues(): ?array
    {
        return $this->defaultValues;
    }

    public function getPreviewRenderer(): ?string
    {
        return $this->previewRenderer;
    }

    public function getRecordType(): int|string
    {
        return $this->recordType;
    }

    public function getSaveAndClose(): ?bool
    {
        return $this->saveAndClose;
    }

    public function getShowitem(): string
    {
        return $this->showitem;
    }
}
