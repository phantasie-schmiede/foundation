<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Service\Configuration;

use BackedEnum;
use PSBits\Foundation\Service\TypoScriptProviderService;
use PSBits\Foundation\Utility\ContextUtility;
use PSBits\Foundation\Utility\ObjectUtility;
use ReflectionEnum;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class FlexFormMarkerService
 *
 * Processes FlexForm XML and replaces ###MARKER### expressions with resolved values.
 * Supported marker expressions:
 * - TypoScript paths:        ###TS:path.to.value###
 * - PHP class constants:     ###\Vendor\Extension\Constants::MY_CONSTANT###
 * - Enum cases (backed):     ###\Vendor\Extension\Enum\MyEnum::CaseName### (resolved to the backed value)
 * - Enum cases (pure):       ###\Vendor\Extension\Enum\MyEnum::CaseName### (resolved to the case name)
 *
 * @package PSBits\Foundation\Service\Configuration
 */
class FlexFormMarkerService
{
    public const string MARKER_PATTERN = '/###(.+?)###/s';

    /**
     * Processes the given FlexForm XML string and replaces all ###MARKER### expressions with their resolved values.
     * Markers that cannot be resolved are left unchanged.
     */
    public function process(string $xml): string
    {
        return preg_replace_callback(
            self::MARKER_PATTERN,
            fn(array $matches): string => $this->resolveMarker($matches[0], $matches[1]),
            $xml
        ) ?? $xml;
    }

    /**
     * Resolves a single marker expression to its string value.
     * Returns the original marker (including ###) if resolution fails.
     */
    private function resolveMarker(string $originalMarker, string $expression): string
    {
        $expression = trim($expression);

        if (str_starts_with($expression, 'TS:')) {
            return $this->resolveTypoScriptMarker($originalMarker, $expression);
        }

        if (str_starts_with($expression, '\\') && str_contains($expression, '::')) {
            return $this->resolveClassMemberMarker($originalMarker, $expression);
        }

        return $originalMarker;
    }

    private function resolveTypoScriptMarker(string $originalMarker, string $expression): string
    {
        if (!ContextUtility::isTypoScriptAvailable()) {
            return $originalMarker;
        }

        [, $path] = GeneralUtility::trimExplode(':', $expression, true, 2);
        $typoScriptProviderService = GeneralUtility::makeInstance(TypoScriptProviderService::class);

        if ($typoScriptProviderService->has($path)) {
            return (string)$typoScriptProviderService->get($path);
        }

        return $originalMarker;
    }

    private function resolveClassMemberMarker(string $originalMarker, string $expression): string
    {
        $separatorPosition = mb_strpos($expression, '::');
        $scope = mb_substr($expression, 0, $separatorPosition);
        $member = mb_substr($expression, $separatorPosition + 2);
        $className = ObjectUtility::getFullQualifiedClassName($scope, []);

        if (false === $className) {
            return $originalMarker;
        }

        if (enum_exists($className)) {
            $enumReflection = new ReflectionEnum($className);

            if ($enumReflection->hasCase($member)) {
                $case = $enumReflection->getCase($member)->getValue();

                if ($case instanceof BackedEnum) {
                    return (string)$case->value;
                }

                return $case->name;
            }
        }

        $constantKey = $className . '::' . $member;

        if (defined($constantKey)) {
            return (string)constant($constantKey);
        }

        return $originalMarker;
    }
}
