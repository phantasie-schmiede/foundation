<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Tests\Functional;

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

#[IgnoreDeprecations]
class PageTypeRegistrationTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/psbits/foundation',
        'typo3conf/ext/psbitstest/pluginfixture',
    ];

    #[Test]
    public function customPageTypeRegistrationPreservesDefaultAllowedTables(): void
    {
        $registry = GeneralUtility::makeInstance(PageDoktypeRegistry::class);

        self::assertTrue($registry->isRecordTypeAllowedForDoktype('tt_content', 1));
        self::assertTrue($registry->isRecordTypeAllowedForDoktype('pages', 4242));
        self::assertFalse($registry->isRecordTypeAllowedForDoktype('tt_content', 4242));
        self::assertTrue($registry->doesDoktypeOnlyAllowSpecifiedRecordTypes(4242));
    }
}
