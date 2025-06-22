<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Controller\Backend;

use Doctrine\DBAL\Exception;
use JsonException;
use PSBits\Foundation\Attribute\ModuleAction;
use PSBits\Foundation\Exceptions\ImplementationException;
use PSBits\Foundation\Service\ExtensionInformationService;
use PSBits\Foundation\Utility\Configuration\FilePathUtility;
use PSBits\Foundation\Utility\Localization\LoggingUtility;
use PSBits\Foundation\Utility\Xml\XmlUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function is_array;

/**
 * Class AbstractModuleController
 *
 * @package PSBits\Foundation\Controller\Backend
 */
#[AsController]
class AnalyzeLocalLangController extends AbstractModuleController
{
    public function __construct(
        protected readonly ExtensionInformationService $extensionInformationService,
        ModuleTemplateFactory                          $moduleTemplateFactory,
    ) {
        parent::__construct($moduleTemplateFactory);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws ImplementationException
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    #[ModuleAction(default: true)]
    public function overviewAction(): ResponseInterface
    {
        $this->moduleTemplate->assignMultiple([
            'labelAccessLogData'   => $this->fetchLabelAccessLogData(),
            'missingLabelsLogData' => $this->fetchMissingLabelsLogData(),
        ]);

        return $this->htmlResponse();
    }

    /**
     * Returns an array with all defined identifiers as keys (format: "LLL:EXT:...").
     *
     * @throws ContainerExceptionInterface
     * @throws ImplementationException
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    private function collectAllLanguageLabels(): array
    {
        $allExtensionInformation = $this->extensionInformationService->getAllExtensionInformation();
        $languageLabels = [];

        foreach ($allExtensionInformation as $extensionInformation) {
            $languageDirectory = FilePathUtility::getResourcePath($extensionInformation) . 'Private/Language/';
            $realPath = GeneralUtility::getFileAbsFileName($languageDirectory);

            if (!is_dir($realPath)) {
                continue;
            }

            $finder = Finder::create()
                ->files()
                ->in($realPath)
                ->name('*' . FilePathUtility::LANGUAGE_FILE_EXTENSION);

            /** @var SplFileInfo $fileInfo */
            foreach ($finder as $fileInfo) {
                $fileIdentifier = FilePathUtility::LANGUAGE_LABEL_PREFIX . $languageDirectory . $fileInfo->getRelativePathname(
                    );

                $xmlData = XmlUtility::convertFromXml(file_get_contents($fileInfo->getRealPath()));

                // Skip empty files.
                if (!isset($xmlData['xliff']['file']['body']['trans-unit']) || !is_array(
                        $xmlData['xliff']['file']['body']['trans-unit']
                    )) {
                    continue;
                }

                // Skip translations.
                if (isset($xmlData['xliff']['file'][XmlUtility::SPECIAL_ARRAY_KEYS['ATTRIBUTES']]['target-language'])) {
                    continue;
                }

                foreach ($xmlData['xliff']['file']['body']['trans-unit'] as $transUnit) {
                    if (isset($transUnit[XmlUtility::SPECIAL_ARRAY_KEYS['ATTRIBUTES']]['id'])) {
                        $languageLabels[$fileIdentifier . ':' . $transUnit[XmlUtility::SPECIAL_ARRAY_KEYS['ATTRIBUTES']]['id']] = null;
                    }
                }
            }
        }

        return $languageLabels;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws ImplementationException
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    private function fetchLabelAccessLogData(): array
    {
        $languageLabels = $this->collectAllLanguageLabels();
        LoggingUtility::checkPostponedAccessLogEntries();

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(LoggingUtility::LOG_TABLES['ACCESS']);

        $logData = $queryBuilder->select('*')
            ->from(LoggingUtility::LOG_TABLES['ACCESS'])
            ->executeQuery()
            ->fetchAllAssociative();

        foreach ($logData as $logRecord) {
            $key = $logRecord['locallang_key'];

            // Skip log entries of keys which are not present in extensions based on foundation.
            if (!array_key_exists($key, $languageLabels)) {
                continue;
            }

            $languageLabels[$key] = $logRecord['hit_count'];
        }

        // Sort by hitCount in ascending order
        asort($languageLabels);

        $result = [];

        // Group results by extension:
        foreach ($languageLabels as $languageLabel => $hitCount) {
            $result[$this->getExtensionKeyFromLanguageLabel($languageLabel)][$languageLabel] = $hitCount;
        }

        ksort($result);

        return $result;
    }

    /**
     * @throws Exception
     * @throws JsonException
     */
    private function fetchMissingLabelsLogData(): array
    {
        LoggingUtility::checkPostponedMissingLogEntries();
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(LoggingUtility::LOG_TABLES['MISSING']);

        $logData = $queryBuilder->select('*')
            ->from(LoggingUtility::LOG_TABLES['MISSING'])
            ->executeQuery()
            ->fetchFirstColumn();
        sort($logData);

        $result = [];

        // Group results by extension:
        foreach ($logData as $languageLabel) {
            $result[$this->getExtensionKeyFromLanguageLabel($languageLabel)][] = $languageLabel;
        }

        ksort($result);

        return $result;
    }

    private function getExtensionKeyFromLanguageLabel(string $languageLabel): string
    {
        // "LLL:EXT:" = 8 characters
        return mb_substr($languageLabel, 8, mb_strpos($languageLabel, '/') - 8);
    }
}
