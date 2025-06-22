<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Utility;

use DateTime;
use Exception;
use JsonException;
use NumberFormatter;
use PSBits\Foundation\Service\TypoScriptProviderService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function constant;
use function in_array;
use function strlen;

/**
 * Class StringUtility
 *
 * @package PSBits\Foundation\Utility
 */
class StringUtility
{
    public const EMPTY_CHARACTERS = " \t\n\r\0\x0B\xC2\xA0";

    public static function cleanUrl(string $url): string
    {
        return html_entity_decode(urldecode($url));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public static function convertString(
        ?string $variable,
        bool    $convertEmptyStringToNull = false,
        array   $namespaces = [],
    ): mixed {
        if (null === $variable || ($convertEmptyStringToNull && '' === $variable)) {
            return null;
        }

        if ('' === $variable || str_contains($variable, '{#')) {
            // string is either empty or contains quoted query parameter
            return $variable;
        }

        /*
         * Try string-to-number conversion in these cases:
         * - single zero
         * - float beginning with "0," or "0."
         * - float not ending with zero (see line 85)
         * - any other number not beginning with a zero
         */
        if (1 === strlen($variable) || !str_starts_with($variable, '0') || in_array(
                $variable[1],
                [
                    '.',
                    ',',
                ],
                true
            )) {
            $intRepresentation = filter_var($variable, FILTER_VALIDATE_INT);

            if (false !== $intRepresentation) {
                return $intRepresentation;
            }

            $floatRepresentation = filter_var(str_replace(',', '.', $variable), FILTER_VALIDATE_FLOAT);

            // Avoid string manipulation by "rounding away" trailing zeros!
            if (false !== $floatRepresentation && !str_ends_with($variable, '0')) {
                return $floatRepresentation;
            }
        }

        if (str_starts_with($variable, 'TS:') && ContextUtility::isTypoScriptAvailable()) {
            $typoScriptProviderService = GeneralUtility::makeInstance(TypoScriptProviderService::class);
            [
                ,
                $path,
            ] = GeneralUtility::trimExplode(':', $variable);

            if ($typoScriptProviderService->has($path)) {
                return $typoScriptProviderService->get($path);
            }
        }

        // check for constant
        if (0 < mb_strpos($variable, '::')) {
            [
                $className,
                $constantName,
            ] = GeneralUtility::trimExplode('::', $variable, true, 2);
            $className = ObjectUtility::getFullQualifiedClassName($className, $namespaces);

            // If $className is false, we have a false positive. It's not a constant, but for example CSS.
            if (false !== $className) {
                if ('class' === $constantName) {
                    return $className;
                }

                $variable = $className . '::' . $constantName;

                /*
                 * find all [...] segments after constant name and convert each one separately before trying to access that
                 * array path.
                 */
                if (0 < preg_match_all('/\[\'?(.*)\'?(](?=\[)|]$)/U', $constantName, $pathSegments)) {
                    $pathSegments = array_map(static function($value) use ($convertEmptyStringToNull, $namespaces) {
                        return self::convertString(trim($value, '\'"'), $convertEmptyStringToNull, $namespaces);
                    }, $pathSegments[1]);

                    // get constant array (path information is stripped away)
                    $variable = constant(preg_replace('/\[\'?(.*)\'?]/', '', $variable));

                    try {
                        // now try to access the array path
                        return ArrayUtility::getValueByPath($variable, $pathSegments);
                    } catch (Exception) {
                        throw new RuntimeException(
                            __CLASS__ . ': Path "[' . implode(
                                '][',
                                $pathSegments
                            ) . ']" does not exist in array!', 1548170593
                        );
                    }
                }

                // check for dot-notation of array path
                if (str_contains($constantName, '.')) {
                    $pathSegments = explode('.', $constantName);
                    $pathSegments = array_map(static function($value) use ($convertEmptyStringToNull, $namespaces) {
                        return self::convertString(trim($value, '\'"'), $convertEmptyStringToNull, $namespaces);
                    }, $pathSegments);

                    // remove constant name from array
                    array_shift($pathSegments);

                    $variable = constant(mb_substr($variable, 0, mb_strpos($variable, '.')));

                    try {
                        // now try to access the array path
                        return ArrayUtility::getValueByPath($variable, $pathSegments);
                    } catch (Exception) {
                        throw new RuntimeException(
                            'Path "' . implode(
                                '.',
                                $pathSegments
                            ) . '" does not exist in array!', 1589385393
                        );
                    }
                }

                return constant($variable);
            }
        }

        // check for JSON
        if (in_array(
            $variable[0],
            [
                '{',
                '[',
            ],
            true
        )) {
            try {
                $decodedString = json_decode(str_replace('\'', '"', $variable), true, 512, JSON_THROW_ON_ERROR);

                if (null !== $decodedString) {
                    return $decodedString;
                }
            } catch (Exception) {
                // The string is not valid JSON. Just continue.
            }
        }

        return match ($variable) {
            'true' => true,
            'false' => false,
            default => $variable,
        };
    }

    public static function convertToDateTime(string $dateTimeString): DateTime
    {
        $timestamp = strtotime($dateTimeString);

        if (false === $timestamp) {
            throw new RuntimeException(__CLASS__ . ': String cannot be converted to DateTime!', 1664888951);
        }

        return (new DateTime())->setTimestamp($timestamp);
    }

    public static function convertToFloat(string $variable): float
    {
        return (float)str_replace(',', '.', $variable);
    }

    /**
     * @param string $string
     * @param int    $length
     * @param string $appendix
     * @param bool   $respectWordBoundaries
     * @param bool   $respectHtml Increases length of output string until all opened tags are properly closed
     *
     * @return string
     */
    public static function crop(
        string $string,
        int    $length,
        string $appendix = '…',
        bool   $respectWordBoundaries = true,
        bool   $respectHtml = true,
    ): string {
        if (mb_strlen($string) <= $length) {
            return $string;
        }

        $lastCharacterBeforeTruncation = '';

        if (true === $respectHtml) {
            $preparedString = preg_replace_callback('/<.*>/U', static function($matches) {
                return '###TAG###' . $matches[0] . '###TAG###';
            }, $string);

            $stringParts = array_filter(explode('###TAG###', $preparedString));
            $openedTags = [];
            $pureTextLength = 0;
            $outputString = '';

            foreach ($stringParts as $stringPart) {
                if ('/>' !== mb_substr($stringPart, -2)) {
                    if (str_starts_with($stringPart, '</')) {
                        $lastOpenedTag = array_pop($openedTags);
                        preg_match('/<\/(.+)>/U', $stringPart, $matches);
                        $closedTag = $matches[1];

                        if ($lastOpenedTag !== $closedTag) {
                            throw new RuntimeException(
                                __CLASS__ . ': HTML tags in the input string are not properly nested.', 1565696694
                            );
                        }
                    } elseif (str_starts_with($stringPart, '<')) {
                        // extract the tag name
                        preg_match('/<(.+)[\s>]/U', $stringPart, $matches);
                        $openedTags[] = $matches[1];
                    } else {
                        if (empty($openedTags)) {
                            $stringPart = mb_substr($stringPart, 0, $length - $pureTextLength);
                        }

                        $lastCharacterBeforeTruncation = mb_substr($stringPart, -1);
                        $pureTextLength += mb_strlen($stringPart);
                    }
                }

                $outputString .= $stringPart;

                if (empty($openedTags) && $pureTextLength >= $length) {
                    $length = mb_strlen($outputString);
                    break;
                }
            }
        }

        if (true === $respectWordBoundaries) {
            $notMultiByteLength = strlen(mb_substr($string, 0, $length));
            preg_match('/[\n|\s]/', $string, $matches, 0, $notMultiByteLength);

            if (!empty($matches)) {
                $length = mb_strpos($string, $matches[0], $length);
            }

            $lastCharacterBeforeTruncation = mb_substr($string, $length - 1, 1);
        }

        if (in_array(
            $lastCharacterBeforeTruncation,
            [
                '.',
                '!',
                '?',
            ],
            true
        )) {
            $appendix = '';
        }

        return mb_substr($string, 0, $length) . $appendix;
    }

    public static function explodeByLineBreaks(string $string): array
    {
        return preg_split(
            '/' . implode(
                '|',
                [
                    CRLF,
                    LF,
                    CR,
                ]
            ) . '/',
            $string
        ) ?: [];
    }

    public static function getFirstWord(string $sentence): string
    {
        $words = GeneralUtility::trimExplode(' ', $sentence, true);

        return $words[0];
    }

    /**
     * @throws AspectNotFoundException
     */
    public static function getNumberFormatter(int $style = NumberFormatter::DEFAULT_STYLE): NumberFormatter
    {
        return NumberFormatter::create(ContextUtility::getCurrentLocale(), $style);
    }

    public static function isEmpty(string $string): bool
    {
        return '' === trim($string, self::EMPTY_CHARACTERS);
    }

    public static function normalizeForSorting(string $string): string
    {
        return transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $string) ?: $string;
    }

    /**
     * Removes all characters which are not -, _, [, ], (, ), ., a space, a digit or a letter in the range a-zA-Z.
     */
    public static function removeSpecialChars(string $string): string
    {
        return mb_ereg_replace('/([^\w\ \d\-_\[\]\(\).])/', '', $string);
    }

    public static function sanitizePropertyName(string $propertyName): string
    {
        return lcfirst(
            str_replace(
                ' ',
                '',
                ucwords(
                    str_replace(
                        [
                            '_',
                            '-',
                        ],
                        ' ',
                        strtolower($propertyName)
                    )
                )
            )
        );
    }
}
