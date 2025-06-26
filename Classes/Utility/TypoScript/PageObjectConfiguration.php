<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Utility\TypoScript;

use PSBits\Foundation\Enum\ContentType;

/**
 * Class PageObjectConfiguration
 *
 * @package PSBits\Foundation\Utility\TypoScript
 */
class PageObjectConfiguration
{
    protected bool        $cacheable;
    protected ContentType $contentType          = ContentType::HTML;
    protected bool        $disableAllHeaderCode = true;
    protected string      $extensionName;
    protected string      $pluginName;
    protected array       $settings             = [];
    protected int         $typeNum;
    protected string      $typoScriptObjectName = '';
    protected string      $userFunc             = '';
    protected array       $userFuncParameters;
    protected string      $vendorName;

    public function getContentType(): ContentType
    {
        return $this->contentType;
    }

    public function getExtensionName(): ?string
    {
        return $this->extensionName;
    }

    public function getPluginName(): ?string
    {
        return $this->pluginName;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function getTypeNum(): int
    {
        return $this->typeNum;
    }

    public function getTypoScriptObjectName(): string
    {
        return $this->typoScriptObjectName;
    }

    public function getUserFunc(): ?string
    {
        return $this->userFunc;
    }

    public function getUserFuncParameters(): array
    {
        return $this->userFuncParameters ?? [];
    }

    public function getVendorName(): ?string
    {
        return $this->vendorName;
    }

    public function isCacheable(): ?bool
    {
        return $this->cacheable;
    }

    public function isDisableAllHeaderCode(): bool
    {
        return $this->disableAllHeaderCode;
    }

    public function setCacheable(bool $cacheable): void
    {
        $this->cacheable = $cacheable;
    }

    public function setContentType(ContentType $contentType): void
    {
        $this->contentType = $contentType;
    }

    public function setDisableAllHeaderCode(bool $disableAllHeaderCode): void
    {
        $this->disableAllHeaderCode = $disableAllHeaderCode;
    }

    public function setExtensionName(string $extensionName): self
    {
        $this->extensionName = $extensionName;

        return $this;
    }

    public function setPluginName(string $pluginName): self
    {
        $this->pluginName = $pluginName;

        return $this;
    }

    public function setSettings(array $settings): self
    {
        $this->settings = $settings;

        return $this;
    }

    public function setTypeNum(int $typeNum): self
    {
        $this->typeNum = $typeNum;

        return $this;
    }

    public function setTypoScriptObjectName(string $typoScriptObjectName): self
    {
        $this->typoScriptObjectName = $typoScriptObjectName;

        return $this;
    }

    public function setUserFunc(string $userFunc): void
    {
        $this->userFunc = $userFunc;
    }

    public function setUserFuncParameters(array $userFuncParameters): void
    {
        $this->userFuncParameters = $userFuncParameters;
    }

    public function setVendorName(string $vendorName): self
    {
        $this->vendorName = $vendorName;

        return $this;
    }
}
