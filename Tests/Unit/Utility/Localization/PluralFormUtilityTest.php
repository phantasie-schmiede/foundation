<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Utility\Localization;

use Generator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class PluralFormUtilityTest
 *
 * @package PSBits\Foundation\Utility\Localization
 */
class PluralFormUtilityTest extends UnitTestCase
{
    public static function getPluralFormDataProvider(): Generator
    {
        // Rule 0: always plural form 0 (e.g. Indonesian, Japanese)
        yield 'Indonesian singular 1' => ['id', 1, 0];
        yield 'Indonesian plural 5' => ['id', 5, 0];
        yield 'Japanese 10' => ['ja', 10, 0];

        // Rule 2: n != 1 (e.g. English, German, Dutch)
        yield 'English singular 1' => ['en', 1, 0];
        yield 'English plural 2' => ['en', 2, 1];
        yield 'English plural 0' => ['en', 0, 1];
        yield 'German singular 1' => ['de', 1, 0];
        yield 'German plural 3' => ['de', 3, 1];
        yield 'Dutch singular 1' => ['nl', 1, 0];
        yield 'Dutch plural 100' => ['nl', 100, 1];

        // Rule 1: n > 1 (e.g. French, Portuguese Brazil)
        yield 'French 0 gives form 0' => ['fr', 0, 0];
        yield 'French 1 gives form 0' => ['fr', 1, 0];
        yield 'French 2 gives form 1' => ['fr', 2, 1];

        // Rule 18: Slavic (e.g. Russian, Ukrainian)
        yield 'Russian 1 gives form 0' => ['ru', 1, 0];
        yield 'Russian 2 gives form 1' => ['ru', 2, 1];
        yield 'Russian 5 gives form 2' => ['ru', 5, 2];
        yield 'Russian 11 gives form 2' => ['ru', 11, 2];
        yield 'Russian 21 gives form 0' => ['ru', 21, 0];

        // Rule 6: Czech, Slovak (n==1 ? 0 : n>=2 && n<=4 ? 1 : 2)
        yield 'Czech 1 gives form 0' => ['cs', 1, 0];
        yield 'Czech 3 gives form 1' => ['cs', 3, 1];
        yield 'Czech 5 gives form 2' => ['cs', 5, 2];

        // Unknown language falls back to 0
        yield 'unknown language returns 0' => ['xx', 42, 0];

        // Region variant fallback (e.g. de_CH falls back to de)
        yield 'German Swiss fallback' => ['de_CH', 1, 0];
        yield 'German Swiss plural fallback' => ['de_CH', 5, 1];
    }

    /**
     * @test
     * @dataProvider getPluralFormDataProvider
     */
    public function getPluralForm(string $languageKey, int|float $quantity, int $expectedResult): void
    {
        self::assertSame(
            $expectedResult,
            PluralFormUtility::getPluralForm($languageKey, $quantity)
        );
    }
}
