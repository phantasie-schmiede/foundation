<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Service\Configuration;

use JsonException;
use PSBits\Foundation\Exceptions\ImplementationException;
use PSBits\Foundation\Exceptions\MisconfiguredTcaException;
use PSBits\Foundation\Service\Configuration\Tca\Builder;
use PSBits\Foundation\Service\Configuration\Tca\NameResolver;
use PSBits\Foundation\Service\Configuration\Tca\TcaTable;
use PSBits\Foundation\Service\ExtensionInformationService;
use PSBits\Foundation\Utility\Configuration\TcaUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use RuntimeException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

use function get_class;

/**
 * Class TcaService
 *
 * Public API for the TCA generation from domain model attributes. The actual work is done by the classes of the
 * PSBits\Foundation\Service\Configuration\Tca namespace.
 *
 * @package PSBits\Foundation\Service\Configuration
 */
class TcaService
{
    public const array  PALETTE_IDENTIFIERS = TcaUtility::CORE_PALETTE_IDENTIFIERS;
    public const string UNSET_KEYWORD       = TcaTable::UNSET_KEYWORD;

    protected string                $defaultLabelPath = '';
    protected readonly NameResolver $nameResolver;
    protected string                $tableName = '';

    public function __construct(
        protected readonly ExtensionInformationService $extensionInformationService,
        protected readonly PackageManager              $packageManager,
    ) {
        $this->nameResolver = new NameResolver($extensionInformationService, $packageManager);
    }

    public function addColumnConfiguration(string $columnName, array $columnConfiguration): void
    {
        $this->getTcaTable()
            ->addColumnConfiguration($columnName, $columnConfiguration);
    }

    public function addToPalette(string $identifier, array $fieldNames, string $position = ''): void
    {
        $this->getTcaTable()
            ->addFieldsToPalette($identifier, $fieldNames, $position);
    }

    /**
     * This function will be executed when the core builds the TCA, but as it does not return an array there will be no
     * entry for the required file, instead this function expands the TCA on its own by scanning through the domain
     * models of all registered extensions (extensions which provide an ExtensionInformation class, see
     * \PSBits\Foundation\Data\ExtensionInformationInterface).
     * Transient domain models (those without a corresponding table in the database) will be skipped.
     *
     * @param bool $overrideMode If set to false, the configuration of all original domain models (not extending other
     *                           domain models) is added to the TCA.
     *                           If set to true, the configuration of all extending domain models is added to the TCA.
     *
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws ImplementationException
     * @throws JsonException
     * @throws MisconfiguredTcaException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws RuntimeException
     */
    public function buildTca(bool $overrideMode): void
    {
        $builder = new Builder($this->nameResolver);
        $builder->build($overrideMode);
    }

    public function checkIfTableNameIsSet(): void
    {
        if ('' === $this->tableName) {
            throw new RuntimeException(__CLASS__ . ': You have to specify a table with setTable() first!', 1646899798);
        }
    }

    /**
     * Checks the ClassesConfiguration of TYPO3 if already available (it is not during bootstrapping). Otherwise, the
     * value from mapping attributes is returned. As last fallback, the class name is converted according to the
     * naming convention.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function convertClassNameToTableName(string $className): string
    {
        return $this->nameResolver->convertClassNameToTableName($className);
    }

    /**
     * Checks the ClassesConfiguration of TYPO3 if already available (it is not during bootstrapping). Otherwise, the
     * value from mapping attributes is returned. As last fallback, the property name is converted according to the
     * naming convention.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function convertPropertyNameToColumnName(string $propertyName, ?string $className = null): string
    {
        return $this->nameResolver->convertPropertyNameToColumnName($propertyName, $className);
    }

    /**
     * This uses the internal mapping of class names to table names to do a reverse lookup.
     * The result is an array of class names which are mapped to the given table name.
     * If no class is found, an empty array is returned.
     *
     * @throws ContainerExceptionInterface
     * @throws ImplementationException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function convertTableNameToClassNames(string $tableName): array
    {
        return $this->nameResolver->convertTableNameToClassNames($tableName);
    }

    /**
     * An existing palette with given identifier would be overwritten!
     *
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function createPalette(
        string $identifier,
        string $label = '',
        string $description = '',
        bool   $isHiddenPalette = false,
    ): void {
        $this->getTcaTable()
            ->createPalette($identifier, $label, $description, $isHiddenPalette, $this->defaultLabelPath);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ImplementationException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function getClassesTableMapping(): array
    {
        return $this->nameResolver->getClassesTableMapping();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function getConfigurationForPropertyOfDomainModel(AbstractEntity $domainModel, string $property): array
    {
        $tableName = $this->convertClassNameToTableName(get_class($domainModel));
        $column    = $this->convertPropertyNameToColumnName($property);

        return (new TcaTable($tableName))->getColumnConfiguration($column);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function setTableName(string $classOrTableName): void
    {
        if (str_contains($classOrTableName, '\\')) {
            $classOrTableName = $this->nameResolver->convertClassNameToTableName($classOrTableName);
        }

        $this->tableName = $classOrTableName;
    }

    private function getTcaTable(): TcaTable
    {
        $this->checkIfTableNameIsSet();

        return new TcaTable($this->tableName);
    }
}
