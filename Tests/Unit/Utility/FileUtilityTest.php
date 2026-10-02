<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Tests\Unit\Utility;

use DateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PSBits\Foundation\Utility\FileUtility;
use RuntimeException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

use function ob_get_clean;
use function ob_start;

/**
 * Class FileUtilityTest
 *
 * Filesystem-based tests run in a dedicated temporary directory that is
 * created in setUp and removed in tearDown.
 *
 * @package PSBits\Foundation\Tests\Unit\Utility
 */
class FileUtilityTest extends UnitTestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        /*
         * The temp directory must be inside the project path and normalized
         * (no '..'), because GeneralUtility::getFileAbsFileName() only
         * resolves allowed, valid paths.
         */
        $this->tempDir = realpath(__DIR__ . '/../../../.Build') . '/var/fileutility_' . uniqid('', true);
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $files = glob($this->tempDir . '/*') ?: [];

        foreach ($files as $file) {
            @unlink($file);
        }
        @rmdir($this->tempDir);
        parent::tearDown();
    }

    private function tempFile(string $name, string $content = 'content'): string
    {
        $path = $this->tempDir . '/' . $name;
        file_put_contents($path, $content);

        return $path;
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function sanitizeFileNameDataProvider(): array
    {
        return [
            'unaffected name is returned unchanged'       => [
                'simple-name.file.txt',
                'simple-name.file.txt',
            ],
            'spaces are replaced'                         => [
                'foo bar.txt',
                'foo_bar.txt',
            ],
            'path separators are replaced'                => [
                'a/b\\c',
                'a_b_c',
            ],
            'non ascii characters are replaced byte-wise' => [
                "a\303\274\303\244.txt",
                'a____.txt',
            ],
        ];
    }

    #[Test]
    #[DataProvider('sanitizeFileNameDataProvider')]
    public function sanitizeFileName(string $fileName, string $expected): void
    {
        self::assertSame($expected, FileUtility::sanitizeFileName($fileName));
    }

    /**
     * @return array<string, array{int|string, ?int, int, string}>
     */
    public static function formatFileSizeDataProvider(): array
    {
        return [
            'zero bytes'                             => [
                0,
                null,
                2,
                '0&nbsp;B',
            ],
            'bytes'                                  => [
                512,
                null,
                2,
                '512&nbsp;B',
            ],
            'kilobytes'                              => [
                1024,
                null,
                2,
                '1&nbsp;KB',
            ],
            'megabytes'                              => [
                1024 ** 2,
                null,
                2,
                '1&nbsp;MB',
            ],
            'terabytes'                              => [
                1024 ** 4,
                null,
                2,
                '1&nbsp;TB',
            ],
            'forced unit divides by the unit'        => [
                524288,
                FileUtility::FILE_SIZE_UNITS['KB'],
                2,
                '512&nbsp;KB',
            ],
            'decimals are limited'                   => [
                1536,
                null,
                0,
                '2&nbsp;KB',
            ],
            'filename input is resolved to its size' => [
                '3-byte-file',
                null,
                2,
                '3&nbsp;B',
            ],
        ];
    }

    #[Test]
    #[DataProvider('formatFileSizeDataProvider')]
    public function formatFileSize(int|string $input, ?int $unit, int $decimals, string $expected): void
    {
        if (is_string($input)) {
            $input = $this->tempFile($input, 'abc');
        }

        self::assertSame($expected, FileUtility::formatFileSize($input, $unit, $decimals));
    }

    #[Test]
    public function fileExistsReportsPresenceAndAbsenceOfFiles(): void
    {
        $path = $this->tempFile('exists.txt');

        self::assertTrue(FileUtility::fileExists($path));
        self::assertFalse(FileUtility::fileExists($this->tempDir . '/missing.txt'));
    }

    #[Test]
    public function resolveFileNameReturnsAbsoluteExistingPath(): void
    {
        $path = $this->tempFile('resolve.txt');

        self::assertSame($path, FileUtility::resolveFileName($path));
    }

    #[Test]
    public function resolveFileNameReturnsEmptyStringForUnresolvablePath(): void
    {
        // Paths outside the allowed project/public areas cannot be resolved.
        self::assertSame('', FileUtility::resolveFileName('/nonexistent_outside_project/file.txt'));
    }

    #[Test]
    public function getLockFileNameAppendsLockSuffix(): void
    {
        $path = $this->tempFile('locked.txt');

        self::assertSame($path . '.lock', FileUtility::getLockFileName($path));
    }

    #[Test]
    public function getLockFileNameFallsBackToGivenNameForUnresolvablePath(): void
    {
        self::assertSame('EXT:nonexistent/file.txt.lock', FileUtility::getLockFileName('EXT:nonexistent/file.txt'));
    }

    #[Test]
    public function getMimeTypeDetectsPlainText(): void
    {
        $path = $this->tempFile('mime.txt', 'plain text content');

        self::assertSame('text/plain', FileUtility::getMimeType($path));
    }

    #[Test]
    public function lockFileWithoutLifetimeCreatesEmptyLockFile(): void
    {
        $path = $this->tempFile('lock1.txt');

        self::assertTrue(FileUtility::lockFile($path));
        self::assertFileExists($path . '.lock');
        self::assertSame('', trim(file_get_contents($path . '.lock')));
        self::assertTrue(FileUtility::isFileLocked($path));
    }

    #[Test]
    public function lockFileWithFutureLifetimeKeepsFileLocked(): void
    {
        $path     = $this->tempFile('lock2.txt');
        $lifetime = new DateTime('+5 minutes');

        self::assertTrue(FileUtility::lockFile($path, $lifetime));
        self::assertTrue(FileUtility::isFileLocked($path));
        self::assertSame($lifetime->format('Y-m-d H:i:s'), trim(file_get_contents($path . '.lock')));
    }

    #[Test]
    public function lockFileWithPastLifetimeIsReleasedOnLockCheck(): void
    {
        $path = $this->tempFile('lock3.txt');

        self::assertTrue(FileUtility::lockFile($path, new DateTime('-5 minutes')));
        self::assertFalse(FileUtility::isFileLocked($path));
        self::assertFileDoesNotExist($path . '.lock');
    }

    #[Test]
    public function unlockFileRemovesLockFile(): void
    {
        $path = $this->tempFile('lock4.txt');
        FileUtility::lockFile($path);
        self::assertFileExists($path . '.lock');

        self::assertTrue(FileUtility::unlockFile($path));
        self::assertFileDoesNotExist($path . '.lock');
    }

    #[Test]
    public function unlockFileWithoutLockFileSucceeds(): void
    {
        $path = $this->tempFile('lock5.txt');

        self::assertTrue(FileUtility::unlockFile($path));
    }

    #[Test]
    public function writeCreatesFileWithContent(): void
    {
        $path = $this->tempDir . '/written.txt';

        self::assertTrue(FileUtility::write($path, 'hello'));
        self::assertSame('hello', file_get_contents($path));
    }

    #[Test]
    public function writeAppendsWhenRequested(): void
    {
        $path = $this->tempDir . '/appended.txt';
        FileUtility::write($path, 'one');

        self::assertTrue(FileUtility::write($path, 'two', true));
        self::assertSame('onetwo', file_get_contents($path));
    }

    #[Test]
    public function writeWithEmptyContentSucceeds(): void
    {
        $path = $this->tempDir . '/empty.txt';

        self::assertTrue(FileUtility::write($path, ''));
        self::assertFileExists($path);
        self::assertSame('', file_get_contents($path));
    }

    #[Test]
    public function writeFailsWhileFileIsLocked(): void
    {
        $path = $this->tempDir . '/locked-write.txt';
        FileUtility::lockFile($path);

        self::assertFalse(FileUtility::write($path, 'nope'));
        self::assertFileDoesNotExist($path);
    }

    #[Test]
    public function initiateDownloadWithoutContentAndFilenameThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Either $content or $filename has to be set!');
        $this->expectExceptionCode(1739366404);

        FileUtility::initiateDownload('text/plain');
    }

    #[Test]
    public function initiateDownloadWithContentButWithoutDownloadNameThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('$downloadName has to be set when $content is set!');
        $this->expectExceptionCode(1739366548);

        FileUtility::initiateDownload('text/plain', content: 'content');
    }

    #[Test]
    public function initiateDownloadOutputsGivenContent(): void
    {
        ob_start();
        FileUtility::initiateDownload('text/plain', content: 'downloaded content', downloadName: 'file.txt');
        $output = ob_get_clean();

        self::assertSame('downloaded content', $output);
    }

    #[Test]
    public function initiateDownloadOutputsFileContent(): void
    {
        $path = $this->tempFile('download.txt', 'file content');

        ob_start();
        FileUtility::initiateDownload('text/plain', filename: $path);
        $output = ob_get_clean();

        self::assertSame('file content', $output);
    }
}
