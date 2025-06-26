<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Data;

/**
 * Class PageTypeConfiguration
 *
 * @package PSBits\Foundation\Data
 */
class PageTypeConfiguration
{
    /**
     * @param int         $doktype
     * @param string      $name
     * @param array       $allowedTables  If empty, all tables are allowed.
     * @param string|null $iconIdentifier Defaults to page-type-[your-page-type-name] if not set.
     * @param string|null $label          Defaults to
     *                                    EXT:[your_extension]/Resources/Private/Language/Backend/Configuration/TCA/Overrides/page.xlf:pageType.[yourPageTypeName]
     *                                    if not set. If that key doesn't exist, "name" will be transformed from
     *                                    "yourPageTypeName" to "Your page type name".
     */
    public function __construct(
        protected int     $doktype,
        protected string  $name,
        protected array   $allowedTables = [],
        protected ?string $iconIdentifier = null,
        protected ?string $label = null,
    ) {
    }

    public function getAllowedTables(): array
    {
        return $this->allowedTables;
    }

    public function getDoktype(): int
    {
        return $this->doktype;
    }

    public function getIconIdentifier(): ?string
    {
        return $this->iconIdentifier;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
