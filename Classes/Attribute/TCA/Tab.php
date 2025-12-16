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
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Tab
 *
 * @package PSBits\Foundation\Attribute\TCA
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_CLASS)]
class Tab extends AbstractTcaAttribute
{
    public function __construct(
        // The identifier has to be written in snake_case!
        protected string $identifier = '',
        protected string $label = '',

        /**
         * Usage: 'key:propertyName'
         * You can use the keys 'after', 'before' and 'replace'.
         */
        protected string $position = '',
    ) {
        parent::__construct();
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
        if (empty($this->position)) {
            return '';
        }

        [
            $key,
            $location,
        ] = GeneralUtility::trimExplode(':', $this->position, false, 2);

        // Check if $location is NOT a palette name.
        if (!str_contains($location, '-')) {
            $location = $this->tcaService->convertPropertyNameToColumnName($location);
        }

        return $key . ':' . $location;
    }
}
