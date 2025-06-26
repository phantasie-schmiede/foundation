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
use function is_array;

/**
 * Class Group
 *
 * @package PSBits\Foundation\Attribute\TCA\ColumnType
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Group extends AbstractColumnType
{
    protected TcaService $tcaService;

    /**
     * $mmOppositeUsage automatically populates $allowed if it's empty.
     *
     * @param string|null $allowed                         https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Group/Properties/Allowed.html
     * @param array|null  $elementBrowserEntryPoints       https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Group/Properties/ElementBrowserEntryPoints.html
     * @param string|null $foreignTable                    https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Group/Properties/ForeignTable.html
     * @param string      $linkedModel                     Instead of directly specifying a foreign table, it is
     *                                                     possible to specify a domain model class.
     * @param int|null    $maxItems                        https://docs.typo3.org/m/typo3/reference-tca/12.4/en-us/ColumnsConfig/CommonProperties/Maxitems.html
     * @param string|null $mm                              https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Group/Properties/Mm.html
     * @param array|null  $mmOppositeUsage                 https://docs.typo3.org/m/typo3/reference-tca/12.4/en-us/ColumnsConfig/Type/Group/Properties/Mm.html#confval-group-mm-opposite-usage
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function __construct(
        protected ?string $allowed = null,
        protected ?array  $elementBrowserEntryPoints = null,
        protected ?string $foreignTable = null,
        protected string  $linkedModel = '',
        protected ?int    $maxItems = null,
        protected ?string $mm = null,
        protected ?array  $mmOppositeUsage = null,
    ) {
        $this->tcaService = GeneralUtility::makeInstance(TcaService::class);

        if (class_exists($linkedModel)) {
            $this->foreignTable = $this->tcaService->convertClassNameToTableName($linkedModel);
        }

        if (!empty($mmOppositeUsage)) {
            $this->mmOppositeUsage = [];

            foreach ($mmOppositeUsage as $modelOrTableName => $fieldOrPropertyNames) {
                $this->mmOppositeUsage[$this->tcaService->convertClassNameToTableName($modelOrTableName)] = array_map(
                    fn(string $fieldOrPropertyName) => $this->tcaService->convertPropertyNameToColumnName(
                        $fieldOrPropertyName
                    ),
                    $fieldOrPropertyNames
                );
            }

            if (null === $this->allowed) {
                $this->allowed = implode(',', array_keys($this->mmOppositeUsage));
            }
        }
    }

    public function getAllowed(): ?string
    {
        return $this->allowed;
    }

    public function getDatabaseDefinition(): string
    {
        if (empty($this->mm)) {
            return DefinitionUtility::text();
        }

        return DefinitionUtility::int(unsigned: true);
    }

    public function getElementBrowserEntryPoints(): ?array
    {
        return $this->elementBrowserEntryPoints;
    }

    public function getForeignTable(): ?string
    {
        return $this->foreignTable;
    }

    public function getMaxItems(): ?int
    {
        return $this->maxItems;
    }

    public function getMm(): ?string
    {
        return $this->mm;
    }

    public function getMmOppositeUsage(): ?array
    {
        return $this->mmOppositeUsage;
    }
}
