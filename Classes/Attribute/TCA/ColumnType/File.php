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
use PSBits\Foundation\Utility\Database\DefinitionUtility;

/**
 * Class File
 *
 * @package PSBits\Foundation\Attribute\TCA\ColumnType
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class File extends AbstractColumnType
{
    /**
     * @param array|string $allowed                              https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/File/Properties/Allowed.html
     * @param int|null     $maxItems                             https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/File/Properties/Maxitems.html
     * @param int|null     $minItems                             https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/File/Properties/Minitems.html
     * @param array|null   $overrideChildTca                     https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/File/Properties/OverrideChildTCa.html
     * @param array|null   $upload
     * @param string|null  $uploadDuplicationBehaviour           Defines how duplicates in the file system should be
     *                                                           handled (default is renaming the new file).
     *                                                           See \TYPO3\CMS\Core\Resource\DuplicationBehavior.
     * @param int|null     $uploadFileMaxSize                    If set and greater than zero, uploaded files must not
     *                                                           exceed this size (in bytes).
     * @param bool         $uploadFileNameGeneratorAppendHash    If true, the hash value of the file content is
     *                                                           appended to the file name.
     * @param string       $uploadFileNameGeneratorPartSeparator string which combines the different file name parts
     *                                                           (default is "-")
     * @param string|null  $uploadFileNameGeneratorPrefix        If set, the file name will start with this string.
     * @param array|null   $uploadFileNameGeneratorProperties    If empty, client file name will be used (removing
     *                                                           unsafe characters).
     * @param array|null   $uploadFileNameGeneratorReplacements  Associative array whose keys will be replaced by its
     *                                                           values in the file name
     * @param string|null  $uploadFileNameGeneratorSuffix        If set, the file name will end with this string.
     * @param string|null  $uploadTargetFolder                   This can be a simple file path or a combined
     *                                                           identifier like "2:my/file/path/" which defines the
     *                                                           ResourceStorage to be used. Default is "user_upload".
     *                                                           Example: "my/file/path/" will result to
     *                                                           "1:fileadmin/my/file/path/" (with default TYPO3
     *                                                           configuration).
     */
    public function __construct(
        protected array|string $allowed = 'common-image-types',
        protected ?int         $maxItems = null,
        protected ?int         $minItems = null,
        protected ?array       $overrideChildTca = null,
        protected ?array       $upload = null,
        protected ?string      $uploadDuplicationBehaviour = null,
        protected ?int         $uploadFileMaxSize = null,
        protected bool         $uploadFileNameGeneratorAppendHash = true,
        protected string       $uploadFileNameGeneratorPartSeparator = '-',
        protected ?string      $uploadFileNameGeneratorPrefix = null,
        protected ?array       $uploadFileNameGeneratorProperties = null,
        protected ?array       $uploadFileNameGeneratorReplacements = null,
        protected ?string      $uploadFileNameGeneratorSuffix = null,
        protected ?string      $uploadTargetFolder = null,
    ) {
    }

    public function getAllowed(): array|string
    {
        return $this->allowed;
    }

    public function getDatabaseDefinition(): string
    {
        return DefinitionUtility::int(unsigned: true);
    }

    public function getMaxItems(): ?int
    {
        return $this->maxItems;
    }

    public function getMinItems(): ?int
    {
        return $this->minItems;
    }

    public function getOverrideChildTca(): ?array
    {
        return $this->overrideChildTca;
    }

    public function getUpload(): ?array
    {
        $configuration = null;

        if (null !== $this->uploadFileNameGeneratorProperties) {
            $fileNameGeneratorOptions['properties'] = $this->uploadFileNameGeneratorProperties;

            if (null !== $this->uploadFileNameGeneratorAppendHash) {
                $fileNameGeneratorOptions['appendHash'] = $this->uploadFileNameGeneratorAppendHash;
            }

            if (null !== $this->uploadFileNameGeneratorPartSeparator) {
                $fileNameGeneratorOptions['partSeparator'] = $this->uploadFileNameGeneratorPartSeparator;
            }

            if (null !== $this->uploadFileNameGeneratorPrefix) {
                $fileNameGeneratorOptions['prefix'] = $this->uploadFileNameGeneratorPrefix;
            }

            if (null !== $this->uploadFileNameGeneratorReplacements) {
                $fileNameGeneratorOptions['replacements'] = $this->uploadFileNameGeneratorReplacements;
            }

            if (null !== $this->uploadFileNameGeneratorSuffix) {
                $fileNameGeneratorOptions['suffix'] = $this->uploadFileNameGeneratorSuffix;
            }

            $configuration['fileNameGenerator'] = $fileNameGeneratorOptions;
        }

        if (null !== $this->uploadDuplicationBehaviour) {
            $configuration['duplicationBehaviour'] = $this->uploadDuplicationBehaviour;
        }

        if (null !== $this->uploadFileMaxSize) {
            $configuration['maxSize'] = $this->uploadFileMaxSize;
        }

        if (null !== $this->uploadTargetFolder) {
            $configuration['targetFolder'] = $this->uploadTargetFolder;
        }

        if (empty($configuration)) {
            return null;
        }

        return $configuration;
    }
}
