<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Utility\Xml;

use PSBits\Foundation\Traits\AutoFillPropertiesTrait;
use PSBits\Foundation\Utility\ObjectUtility;
use ReflectionClass;
use ReflectionException;
use function in_array;
use function is_array;

/**
 * Class AbstractXmlElement
 *
 * @package PSBits\Foundation\Utility\Xml
 */
class AbstractXmlElement implements XmlElementInterface
{
    use AutoFillPropertiesTrait;

    protected array $_attributes = [];

    /**
     * @var array Should be of type mixed, but Extbase throws an error in that case. Thus, we have to use this
     *            workaround.
     */
    protected array $_nodeValue = [];

    protected ?int $_position = null;

    /**
     * @throws ReflectionException
     */
    public function __construct(array $childData)
    {
        foreach ($childData as $childKey => $childValues) {
            if (is_array($childValues)) {
                if (isset($childValues[XmlUtility::SPECIAL_ARRAY_KEYS['POSITION']])) {
                    $this->_setPosition($childValues[XmlUtility::SPECIAL_ARRAY_KEYS['POSITION']]);
                    unset ($childValues[XmlUtility::SPECIAL_ARRAY_KEYS['POSITION']]);
                }

                $onlyNodeValue = true;

                foreach ($childValues as $childValueKey => $childValue) {
                    if ($childValueKey !== XmlUtility::SPECIAL_ARRAY_KEYS['NODE_VALUE']) {
                        $onlyNodeValue = false;
                    }
                }

                if ($onlyNodeValue) {
                    $childData[$childKey] = $childValues[XmlUtility::SPECIAL_ARRAY_KEYS['NODE_VALUE']];
                }
            }
        }

        if (isset($childData[XmlUtility::SPECIAL_ARRAY_KEYS['ATTRIBUTES']])) {
            $this->_setAttributes($childData[XmlUtility::SPECIAL_ARRAY_KEYS['ATTRIBUTES']]);
        }

        if (isset($childData[XmlUtility::SPECIAL_ARRAY_KEYS['NODE_VALUE']])) {
            $this->_setNodeValue($childData[XmlUtility::SPECIAL_ARRAY_KEYS['NODE_VALUE']]);
        }

        $this->fillProperties($childData);
    }

    public static function getTagName(): string
    {
        return XmlUtility::sanitizeTagName((new ReflectionClass(static::class))->getShortName());
    }

    public function _getAttributes(): array
    {
        return $this->_attributes;
    }

    public function _getNodeValue(): mixed
    {
        return $this->_nodeValue[0] ?? null;
    }

    public function _getPosition(): ?int
    {
        return $this->_position;
    }

    public function _setAttributes(array $attributes): void
    {
        $this->_attributes = $attributes;
    }

    public function _setNodeValue($nodeValue): void
    {
        $this->_nodeValue = [$nodeValue];
    }

    public function _setPosition(int $position): void
    {
        $this->_position = $position;
    }

    /**
     * @throws ReflectionException
     */
    public function toArray(): array
    {
        $propertiesArray = ObjectUtility::toArray($this);
        $array = [];

        foreach ($propertiesArray as $key => $value) {
            if (!in_array($key, XmlUtility::SPECIAL_XML_KEYS, true)) {
                $array[XmlUtility::sanitizeTagName($key)] = $value;
            }
        }

        if (!empty($this->_getAttributes())) {
            $array[XmlUtility::SPECIAL_ARRAY_KEYS['ATTRIBUTES']] = $this->_getAttributes();
        }

        if (null !== $this->_getNodeValue()) {
            $array[XmlUtility::SPECIAL_ARRAY_KEYS['NODE_VALUE']] = $this->_getNodeValue();
        }

        if (null !== $this->_getPosition()) {
            $array[XmlUtility::SPECIAL_ARRAY_KEYS['POSITION']] = $this->_getPosition();
        }

        return $array;
    }
}
