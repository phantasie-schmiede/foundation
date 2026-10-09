<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Service\Configuration\Tca;

/**
 * Class DefaultFieldDefinition
 *
 * A default showitem entry (tab, palette or field) that is added to the showitems of all types after the
 * position-dependencies of the columns have been resolved. The definitions can be used as position references.
 *
 * @package PSBits\Foundation\Service\Configuration\Tca
 */
final readonly class DefaultFieldDefinition
{
    public const string BLOCK_ACCESS   = 'access';
    public const string BLOCK_LANGUAGE = 'language';
    public const string TYPE_FIELD     = 'field';
    public const string TYPE_PALETTE   = 'palette';
    public const string TYPE_TAB       = 'tab';

    /*
     * The language tab and the language palette share the identifier "language", so the palette gets its own
     * reference name.
     */
    public const string REFERENCE_LANGUAGE_PALETTE = 'languagePalette';

    /**
     * @param string $reference  The reference name that can be used in position specifications
     *                           (e.g. "language" or "timeRestriction").
     * @param string $block      The block this field belongs to. A block is materialized as a whole.
     * @param string $type       One of the TYPE_* constants.
     * @param string $identifier The tab identifier, palette identifier or column name.
     * @param string $label      The resolved label of the tab (required for TYPE_TAB).
     */
    public function __construct(
        private string $reference,
        private string $block,
        private string $type,
        private string $identifier,
        private string $label = '',
    ) {
    }

    public function getBlock(): string
    {
        return $this->block;
    }

    /**
     * Returns the canonical showitem item of the defined field (e.g. "--palette--;;language").
     */
    public function getItem(): string
    {
        return match ($this->type) {
            self::TYPE_PALETTE => '--palette--;;' . $this->identifier,
            self::TYPE_TAB     => '--div--;' . $this->label,
            default            => $this->identifier,
        };
    }

    public function getReference(): string
    {
        return $this->reference;
    }
}
