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
use PSBits\Foundation\Utility\Database\DefinitionUtility;

/**
 * Class Link
 *
 * @package PSBits\Foundation\Attribute\TCA\ColumnType
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Link extends AbstractColumnType
{
    /**
     * @param array|null $allowedTypes https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Link/Properties/AllowedTypes.html
     * @param bool       $autocomplete https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Link/Properties/Autocomplete.html
     * @param array|null $valuePicker  https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Link/Properties/ValuePicker.html
     */
    public function __construct(
        protected ?array $allowedTypes = null,
        protected bool   $autocomplete = false,
        protected ?array $valuePicker = null,
    ) {
    }

    public function getAllowedTypes(): ?array
    {
        return $this->allowedTypes;
    }

    public function getAutocomplete(): bool
    {
        return $this->autocomplete;
    }

    public function getDatabaseDefinition(): string
    {
        return DefinitionUtility::text();
    }

    public function getValuePicker(): ?array
    {
        if (null === $this->valuePicker) {
            return null;
        }

        return ['items' => $this->valuePicker];
    }
}
