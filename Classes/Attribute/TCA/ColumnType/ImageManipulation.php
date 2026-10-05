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
use PSBits\Foundation\Service\Configuration\TcaService;
use PSBits\Foundation\Utility\Database\DefinitionUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ImageManipulation
 *
 * @package PSBits\Foundation\Attribute\TCA\ColumnType
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class ImageManipulation extends AbstractColumnType
{
    protected TcaService $tcaService;

    /**
     * @param string|null $allowedExtensions https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/ImageManipulation/Index.html#confval-imagemanipulation-allowedextensions
     * @param array|null  $cropVariants      https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/ImageManipulation/Index.html#confval-imagemanipulation-cropvariants
     * @param string      $fileField         https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/ImageManipulation/Index.html#confval-imagemanipulation-file-field
     *                                       You can use the property name. It will be converted to the column name
     *                                       automatically.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function __construct(
        protected ?string $allowedExtensions = null,
        protected ?array  $cropVariants = null,
        protected string  $fileField = '',
    ) {
        $this->tcaService = GeneralUtility::makeInstance(TcaService::class);
    }

    public function getAllowedExtensions(): ?string
    {
        return $this->allowedExtensions;
    }

    public function getCropVariants(): ?array
    {
        return $this->cropVariants;
    }

    public function getDatabaseDefinition(): string
    {
        return DefinitionUtility::int(unsigned: true);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function getFileField(): ?string
    {
        if ('' === $this->fileField) {
            return null;
        }

        return $this->tcaService->convertPropertyNameToColumnName($this->fileField);
    }
}
