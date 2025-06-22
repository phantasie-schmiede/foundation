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
 * Class Palette
 *
 * @link    https://docs.typo3.org/m/typo3/reference-tca/main/en-us/Palettes/Index.html
 * @package PSBits\Foundation\Attribute\TCA
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_CLASS)]
class Palette extends AbstractTcaAttribute
{
    public const SPECIAL_FIELDS = [
        'LINE_BREAK' => '--linebreak--',
    ];

    // These values can be used by attributes of type AbstractColumnAttribute, if placed inside a palette.
    public const SPECIAL_POSITIONS = [
        'NEW_LINE_AFTER'  => 'newLineAfter',
        'NEW_LINE_BEFORE' => 'newLineBefore',
    ];

    /**
     * @param string $description https://docs.typo3.org/m/typo3/reference-tca/main/en-us/Palettes/Properties/Description.html
     * @param string $identifier
     * @param string $label       https://docs.typo3.org/m/typo3/reference-tca/main/en-us/Palettes/Properties/Label.html
     * @param string $position
     */
    public function __construct(
        protected string $description = '',
        protected string $identifier = '',
        protected string $label = '',
        /**
         * Usage: 'key:propertyName'
         * You can use the keys 'after', 'before', 'replace' and 'tab'.
         */
        protected string $position = '',
    ) {
        parent::__construct();
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getLabel(): ?string
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
