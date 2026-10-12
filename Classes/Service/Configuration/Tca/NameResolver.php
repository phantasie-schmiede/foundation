<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Service\Configuration\Tca;

use PSBits\Foundation\Attribute\TCA\Mapping\Field;
use PSBits\Foundation\Attribute\TCA\Mapping\Table;
use PSBits\Foundation\Exceptions\ImplementationException;
use PSBits\Foundation\Service\ExtensionInformationService;
use PSBits\Foundation\Utility\ArrayUtility;
use PSBits\Foundation\Utility\ReflectionUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\ArrayUtility as Typo3ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\DomainObject\AbstractValueObject;
use TYPO3\CMS\Extbase\Persistence\ClassesConfiguration;

use function array_replace_recursive;
use function array_slice;
use function in_array;
use function is_array;

/**
 * Class NameResolver
 *
 * @package PSBits\Foundation\Service\Configuration\Tca
 */
class NameResolver
{
    protected const array  CLASS_TABLE_MAPPING_KEYS = [
        'TCA_OVERRIDES' => 'tcaOverrides',
        'TCA'           => 'tca',
    ];

    protected static bool           $allowCaching         = true;
    protected static array          $classTableMapping    = [];
    protected ?ClassesConfiguration $classesConfiguration = null;

    public function __construct(
        protected readonly ExtensionInformationService $extensionInformationService,
        protected readonly PackageManager              $packageManager,
    ) {
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
        $this->checkClassesConfiguration();

        if ($this->classesConfiguration->hasClass($className)) {
            $configuration = $this->classesConfiguration->getConfigurationFor($className);

            if (!empty($configuration['tableName'])) {
                return $configuration['tableName'];
            }
        }

        $tableMapping = ReflectionUtility::getAttributeInstance(Table::class, $className);

        if ($tableMapping instanceof Table) {
            return $tableMapping->getName();
        }

        $classNameParts = explode('\\', $className);

        // Skip vendor and product name for core classes
        if (str_starts_with($className, 'TYPO3\\CMS\\')) {
            $classPartsToSkip = 2;
        } else {
            $classPartsToSkip = 1;
        }

        return 'tx_' . strtolower(implode('_', array_slice($classNameParts, $classPartsToSkip)));
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
        if (!empty($className)) {
            $this->checkClassesConfiguration();

            if ($this->classesConfiguration->hasClass($className)) {
                $configuration = $this->classesConfiguration->getConfigurationFor($className);

                if (!empty($configuration['properties'][$propertyName]['fieldName'])) {
                    return $configuration['properties'][$propertyName]['fieldName'];
                }
            }

            $propertyReflection = new ReflectionProperty($className, $propertyName);
            $fieldMapping       = ReflectionUtility::getAttributeInstance(Field::class, $propertyReflection);

            if ($fieldMapping instanceof Field) {
                return $fieldMapping->getName();
            }
        }

        return GeneralUtility::camelCaseToLowerCaseUnderscored($propertyName);
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
        $searchResults = ArrayUtility::inArrayRecursive(
            $this->getClassesTableMapping(),
            $tableName
        );

        if (!empty($searchResults)) {
            // Explode each result path and keep only last part.
            return array_map(static function($item) {
                $arrayPathParts = explode('.', $item);

                return array_pop($arrayPathParts);
            }, $searchResults);
        }

        return [];
    }

    /**
     * @return array<string, array<string, string>>
     * @throws ContainerExceptionInterface
     * @throws ImplementationException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function getClassesTableMapping(): array
    {
        if (false === self::$allowCaching || empty(self::$classTableMapping)) {
            $this->buildClassesTableMapping();
        }

        return self::$classTableMapping;
    }

    /**
     * Returns the mapping of class names to table names for the given build mode.
     *
     * @param bool $overrideMode If set to true, the mapping of extending domain models is returned, otherwise the
     *                           mapping of all original domain models.
     *
     * @return array<string, string> An array of class names as keys and table names as values.
     * @throws ContainerExceptionInterface
     * @throws ImplementationException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function getMappedClassNames(bool $overrideMode): array
    {
        if ($overrideMode) {
            $key = self::CLASS_TABLE_MAPPING_KEYS['TCA_OVERRIDES'];
        } else {
            $key = self::CLASS_TABLE_MAPPING_KEYS['TCA'];
        }

        return $this->getClassesTableMapping()[$key] ?? [];
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ImplementationException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    protected function buildClassesTableMapping(): void
    {
        self::$classTableMapping = [];
        $allExtensionInformation = $this->extensionInformationService->getAllExtensionInformation();

        foreach ($allExtensionInformation as $extensionInformation) {
            $classNames = $this->extensionInformationService->getDomainModelClassNames($extensionInformation);

            foreach ($classNames as $className) {
                $reflectionClass = new ReflectionClass($className);

                if ($reflectionClass->isAbstract() || $reflectionClass->isInterface()) {
                    continue;
                }

                $tableName = $this->convertClassNameToTableName($className);

                if (str_starts_with($tableName, 'tx_' . mb_strtolower($extensionInformation->getExtensionName()))) {
                    self::$classTableMapping[self::CLASS_TABLE_MAPPING_KEYS['TCA']][$className] = $tableName;
                } else {
                    self::$classTableMapping[self::CLASS_TABLE_MAPPING_KEYS['TCA_OVERRIDES']][$className] = $tableName;
                }
            }
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    private function checkClassesConfiguration(): void
    {
        if (null === $this->classesConfiguration) {
            /*
             * Copied from TYPO3\CMS\Extbase\Persistence\ClassesConfigurationFactory because instantiation of that class
             * would throw an exception (e.g. CacheManager not available, dependency injection not ready).
             */
            $classes = [];

            foreach ($this->packageManager->getActivePackages() as $activePackage) {
                $persistenceClassesFile = $activePackage->getPackagePath(
                ) . 'Configuration/Extbase/Persistence/Classes.php';

                if (file_exists($persistenceClassesFile)) {
                    $definedClasses = require $persistenceClassesFile;

                    if (is_array($definedClasses)) {
                        Typo3ArrayUtility::mergeRecursiveWithOverrule(
                            $classes,
                            $definedClasses,
                            true,
                            false
                        );
                    }
                }
            }

            $classes                    = $this->inheritPropertiesFromParentClasses($classes);
            $this->classesConfiguration = GeneralUtility::makeInstance(ClassesConfiguration::class, $classes);
        }
    }

    /**
     * Copied from TYPO3\CMS\Extbase\Persistence\ClassesConfigurationFactory because instantiation of that class would
     * throw an exception (e.g. CacheManager not available, dependency injection not ready).
     */
    private function inheritPropertiesFromParentClasses(array $classes): array
    {
        foreach (array_keys($classes) as $className) {
            if (!isset($classes[$className]['properties'])) {
                $classes[$className]['properties'] = [];
            }

            /*
             * At first we need to clean the list of parent classes.
             * This method is expected to be called for models that either inherit
             * AbstractEntity or AbstractValueObject, therefore we want to know all
             * parents of $className until one of these parents.
             */
            $relevantParentClasses = [];
            $parentClasses         = class_parents($className) ?: [];

            while (null !== $parentClass = array_shift($parentClasses)) {
                if (in_array(
                    $parentClass,
                    [
                        AbstractEntity::class,
                        AbstractValueObject::class,
                    ],
                    true
                )) {
                    break;
                }

                $relevantParentClasses[] = $parentClass;
            }

            /*
             * Once we found all relevant parent classes of $class, we can check their
             * property configuration and merge theirs with the current one. This is necessary
             * to get the property configuration of parent classes in the current one to not
             * miss data in the model later on.
             */
            foreach ($relevantParentClasses as $currentClassName) {
                $properties = $classes[$currentClassName]['properties'] ?? null;

                if (null === $properties) {
                    continue;
                }

                // Merge new properties over existing ones.
                $classes[$className]['properties'] = array_replace_recursive(
                    $properties,
                    $classes[$className]['properties'] ?? []
                );
            }
        }

        return $classes;
    }
}
