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
use PSBits\Foundation\Enum\DateType;
use PSBits\Foundation\Utility\StringUtility;
use function is_string;

/**
 * Class Datetime
 *
 * @package PSBits\Foundation\Attribute\TCA\ColumnType
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Datetime extends AbstractColumnType
{
    /**
     * @param DateType|null         $dbType            https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Datetime/Properties/DbType.html
     * @param bool|null             $disableAgeDisplay https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Datetime/Properties/DisableAgeDisplay.html
     * @param DateType              $format            https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Datetime/Properties/Format.html
     * @param array                 $range             https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Datetime/Properties/Range.html
     * @param \DateTime|string|null $rangeLower
     * @param \DateTime|string|null $rangeUpper
     */
    public function __construct(
        protected ?DateType             $dbType = DateType::datetime,
        protected ?bool                 $disableAgeDisplay = null,
        protected DateType              $format = DateType::datetime,
        protected array                 $range = [],
        protected \DateTime|string|null $rangeLower = null,
        protected \DateTime|string|null $rangeUpper = null,
    ) {
        if (is_string($rangeLower)) {
            $this->rangeLower = StringUtility::convertToDateTime($this->rangeLower);
        }

        if (is_string($rangeUpper)) {
            $this->rangeUpper = StringUtility::convertToDateTime($this->rangeUpper);
        }
    }

    /**
     * Database field for type datetime is added by TYPO3 automatically.
     */
    public function getDatabaseDefinition(): string
    {
        return '';
    }

    public function getDbType(): ?string
    {
        return $this->dbType?->value;
    }

    public function getDisableAgeDisplay(): ?bool
    {
        return $this->disableAgeDisplay;
    }

    public function getFormat(): string
    {
        return $this->format->value;
    }

    public function getRange(): ?array
    {
        if (null !== $this->rangeLower) {
            $range['lower'] = $this->rangeLower;
        }

        if (null !== $this->rangeUpper) {
            $range['upper'] = $this->rangeUpper;
        }

        return $range ?? null;
    }
}
