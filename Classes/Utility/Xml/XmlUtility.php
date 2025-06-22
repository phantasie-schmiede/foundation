<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Utility\Xml;

use DOMDocument;
use JsonException;
use PSBits\Foundation\Utility\StringUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use SimpleXMLElement;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function is_array;
use function is_string;

/**
 * Class XmlUtility
 *
 * @package PSBits\Foundation\Utility\Xml
 */
class XmlUtility
{
    public const SPECIAL_ARRAY_KEYS = [
        'ATTRIBUTES' => '@attributes',
        'NAMESPACES' => '@namespaces',
        'NODE_VALUE' => '@nodeValue',
        'POSITION'   => '@position',
    ];

    public const SPECIAL_XML_KEYS = [
        '_attributes',
        '_namespaces',
        '_nodeValue',
        '_position',
    ];

    public const XML_HEADER = '<?xml version="1.0" encoding="UTF-8"?>';

    public static function beautifyXml(string $xml, bool $forceNoWrap = false): string
    {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->loadXML($xml, LIBXML_PARSEHUGE | LIBXML_NOCDATA);
        $dom->formatOutput = true;
        $formattedXml = $dom->saveXML();

        if ($forceNoWrap) {
            // Replace spaces with non-breaking spaces to enforce correct indentation in frontend.
            $formattedXml = str_replace(' ', "\xc2\xa0", $formattedXml);
        }

        return $formattedXml;
    }

    /**
     * @param SimpleXMLElement|string $xml
     * @param bool                    $sortAlphabetically Sort tags on same level alphabetically by tag name.
     * @param array                   $mapping
     *
     * @return object|array|string
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public static function convertFromXml(
        SimpleXMLElement|string $xml,
        bool                    $sortAlphabetically = false,
        array                   $mapping = [],
    ): object|array|string {
        if (is_string($xml)) {
            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $xml = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_PARSEHUGE | LIBXML_NOCDATA);

            if (!$xml instanceof SimpleXMLElement) {
                throw new RuntimeException(__CLASS__ . ': No valid XML provided!');
            }
        }

        $namespaces = $xml->getDocNamespaces(true) ?: [];

        if (!isset($namespaces[''])) {
            $namespaces[''] = '';
        }

        ksort($namespaces);

        return self::buildFromXml($sortAlphabetically, $xml, $mapping, $namespaces);
    }

    public static function convertToXml(
        array|XmlElementInterface $data,
        string                    $xmlHeader = self::XML_HEADER,
        bool                      $wellFormatted = true,
    ): string {
        $xml = $xmlHeader . self::buildXml($data);

        if ($wellFormatted) {
            $xml = self::beautifyXml($xml);
        }

        return $xml;
    }

    public static function getNodeValue(array $array, string $path, bool $strict = true): mixed
    {
        $path .= '.' . self::SPECIAL_ARRAY_KEYS['NODE_VALUE'];

        if (false === $strict && !ArrayUtility::isValidPath($array, $path, '.')) {
            return null;
        }

        return ArrayUtility::getValueByPath($array, $path, '.');
    }

    public static function removeNode(array &$array, string $path, bool $strict = false): void
    {
        if (false === $strict && !ArrayUtility::isValidPath($array, $path, '.')) {
            return;
        }

        $array = ArrayUtility::removeByPath($array, $path, '.');
    }

    public static function sanitizeTagName(string $tagName): string
    {
        return str_replace('_', '-', GeneralUtility::camelCaseToLowerCaseUnderscored($tagName));
    }

    public static function setNodeValue(array &$array, string $path, mixed $value): void
    {
        $path .= '.' . self::SPECIAL_ARRAY_KEYS['NODE_VALUE'];
        $array = ArrayUtility::setValueByPath($array, $path, $value, '.');
    }

    /**
     * @param bool             $sortAlphabetically
     * @param SimpleXMLElement $xml
     * @param array            $mapping
     * @param array            $namespaces
     * @param bool             $rootLevel This is an internal parameter only to be set from within this function.
     *
     * @return array|object|string
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    private static function buildFromXml(
        bool             $sortAlphabetically,
        SimpleXMLElement $xml,
        array            $mapping,
        array            $namespaces = [],
        bool             $rootLevel = true,
    ): array|object|string {
        $array = [];

        foreach ($xml->getDocNamespaces(false, false) as $prefix => $namespace) {
            if (!empty($namespace)) {
                $array[self::SPECIAL_ARRAY_KEYS['NAMESPACES']][$prefix] = $namespace;
            }
        }

        $positionOnThisLevel = 0;

        foreach ($namespaces as $prefix => $namespace) {
            $prependPrefix = '';

            if (!empty($prefix)) {
                $prependPrefix = $prefix . ':';
            }

            foreach ($xml->attributes($prefix, true) as $attributeName => $value) {
                if ('version' !== $attributeName) {
                    $value = StringUtility::convertString(trim((string)$value));
                }

                $array[self::SPECIAL_ARRAY_KEYS['ATTRIBUTES']][$prependPrefix . $attributeName] = $value;
            }

            if (0 === $xml->count()) {
                continue;
            }

            foreach ($xml->children($prefix, true) as $childTagName => $child) {
                $childTagName = $prependPrefix . $childTagName;
                $parsedChild = self::buildFromXml($sortAlphabetically, $child, $mapping, $namespaces, false);

                if (isset($mapping[$childTagName])) {
                    $parsedChild = GeneralUtility::makeInstance($mapping[$childTagName], $parsedChild);
                }

                if ($parsedChild instanceof XmlElementInterface) {
                    $parsedChild->_setPosition($positionOnThisLevel++);
                } else {
                    $parsedChild[self::SPECIAL_ARRAY_KEYS['POSITION']] = $positionOnThisLevel++;
                }

                if (!isset($array[$childTagName])) {
                    $array[$childTagName] = $parsedChild;
                } elseif (is_array($array[$childTagName]) && !ArrayUtility::isAssociative($array[$childTagName])) {
                    $array[$childTagName][] = $parsedChild;
                } else {
                    $array[$childTagName] = [
                        $array[$childTagName],
                        $parsedChild,
                    ];
                }
            }
        }

        if (0 === $xml->count()) {
            $array[self::SPECIAL_ARRAY_KEYS['NODE_VALUE']] = StringUtility::convertString(trim((string)$xml));

            return $array;
        }

        if (true === $sortAlphabetically) {
            ksort($array);
        }

        if (true === $rootLevel) {
            $rootName = $xml->getName();

            if (isset($mapping[$rootName])) {
                return GeneralUtility::makeInstance($mapping[$rootName], $array);
            }

            return [$xml->getName() => $array];
        }

        return $array;
    }

    private static function buildTag(string $key, mixed $value): string
    {
        $xml = '<' . $key;

        if (is_array($value) && isset($value[self::SPECIAL_ARRAY_KEYS['NAMESPACES']]) && is_array(
                $value[self::SPECIAL_ARRAY_KEYS['NAMESPACES']]
            )) {
            foreach ($value[self::SPECIAL_ARRAY_KEYS['NAMESPACES']] as $prefix => $namespace) {
                $xml .= ' xmlns' . ($prefix ? (':' . $prefix) : '') . '="' . $namespace . '"';
            }

            unset($value[self::SPECIAL_ARRAY_KEYS['NAMESPACES']]);
        }

        if (is_array($value) && isset($value[self::SPECIAL_ARRAY_KEYS['ATTRIBUTES']]) && is_array(
                $value[self::SPECIAL_ARRAY_KEYS['ATTRIBUTES']]
            )) {
            foreach ($value[self::SPECIAL_ARRAY_KEYS['ATTRIBUTES']] as $attributeName => $attributeValue) {
                $xml .= ' ' . $attributeName . '="' . $attributeValue . '"';
            }

            unset($value[self::SPECIAL_ARRAY_KEYS['ATTRIBUTES']]);
        }

        $xml .= '>';

        if (is_array($value) && isset($value[self::SPECIAL_ARRAY_KEYS['NODE_VALUE']])) {
            $value = $value[self::SPECIAL_ARRAY_KEYS['NODE_VALUE']];
        }

        if (is_array($value) || $value instanceof XmlElementInterface) {
            $xml .= self::buildXml($value);
        } else {
            $xml .= $value;
        }

        if ('' === $value) {
            $xml = rtrim($xml, '>');
            $xml .= ' />';
        } else {
            $xml .= '</' . $key . '>';
        }

        return $xml;
    }

    private static function buildXml(array|XmlElementInterface $data)
    {
        $xml = '';
        $siblings = [];

        if ($data instanceof XmlElementInterface) {
            $data = [$data::getTagName() => $data->toArray()];
        }

        foreach ($data as $key => $value) {
            if (false === $value) {
                continue;
            }

            if ($value instanceof XmlElementInterface) {
                $value = $value->toArray();
            }

            if (is_array($value) && !ArrayUtility::isAssociative($value)) {
                foreach ($value as $sibling) {
                    if ($sibling instanceof XmlElementInterface) {
                        $sibling = $sibling->toArray();
                    }

                    $siblings[] = [
                        'tagName'  => $key,
                        'tagValue' => $sibling,
                    ];
                }
            } else {
                $siblings[] = [
                    'tagName'  => $key,
                    'tagValue' => $value,
                ];
            }
        }

        $siblings = self::sortSiblings($siblings);

        foreach ($siblings as $value) {
            if (isset($value['tagValue'][self::SPECIAL_ARRAY_KEYS['POSITION']])) {
                unset($value['tagValue'][self::SPECIAL_ARRAY_KEYS['POSITION']]);
            }

            $xml .= self::buildTag($value['tagName'], $value['tagValue']);
        }

        return $xml;
    }

    private static function sortSiblings(array $siblings): array
    {
        uasort($siblings, static function($a, $b) {
            $positionA = 0;
            $positionB = 0;

            if ($a['tagValue'] instanceof XmlElementInterface) {
                $positionA = $a['tagValue']->_getPosition();
            } elseif (is_array($a['tagValue'])) {
                $positionA = $a['tagValue'][self::SPECIAL_ARRAY_KEYS['POSITION']] ?? 0;
            }

            if ($b['tagValue'] instanceof XmlElementInterface) {
                $positionB = $b['tagValue']->_getPosition();
            } elseif (is_array($b['tagValue'])) {
                $positionB = $b['tagValue'][self::SPECIAL_ARRAY_KEYS['POSITION']] ?? 0;
            }

            return $positionA - $positionB;
        });

        return $siblings;
    }
}
