<?php
declare(strict_types=1);

use PSBits\Foundation\Attribute\TCA\Mapping\Field;
use PSBits\Foundation\Attribute\TCA\Mapping\Table;
use PSBits\Foundation\Service\ExtensionInformationService;
use PSBits\Foundation\Utility\ReflectionUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

$classesConfiguration = [];
$extensionInformationService = GeneralUtility::makeInstance(ExtensionInformationService::class);

foreach ($extensionInformationService->getAllExtensionInformation() as $extensionInformation) {
    foreach ($extensionInformationService->getDomainModelClassNames($extensionInformation) as $className) {
        $tableMapping = ReflectionUtility::getAttributeInstance(Table::class, $className);

        if ($tableMapping instanceof Table) {
            $classesConfiguration[$className]['tableName'] = $tableMapping->getName();

            if (!empty($tableMapping->getParentClass())) {
                $classesConfiguration[$tableMapping->getParentClass()]['subclasses'][$className] = $className;
            }

            if (!empty($tableMapping->getRecordType())) {
                $classesConfiguration[$className]['recordType'] = $tableMapping->getRecordType();
            }
        }

        foreach ((new ReflectionClass($className))->getProperties() as $property) {
            $fieldMapping = ReflectionUtility::getAttributeInstance(Field::class, $property);

            if ($fieldMapping instanceof Field) {
                $classesConfiguration[$className]['properties'][$property->getName()]['fieldName'] = $fieldMapping->getName();
            }
        }
    }
}

return $classesConfiguration;
