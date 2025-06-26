<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\ViewHelpers;

use Closure;
use InvalidArgumentException;
use JsonException;
use PSBits\Foundation\Utility\Configuration\FilePathUtility;
use PSBits\Foundation\Utility\ContextUtility;
use PSBits\Foundation\Utility\LocalizationUtility;
use PSBits\Foundation\ViewHelpers\Translation\RegisterLanguageFileViewHelper;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;
use function count;
use function in_array;
use function is_array;

/**
 * Class TranslateViewHelper
 *
 * Extended clone of the core ViewHelper.
 * - uses \PSBits\Foundation\Utility\LocalizationUtility to log missing language labels
 * - supports plural forms in language files:
 *   <trans-unit>-tags in xlf-files can be grouped like this to define plural forms of a translation:
 *       <group id=“day” restype=“x-gettext-plurals”>
 *           <trans-unit id=“day[0]”>
 *               <source>{0} day</source>
 *           </trans-unit>
 *           <trans-unit id=“day[1]”>
 *               <source>{0} days</source>
 *           </trans-unit>
 *       </group>
 *   The number in [] defines the plural form as defined here:
 *   http://docs.translatehouse.org/projects/localization-guide/en/latest/l10n/pluralforms.html
 *   See \PSBits\Foundation\Utility\Localization\PluralFormUtility for more information.
 *   In order to use the plural forms defined in your language files, you have to transfer an argument named 'quantity':
 *   <psbits:translate arguments="{quantity: 1}" id="..." />
 *   This argument can be combined with others (see support of named arguments below).
 * - provides a more convenient way to pass variables into translations:
 *   Instead of:
 *   <f:translate arguments="{0: 'myVar', 1: 123} id="myLabel" />
 *   <source>My two variables are %1$s and %2$s.</source>
 *   you can use:
 *   <psbits:translate arguments="{myVar: 'myVar', anotherVar: 123} id="myLabel" />
 *   <source>My two variables are {myVar} and {anotherVar}.</source>
 *   If a variable is not passed, the marker will remain untouched!
 * - adds the attribute "excludedLanguages": matching language keys will return null (bypasses fallbacks!)
 *   This way you can remove texts from certain site languages without additional condition wrappers in your template.
 *
 * @package PSBits\Foundation\ViewHelpers
 */
class TranslateViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Output is escaped already. We must not escape children, to avoid double encoding.
     *
     * @var bool
     */
    protected $escapeChildren = false;

    /**
     * Return array element by key.
     *
     * @throws AspectNotFoundException
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public static function renderStatic(
        array                     $arguments,
        Closure                   $renderChildrenClosure,
        RenderingContextInterface $renderingContext,
    ): ?string {
        [
            'arguments'         => $translateArguments,
            'default'           => $default,
            'excludedLanguages' => $excludedLanguages,
            'extensionName'     => $extensionName,
            'id'                => $id,
            'key'               => $key,
            'languageKey'       => $languageKey,
        ] = $arguments;

        // Use key if id is empty.
        if (null === $id) {
            $id = $key;
        }

        if ('' === (string)$id) {
            throw new Exception('An argument "key" or "id" has to be provided', 1682312266);
        }

        $request = null;

        if ($renderingContext instanceof RenderingContext) {
            $request = $renderingContext->getRequest();

            if (is_array($excludedLanguages)) {
                $locale = $request?->getAttribute('language')
                    ->getLocale()
                    ->getName();

                array_walk($excludedLanguages, static function(&$languageKey) {
                    $languageKey = str_replace('_', '-', $languageKey);
                });

                if (in_array($locale, $excludedLanguages, true)) {
                    return null;
                }
            }
        }

        if (!str_starts_with($id, FilePathUtility::LANGUAGE_LABEL_PREFIX)) {
            $result = static::checkRegisteredLanguageFiles($id, $renderingContext);

            if (false !== $result) {
                $id = $result;
            } elseif (null === $extensionName && $request instanceof RequestInterface) {
                $extensionName = $request->getControllerExtensionName();
                $id = static::buildIdFromRequest($id, $request);
            }
        }

        try {
            $value = static::translate($id, $extensionName, $translateArguments, $languageKey);
        } catch (InvalidArgumentException) {
            $value = null;
        }

        if (null === $value) {
            $value = $default ?? $renderChildrenClosure() ?? '';

            if (null !== $value && !empty($translateArguments)) {
                $value = vsprintf($value, $translateArguments);
            }
        }

        return $value;
    }

    /**
     * @param string      $id            Translation Key
     * @param string|null $extensionName UpperCamelCased extension key (for example BlogExample)
     * @param array|null  $arguments     Arguments to be replaced in the resulting string
     * @param string|null $languageKey   Language key to use for this translation
     *
     * @return string|null
     * @throws AspectNotFoundException
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    protected static function translate(
        string $id,
        string $extensionName = null,
        array  $arguments = null,
        string $languageKey = null,
    ): ?string {
        return LocalizationUtility::translate($id, $extensionName, $arguments, $languageKey);
    }

    private static function buildIdFromRequest(string $id, Request $request): string
    {
        $path = 'LLL:EXT:' . GeneralUtility::camelCaseToLowerCaseUnderscored(
                $request->getControllerExtensionName()
            ) . '/Resources/Private/Language/';

        if (ContextUtility::isFrontend()) {
            $path .= 'Frontend';
        } else {
            $path .= 'Backend';
        }

        // Controller name may consist of several parts, e.g. Backend\Module.
        $controllerName = explode('\\', $request->getControllerName());

        // Remove Backend from array to avoid duplicate folder name in path.
        if (1 < count($controllerName) && 'Backend' === $controllerName[0]) {
            array_shift($controllerName);
        }

        return $path . '/' . implode('/', $controllerName) . '/' . self::getActionName(
                $request->getControllerActionName()
            ) . '.xlf:' . $id;
    }

    /**
     * @param string                    $id
     * @param RenderingContextInterface $renderingContext
     *
     * @return false|string
     */
    private static function checkRegisteredLanguageFiles(
        string                    $id,
        RenderingContextInterface $renderingContext,
    ): bool|string {
        if (0 < mb_strpos($id, ':')) {
            [
                $alias,
                $id,
            ] = GeneralUtility::trimExplode(':', $id);
            $templateVariableContainer = $renderingContext->getVariableProvider();

            if ($templateVariableContainer->exists(RegisterLanguageFileViewHelper::VARIABLE_NAME)) {
                $registry = $templateVariableContainer->get(RegisterLanguageFileViewHelper::VARIABLE_NAME);

                if (isset($registry[RegisterLanguageFileViewHelper::REGISTRY_KEY][$alias])) {
                    return FilePathUtility::LANGUAGE_LABEL_PREFIX . $registry[RegisterLanguageFileViewHelper::REGISTRY_KEY][$alias] . ':' . $id;
                }
            }
        }

        return false;
    }

    /**
     * Helper method for TYPO3v12 compatibility: backend actions are named like "Controller/Action".
     */
    private static function getActionName(string $controllerActionName): string
    {
        $parts = explode('/', $controllerActionName);

        return lcfirst(array_pop($parts));
    }

    public function initializeArguments(): void
    {
        $this->registerArgument('arguments', 'array', 'Arguments to be replaced in the resulting string', false, []);
        $this->registerArgument(
            'default',
            'string',
            'If the given locallang key could not be found, this value is used. If this argument is not set, child nodes will be used to render the default'
        );
        $this->registerArgument('excludedLanguages', 'array', 'List of language keys that should return null');
        $this->registerArgument('extensionName', 'string', 'UpperCamelCased extension key (for example BlogExample)');
        $this->registerArgument('id', 'string', 'Translation ID. Same as key.');
        $this->registerArgument('key', 'string', 'Translation Key');
        $this->registerArgument(
            'languageKey',
            'string',
            'Language key ("dk" for example) or "default" to use. If empty, use current language. Ignored in non-extbase context.'
        );
    }
}
