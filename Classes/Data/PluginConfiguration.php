<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Data;

use PSBits\Foundation\Enum\ContentType;

/**
 * Class PluginConfiguration
 *
 * @package PSBits\Foundation\Data
 */
class PluginConfiguration
{
    public function __construct(
        protected string      $name,
        protected bool        $addToElementWizard = true,
        protected array       $controllers = [],
        protected string      $flexForm = '',
        protected string      $group = '',
        protected string      $iconIdentifier = '',
        protected string      $title = '',
        protected int         $typeNum = 0,
        protected bool        $typeNumCacheable = false,
        protected ContentType $typeNumContentType = ContentType::HTML,
        protected bool        $typeNumDisableAllHeaderCode = true,
    ) {
    }

    public function getControllers(): array
    {
        return $this->controllers;
    }

    public function getFlexForm(): string
    {
        return $this->flexForm;
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    public function getIconIdentifier(): string
    {
        return $this->iconIdentifier;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getTypeNum(): int
    {
        return $this->typeNum;
    }

    public function getTypeNumContentType(): ContentType
    {
        return $this->typeNumContentType;
    }

    public function isAddToElementWizard(): bool
    {
        return $this->addToElementWizard;
    }

    public function isTypeNumCacheable(): bool
    {
        return $this->typeNumCacheable;
    }

    public function isTypeNumDisableAllHeaderCode(): bool
    {
        return $this->typeNumDisableAllHeaderCode;
    }
}
