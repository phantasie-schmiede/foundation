<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Service;

use PSBits\Foundation\Exceptions\MisconfiguredTcaException;
use PSBits\Foundation\Service\Configuration\TcaService;
use PSBits\Foundation\Utility\ArrayUtility;
use PSBits\Foundation\Utility\ContextUtility;
use PSBits\Foundation\Utility\FileUtility;
use PSBits\Foundation\Utility\StringUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\Exception\IniSizeFileException;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceStorageInterface;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Reflection\Exception\PropertyNotAccessibleException;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use function count;
use function in_array;

/**
 * Class UploadService
 *
 * @package PSBits\Foundation\Service
 */
class UploadService
{
    public const string DEFAULT_UPLOAD_DIRECTORY = 'user_upload';

    public function __construct(
        protected readonly FileReferenceService $fileReferenceService,
        protected readonly StorageRepository    $storageRepository,
        protected readonly TcaService           $tcaService,
    ) {
    }

    /**
     * Stores uploaded files of an extbase request and maps them to domain model properties. The name of the upload form
     * fields have to be identical with the properties' names!
     *
     * @throws AspectNotFoundException
     * @throws ContainerExceptionInterface
     * @throws MisconfiguredTcaException
     * @throws NotFoundExceptionInterface
     * @throws PropertyNotAccessibleException
     * @throws ReflectionException
     */
    public function fromRequest(AbstractEntity $domainModel, RequestInterface $request): void
    {
        $pluginSignature = ContextUtility::getPluginSignatureFromRequest($request);
        $uploadedFilesCollection = $request->getUploadedFiles();

        if (isset($uploadedFilesCollection[$pluginSignature])) {
            $uploadedFilesCollection = $uploadedFilesCollection[$pluginSignature];
        }

        if (empty($uploadedFilesCollection)) {
            return;
        }

        // Preparation
        $domainModelReflection = new ReflectionClass($domainModel);
        $uploadedFilesCollection = array_filter(
            $uploadedFilesCollection,
            static function($property) use ($domainModelReflection) {
                return $domainModelReflection->hasProperty($property);
            },
            ARRAY_FILTER_USE_KEY
        );

        $this->processCollection($domainModel, $uploadedFilesCollection);
    }

    /**
     * @throws AspectNotFoundException
     * @throws ContainerExceptionInterface
     * @throws MisconfiguredTcaException
     * @throws NotFoundExceptionInterface
     * @throws PropertyNotAccessibleException
     * @throws ReflectionException
     */
    public function uploadForDomainObject(
        AbstractEntity $domainModel,
        string         $property,
        UploadedFile   $uploadedFile,
    ): void {
        $uploadedFilesCollection = [$property => [$uploadedFile]];
        $this->processCollection($domainModel, $uploadedFilesCollection);
    }

    /**
     * @throws PropertyNotAccessibleException
     */
    private function buildTargetFileName(
        AbstractEntity $domainModel,
        string         $property,
        array          $uploadConfiguration,
        UploadedFile   $uploadedFile,
    ): ?string {
        $fileNameGeneratorConfiguration = $uploadConfiguration[$property]['fileNameGenerator'] ?? [];
        $nameParts = [];

        if (!empty($fileNameGeneratorConfiguration['properties'] ?? [])) {
            foreach ($fileNameGeneratorConfiguration['properties'] as $fileNameProperty) {
                $nameParts[] = ObjectAccess::getProperty($domainModel, $fileNameProperty);
            }
        } elseif (!empty($uploadedFile->getClientFilename())) {
            $nameParts[] = StringUtility::removeSpecialChars($uploadedFile->getClientFilename());
        }

        if (!empty($fileNameGeneratorConfiguration['replacements'] ?? [])) {
            array_walk($nameParts, static function(&$namePart) use ($fileNameGeneratorConfiguration) {
                $namePart = str_replace(
                    array_keys($fileNameGeneratorConfiguration['replacements']),
                    array_values($fileNameGeneratorConfiguration['replacements']),
                    (string)$namePart
                );
            });
        }

        if ('' !== ($fileNameGeneratorConfiguration['prefix'] ?? '')) {
            array_unshift($nameParts, (string)$fileNameGeneratorConfiguration['prefix']);
        }

        if ('' !== ($fileNameGeneratorConfiguration['suffix'] ?? '')) {
            $nameParts[] = (string)$fileNameGeneratorConfiguration['suffix'];
        }

        if (true === $fileNameGeneratorConfiguration['appendHash']) {
            $nameParts[] = hash_file('crc32', $uploadedFile->getTemporaryFileName());
        }

        if (empty($nameParts)) {
            return null;
        }

        return implode(
                $fileNameGeneratorConfiguration['partSeparator'],
                $nameParts
            ) . '.' . $this->getFileExtensionByMimeType($uploadedFile);
    }

    /**
     * @throws AspectNotFoundException
     */
    private function checkFileSize(string $property, array $uploadConfiguration, UploadedFile $uploadedFile): void
    {
        if (isset($uploadConfiguration[$property]['maxSize']) && 0 < (int)$uploadConfiguration[$property]['maxSize'] && (int)$uploadConfiguration[$property]['maxSize'] < $uploadedFile->getSize(
            )) {
            throw new RuntimeException(
                __CLASS__ . ': File too large (exceeds ' . FileUtility::formatFileSize(
                    $uploadConfiguration[$property]['maxSize']
                ) . ')!', 1719230057
            );
        }
    }

    private function checkForError(UploadedFile $uploadedFile): void
    {
        switch ($uploadedFile->getError()) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                throw new RuntimeException(__CLASS__ . ': No file sent.', 1719230062);
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new IniSizeFileException(__CLASS__ . ': Exceeded file size limit.', 1719230067);
            default:
                throw new RuntimeException(__CLASS__ . ': Unknown errors.', 1719230071);
        }
    }

    private function checkMimeType(string $property, array $uploadConfiguration, UploadedFile $uploadedFile): void
    {
        $mimeType = FileUtility::getMimeType($uploadedFile->getTemporaryFileName());

        if ($uploadedFile->getClientMediaType() !== $mimeType) {
            throw new RuntimeException(__CLASS__ . ': Transmitted and actual file type diverge!', 1678280985);
        }

        $allowedFileExtensions = $uploadConfiguration[$property]['allowed'] ?? null;
        $fileExtension = $this->getFileExtensionByMimeType($uploadedFile);

        if (null !== $allowedFileExtensions && !in_array($fileExtension, $allowedFileExtensions, true)) {
            throw new RuntimeException(
                __CLASS__ . ': File type not allowed (has to be one of: ' . implode(
                    ', ',
                    $allowedFileExtensions
                ) . ')!', 1678280990
            );
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    private function collectFieldConfigurations(AbstractEntity $domainModel, array $properties): array
    {
        $uploadConfiguration = [];

        foreach ($properties as $property) {
            $fieldConfiguration = $this->tcaService->getConfigurationForPropertyOfDomainModel($domainModel, $property);
            $uploadConfiguration[$property] = $fieldConfiguration['config']['upload'] ?? [];
            $uploadConfiguration[$property]['allowed'] = $fieldConfiguration['config']['allowed'] ? ArrayUtility::guaranteeArrayType(
                $fieldConfiguration['config']['allowed'],
                ','
            ) : null;
        }

        return $uploadConfiguration;
    }

    private function getFileExtensionByMimeType(UploadedFile $uploadedFile): string
    {
        $mimeType = FileUtility::getMimeType($uploadedFile->getTemporaryFileName());

        return GeneralUtility::trimExplode('/', $mimeType)[1];
    }

    private function moveUploadedFileToFileStorage(
        string       $property,
        string       $targetFileName,
        array        $uploadConfiguration,
        UploadedFile $uploadedFile,
    ): FileInterface {
        return $uploadConfiguration[$property]['storage']->addUploadedFile(
            $uploadedFile,
            $uploadConfiguration[$property]['targetFolder'],
            $targetFileName,
            $uploadConfiguration[$property]['duplicationBehaviour'] ?? DuplicationBehavior::RENAME
        );
    }

    /**
     * @throws AspectNotFoundException
     * @throws ContainerExceptionInterface
     * @throws MisconfiguredTcaException
     * @throws NotFoundExceptionInterface
     * @throws PropertyNotAccessibleException
     * @throws ReflectionException
     */
    private function processCollection(AbstractEntity $domainModel, array $uploadedFilesCollection): void
    {
        $uploadConfiguration = $this->collectFieldConfigurations($domainModel, array_keys($uploadedFilesCollection));
        $this->validateUploadedFiles($uploadConfiguration, $uploadedFilesCollection);
        $this->provideTargetFolders($uploadConfiguration);

        // Execution
        foreach ($uploadedFilesCollection as $property => $uploadedFiles) {
            foreach ($uploadedFiles as $uploadedFile) {
                $targetFileName = $this->buildTargetFileName(
                    $domainModel,
                    $property,
                    $uploadConfiguration,
                    $uploadedFile
                );
                $file = $this->moveUploadedFileToFileStorage(
                    $property,
                    $targetFileName,
                    $uploadConfiguration,
                    $uploadedFile
                );
                $this->fileReferenceService->create($domainModel, $file, $property);
            }
        }
    }

    /**
     * @throws MisconfiguredTcaException
     */
    private function provideTargetFolders(array &$uploadConfiguration): void
    {
        foreach (array_keys($uploadConfiguration) as $property) {
            $this->setStorageAndTargetFolder($property, $uploadConfiguration);
        }
    }

    /**
     * @throws MisconfiguredTcaException
     */
    private function setStorageAndTargetFolder(string $property, array &$uploadConfiguration): void
    {
        $storage = $this->storageRepository->getDefaultStorage();

        // get upload target
        if (!empty($uploadConfiguration[$property]['targetFolder'])) {
            $targetFolder = $uploadConfiguration[$property]['targetFolder'];

            if (str_contains($targetFolder, ':')) {
                $parts = GeneralUtility::trimExplode(':', $targetFolder);

                if (2 !== count($parts)) {
                    throw new MisconfiguredTcaException(
                        __CLASS__ . ': The configuration option "targetFolder" of the property "' . $property . '" is in an invalid format!',
                        1677590190
                    );
                }

                $storage = $this->storageRepository->findByUid((int)$parts[0]);
                $targetFolder = $parts[1];
            }
        } else {
            $targetFolder = self::DEFAULT_UPLOAD_DIRECTORY;
        }

        if (!$storage instanceof ResourceStorageInterface) {
            throw new RuntimeException(__CLASS__ . ': Storage not found!', 1678280866);
        }

        $parentFolder = $storage->getRootLevelFolder();

        $uploadConfiguration[$property]['storage'] = $storage;
        $uploadConfiguration[$property]['targetFolder'] = $parentFolder->hasFolder(
            $targetFolder
        ) ? $parentFolder->getSubfolder($targetFolder) : $parentFolder->createFolder($targetFolder);
    }

    /**
     * Checks if the uploaded files match given constraints and ensures a consistent array structure for further loops.
     *
     * @throws AspectNotFoundException
     */
    private function validateUploadedFiles(array $uploadConfiguration, array &$uploadedFilesCollection): void
    {
        foreach ($uploadedFilesCollection as $property => &$uploadedFiles) {
            $uploadedFiles = ArrayUtility::guaranteeArrayType($uploadedFilesCollection[$property]);

            foreach ($uploadedFiles as $uploadedFile) {
                $this->checkForError($uploadedFile);
                $this->checkFileSize($property, $uploadConfiguration, $uploadedFile);
                $this->checkMimeType($property, $uploadConfiguration, $uploadedFile);
            }
        }
    }
}
