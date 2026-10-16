<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Utility\Xml;

/**
 * Interface XmlElementInterface
 *
 * @package PSBits\Foundation\Utility\Xml
 */
interface XmlElementInterface
{
    public static function getTagName(): string;

    /**
     * @return array<string, string>
     */
    public function _getAttributes(): array;

    public function _getNodeValue(): mixed;

    public function _getPosition(): ?int;

    /**
     * @param array<string, string> $attributes
     */
    public function _setAttributes(array $attributes): void;

    public function _setNodeValue(mixed $nodeValue): void;

    public function _setPosition(int $position): void;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
