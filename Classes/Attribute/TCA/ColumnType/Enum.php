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
use BackedEnum;
use JsonException;
use PSBits\Foundation\Enum\SelectRenderType;
use PSBits\Foundation\Exceptions\MisconfiguredTcaException;
use PSBits\Foundation\Utility\Configuration\FilePathUtility;
use PSBits\Foundation\Utility\Database\DefinitionUtility;
use PSBits\Foundation\Utility\LocalizationUtility;
use PSBits\Foundation\Utility\StringUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use function is_string;
use function strlen;

/**
 * Class Enum
 *
 * @package PSBits\Foundation\Attribute\TCA\ColumnType
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Enum implements ColumnTypeWithItemsInterface
{
    protected array $items = [];

    public function __construct(
        protected string $enumClass,
    ) {
    }

    /**
     * @throws MisconfiguredTcaException
     */
    public function getDatabaseDefinition(): string
    {
        $this->checkType();

        // Check if backed enum has string or integer values
        $cases = $this->enumClass::cases();
        $firstCase = reset($cases);

        if (is_string($firstCase->value)) {
            $maxStringLength = 0;

            foreach ($cases as $case) {
                $maxStringLength = max($maxStringLength, strlen((string)$case->value));
            }

            return DefinitionUtility::varchar($maxStringLength);
        }

        $hasNegativeValues = false;
        $maxValue = 0;

        foreach ($cases as $case) {
            if ($case->value < 0) {
                $hasNegativeValues = true;
            }

            $maxValue = max($maxValue, abs($case->value));
        }

        return match ($hasNegativeValues) {
            true  => match (true) {
                $maxValue <= 127        => DefinitionUtility::tinyint(),
                $maxValue <= 32767      => DefinitionUtility::smallint(),
                $maxValue <= 8388607    => DefinitionUtility::mediumint(),
                $maxValue <= 2147483647 => DefinitionUtility::int(),
                default                 => DefinitionUtility::bigint()
            },
            false => match (true) {
                $maxValue <= 255        => DefinitionUtility::tinyint(unsigned: true),
                $maxValue <= 65535      => DefinitionUtility::smallint(unsigned: true),
                $maxValue <= 16777215   => DefinitionUtility::mediumint(unsigned: true),
                $maxValue <= 4294967295 => DefinitionUtility::int(unsigned: true),
                default                 => DefinitionUtility::bigint(unsigned: true)
            },
        };
    }

    public function getType(): string
    {
        return 'select';
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws JsonException
     * @throws MisconfiguredTcaException
     * @throws NotFoundExceptionInterface
     */
    public function processItems(string $labelPath = ''): void
    {
        $this->checkType();
        $items = [];

        foreach ($this->enumClass::cases() as $case) {
            $label = StringUtility::sanitizePropertyName($case->name);

            if (str_starts_with(
                    $labelPath,
                    FilePathUtility::LANGUAGE_LABEL_PREFIX
                ) && LocalizationUtility::translationExists($labelPath . $label)) {
                $label = $labelPath . $label;
            }

            $items[] = [
                'label' => $label,
                'value' => $case->value,
            ];
        }

        $this->items = $items;
    }

    public function toArray(): array
    {
        return [
            'items'      => $this->items,
            'renderType' => SelectRenderType::selectSingle->value,
            'type'       => $this->getType(),
        ];
    }

    /**
     * @throws MisconfiguredTcaException
     */
    private function checkType(): void
    {
        if (!is_subclass_of($this->enumClass, BackedEnum::class)) {
            throw new MisconfiguredTcaException(
                __CLASS__ . ': The provided class "' . $this->enumClass . '" is not a valid backend enum.', 1773836071
            );
        }
    }
}
