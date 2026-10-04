<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Tests\Unit\Utility;

use PHPUnit\Framework\Attributes\Test;
use PSBits\Foundation\Utility\Typo3VersionUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class Typo3VersionUtilityTest
 *
 * Guards the version predicate the whole extension gates its compatibility branches on. The core
 * version differs per matrix job, so each expectation is derived from the version actually running
 * and a disagreement between the two fails the build.
 *
 * @package PSBits\Foundation\Tests\Unit\Utility
 */
class Typo3VersionUtilityTest extends UnitTestCase
{
    private string $coreVersion;

    protected function setUp(): void
    {
        parent::setUp();
        $this->coreVersion = VersionNumberUtility::getNumericTypo3Version();
    }

    /**
     * This is the gate behind passing the native DuplicationBehavior enum to
     * ResourceStorage::addUploadedFile() and behind skipping the manual user.tsconfig import.
     */
    #[Test]
    public function theV13GateIsTrueFromV13On(): void
    {
        self::assertSame(
            version_compare($this->coreVersion, '13.0', '>='),
            Typo3VersionUtility::isAtLeast('13.0')
        );
    }

    #[Test]
    public function everyVersionWithinTheSupportWindowIsAtLeastTheFloor(): void
    {
        self::assertTrue(
            Typo3VersionUtility::isAtLeast('13.0'),
            'The extension supports v13 upwards, so all supported majors must satisfy the floor.'
        );
    }

    /**
     * The upper direction, which is the one the compatibility branches actually rely on: a version
     * above the running core is never satisfied.
     */
    #[Test]
    public function aVersionAboveTheRunningCoreIsNotSatisfied(): void
    {
        $nextMajor = ((int)explode('.', $this->coreVersion)[0] + 1) . '.0';

        self::assertFalse(
            Typo3VersionUtility::isAtLeast($nextMajor),
            sprintf('%s must not be considered satisfied on core %s.', $nextMajor, $this->coreVersion)
        );
    }

    /**
     * The trap this utility exists for: getNumericTypo3Version() returns a dotted string, so an
     * integer comparison silently yields false on every supported major.
     */
    #[Test]
    public function theCoreVersionIsADottedStringAndNotAnInteger(): void
    {
        self::assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', $this->coreVersion);
        self::assertFalse(
            130000 <= $this->coreVersion,
            'An integer style version gate must silently fail against the dotted core version; '
            . 'if it does not, revisit Typo3VersionUtility and the branches using it.'
        );
    }

    #[Test]
    public function majorOnlyAndFullVersionsAreBothAccepted(): void
    {
        $major = (string)(int)explode('.', $this->coreVersion)[0];

        self::assertTrue(Typo3VersionUtility::isAtLeast($major . '.0'));
        self::assertTrue(Typo3VersionUtility::isAtLeast($major . '.0.0'));
        self::assertTrue(Typo3VersionUtility::isAtLeast($this->coreVersion));
    }
}
