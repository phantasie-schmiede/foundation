<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Service\Configuration\Tca;

use JsonException;
use PSBits\Foundation\Attribute\TCA\Ctrl;
use PSBits\Foundation\Utility\Configuration\TcaUtility;
use PSBits\Foundation\Utility\LocalizationUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;

use function is_array;

/**
 * Class CtrlInitializer
 *
 * Applies the configuration of the Ctrl attribute to a table and creates the default columns derived from the ctrl
 * settings (enable columns, language fields).
 *
 * @package PSBits\Foundation\Service\Configuration\Tca
 */
readonly class CtrlInitializer
{
    public function __construct(
        private TcaTable $table,
    ) {
    }

    /**
     * Applies the properties of the Ctrl attribute to the table. In override mode, only the arguments which were
     * explicitly set are applied.
     *
     * @param ReflectionClass<object> $reflection
     *
     * @throws ReflectionException
     */
    public function apply(?Ctrl $ctrl, bool $overrideMode, ReflectionClass $reflection): void
    {
        if (null === $ctrl) {
            return;
        }

        if ($overrideMode) {
            $ctrlProperties = [];
            $setArguments   = $reflection->getAttributes(Ctrl::class)[0]->getArguments();

            foreach ($setArguments as $key => $value) {
                $ctrlProperties[TcaUtility::convertKey($key)] = $value;
            }
        } else {
            $ctrlProperties = $ctrl->toArray();
        }

        $this->table->applyCtrlProperties($ctrlProperties);
    }

    /**
     * Ensures that a valid ctrl title is set, using the default label as fallback.
     *
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function ensureTitle(string $defaultLabelPath): void
    {
        if (empty($this->table->getCtrlProperty('title'))) {
            $this->table->applyCtrlProperties(['title' => $defaultLabelPath . 'ctrl.title']);
        }

        LocalizationUtility::validateLabel((string)$this->table->getCtrlProperty('title'));
    }

    /**
     * Initializes the base configuration of the table and the default columns
     * derived from the ctrl settings.
     */
    public function initializeDummyConfiguration(Ctrl $ctrl): void
    {
        $this->table->setBaseConfiguration([
            'columns'  => [],
            'palettes' => [],
            'types'    => [
                '0' => ['showitem' => ''],
            ],
        ]);

        $enableColumns = $ctrl->getEnableColumns();

        if (is_array($enableColumns)) {
            if (isset($enableColumns[Ctrl::ENABLE_COLUMN_IDENTIFIERS['DISABLED']])) {
                $this->table->addColumnConfiguration(
                    $enableColumns[Ctrl::ENABLE_COLUMN_IDENTIFIERS['DISABLED']],
                    TcaUtility::getDefaultConfigurationForDisabledField()
                );
            }

            if (isset($enableColumns[Ctrl::ENABLE_COLUMN_IDENTIFIERS['STARTTIME']])) {
                $this->table->addColumnConfiguration(
                    $enableColumns[Ctrl::ENABLE_COLUMN_IDENTIFIERS['STARTTIME']],
                    TcaUtility::getDefaultConfigurationForStartTimeField()
                );
                $this->table->addFieldsToPalette(
                    TcaUtility::CORE_PALETTE_IDENTIFIERS['TIME_RESTRICTION'],
                    [$enableColumns[Ctrl::ENABLE_COLUMN_IDENTIFIERS['STARTTIME']]]
                );
            }

            if (isset($enableColumns[Ctrl::ENABLE_COLUMN_IDENTIFIERS['ENDTIME']])) {
                $this->table->addColumnConfiguration(
                    $enableColumns[Ctrl::ENABLE_COLUMN_IDENTIFIERS['ENDTIME']],
                    TcaUtility::getDefaultConfigurationForEndTimeField()
                );
                $this->table->addFieldsToPalette(
                    TcaUtility::CORE_PALETTE_IDENTIFIERS['TIME_RESTRICTION'],
                    [$enableColumns[Ctrl::ENABLE_COLUMN_IDENTIFIERS['ENDTIME']]]
                );
            }
        }

        if (!empty($ctrl->getLanguageField())) {
            $this->table->addColumnConfiguration(
                $ctrl->getLanguageField(),
                TcaUtility::getDefaultConfigurationForLanguageField()
            );
            $this->table->addFieldsToPalette(
                TcaUtility::CORE_PALETTE_IDENTIFIERS['LANGUAGE'],
                [$ctrl->getLanguageField()]
            );
        }

        if (!empty($ctrl->getTransOrigPointerField())) {
            $this->table->addColumnConfiguration(
                $ctrl->getTransOrigPointerField(),
                TcaUtility::getDefaultConfigurationForTransOrigPointerField($this->table->getTableName())
            );
            $this->table->addFieldsToPalette(
                TcaUtility::CORE_PALETTE_IDENTIFIERS['LANGUAGE'],
                [$ctrl->getTransOrigPointerField()]
            );
        }

        if (!empty($ctrl->getTransOrigDiffSourceField())) {
            $this->table->addColumnConfiguration(
                $ctrl->getTransOrigDiffSourceField(),
                TcaUtility::getDefaultConfigurationForTransOrigDiffSourceField()
            );
        }

        if (!empty($ctrl->getTranslationSource())) {
            $this->table->addColumnConfiguration(
                $ctrl->getTranslationSource(),
                TcaUtility::getDefaultConfigurationForTranslationSourceField()
            );
        }
    }
}
