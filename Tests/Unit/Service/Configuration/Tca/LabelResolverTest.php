<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Tests\Unit\Service\Configuration\Tca;

use DateMalformedStringException;
use Doctrine\DBAL\Exception;
use Error;
use InvalidArgumentException;
use JsonException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\ExpectationFailedException;
use PSBits\Foundation\Service\Configuration\Tca\LabelResolver;
use PSBits\Foundation\Utility\Localization\LoggingUtility;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use ReflectionProperty;
use RuntimeException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Extbase\Configuration\Exception\NoServerRequestGivenException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

use function file_put_contents;
use function is_dir;
use function mkdir;
use function rmdir;
use function sprintf;
use function unlink;

class LabelResolverTest extends UnitTestCase
{
    private const string LANGUAGE_LABEL_PREFIX = 'LLL:test_lang/xlf:';

    /**
     * @throws ReflectionException
     * @throws RuntimeException
     */
    protected function tearDown(): void
    {
        $this->resetLoggingState();
        $languageDirectory = rtrim(Environment::getPublicPath(), '/') . '/test_lang';

        if (is_dir($languageDirectory)) {
            @unlink($languageDirectory . '/xlf');
            @rmdir($languageDirectory);
        }
        parent::tearDown();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws DateMalformedStringException
     * @throws Error
     * @throws Exception
     * @throws ExpectationFailedException
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws InvalidArgumentException
     * @throws JsonException
     * @throws NoServerRequestGivenException // t3:14
     * @throws ReflectionException
     * @throws RuntimeException
     */
    #[Test]
    public function resolveLabelFallsBackToDefaultIfLabelIsMissing(): void
    {
        $this->setLoggingState(false);
        $this->writeLanguageFile(['default']);

        self::assertSame(
            self::LANGUAGE_LABEL_PREFIX . 'default',
            LabelResolver::resolveLabel(self::LANGUAGE_LABEL_PREFIX . 'missing', self::LANGUAGE_LABEL_PREFIX . 'default')
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws DateMalformedStringException
     * @throws Error
     * @throws Exception
     * @throws ExpectationFailedException
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws InvalidArgumentException
     * @throws JsonException
     * @throws NoServerRequestGivenException // t3:14
     * @throws ReflectionException
     * @throws RuntimeException
     */
    #[Test]
    public function resolveLabelResolvesEmptyLabelToDefault(): void
    {
        $this->setLoggingState(false);
        $this->writeLanguageFile(['default']);

        self::assertSame(
            self::LANGUAGE_LABEL_PREFIX . 'default',
            LabelResolver::resolveLabel('', self::LANGUAGE_LABEL_PREFIX . 'default')
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws DateMalformedStringException
     * @throws Error
     * @throws Exception
     * @throws ExpectationFailedException
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws InvalidArgumentException
     * @throws JsonException
     * @throws NoServerRequestGivenException // t3:14
     * @throws ReflectionException
     * @throws RuntimeException
     */
    #[Test]
    public function resolveLabelReturnsEmptyStringIfNothingResolves(): void
    {
        $this->setLoggingState(false);
        $this->writeLanguageFile([]);

        self::assertSame('', LabelResolver::resolveLabel('', self::LANGUAGE_LABEL_PREFIX . 'default'));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws DateMalformedStringException
     * @throws Error
     * @throws Exception
     * @throws ExpectationFailedException
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws InvalidArgumentException
     * @throws JsonException
     * @throws NoServerRequestGivenException // t3:14
     * @throws ReflectionException
     * @throws RuntimeException
     */
    #[Test]
    public function resolveLabelReturnsFallbackIfDefaultIsMissing(): void
    {
        $this->setLoggingState(false);
        $this->writeLanguageFile([]);

        self::assertSame(
            'fallback',
            LabelResolver::resolveLabel(
                self::LANGUAGE_LABEL_PREFIX . 'missing',
                self::LANGUAGE_LABEL_PREFIX . 'default',
                'fallback'
            )
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws DateMalformedStringException
     * @throws Error
     * @throws Exception
     * @throws ExpectationFailedException
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws InvalidArgumentException
     * @throws JsonException
     * @throws NoServerRequestGivenException // t3:14
     * @throws ReflectionException
     * @throws RuntimeException
     */
    #[Test]
    public function resolveLabelReturnsValidLllLabel(): void
    {
        $this->setLoggingState(false);
        $this->writeLanguageFile(['my_label']);

        self::assertSame(
            self::LANGUAGE_LABEL_PREFIX . 'my_label',
            LabelResolver::resolveLabel(self::LANGUAGE_LABEL_PREFIX . 'my_label', self::LANGUAGE_LABEL_PREFIX . 'default')
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws DateMalformedStringException
     * @throws Error
     * @throws Exception
     * @throws ExpectationFailedException
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws InvalidArgumentException
     * @throws JsonException
     * @throws NoServerRequestGivenException // t3:14
     * @throws ReflectionException
     * @throws RuntimeException
     */
    #[Test]
    public function resolveLabelReturnsValidPlainTextLabel(): void
    {
        self::assertSame('My Label', LabelResolver::resolveLabel('My Label', self::LANGUAGE_LABEL_PREFIX . 'default'));
    }

    /**
     * @throws ReflectionException
     */
    private function resetLoggingState(): void
    {
        $logMissingLanguageLabels = new ReflectionProperty(LoggingUtility::class, 'logMissingLanguageLabels');
        $logMissingLanguageLabels->setValue(null, null);
    }

    /**
     * @throws ReflectionException
     */
    private function setLoggingState(bool $state): void
    {
        $logMissingLanguageLabels = new ReflectionProperty(LoggingUtility::class, 'logMissingLanguageLabels');
        $logMissingLanguageLabels->setValue(null, $state);
    }

    /**
     * @param list<string> $labelIds
     */
    private function writeLanguageFile(array $labelIds): void
    {
        $languageDirectory = rtrim(Environment::getPublicPath(), '/') . '/test_lang';
        mkdir($languageDirectory, 0777, true);

        $transUnits = '';

        foreach ($labelIds as $labelId) {
            $transUnits .= sprintf(
                '<trans-unit id="%s"><source>%s</source><target>%s</target></trans-unit>',
                htmlspecialchars($labelId),
                htmlspecialchars($labelId),
                htmlspecialchars($labelId)
            );
        }

        file_put_contents(
            $languageDirectory . '/xlf',
            <<<XML
            <?xml version="1.0" encoding="utf-8" standalone="yes"?>
            <xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:1.2">
                <file date="2026-01-01T00:00:00Z" source-language="en" target-language="de" datatype="plaintext" original="i18n">
                    <body>
                        {$transUnits}
                    </body>
                </file>
            </xliff>
            XML
        );
    }
}
