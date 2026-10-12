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
use PSBits\Foundation\Service\Configuration\Tca\Position;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;

/**
 * Class Tab
 *
 * @package PSBits\Foundation\Attribute\TCA
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_CLASS)]
class Tab extends AbstractTcaAttribute
{
    /**
     * @param string $identifier The tab identifier has to be written in snake_case.
     *                           There is no TCA option for tabs, they are rendered as "--div--" entries in showitem.
     * @param string $label
     * @param string $position
     */
    public function __construct(
        protected string $identifier = '',
        protected string $label = '',
        /**
         * Usage: 'key:propertyName'
         * You can use the keys 'after', 'before' and 'replace'.
         */
        protected string $position = '',
    ) {
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function getPosition(): string
    {
        return Position::normalize(
            $this->position,
            fn(string $name): string => $this->tcaService()
                ->convertPropertyNameToColumnName($name)
        );
    }
}
