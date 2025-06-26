<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Utility\Localization;

/**
 * Class PluralFormsUtility
 *
 * Calculates the plural form for a given quantity in a given language.
 *
 * Source for calculation:
 * http://docs.translatehouse.org/projects/localization-guide/en/latest/l10n/pluralforms.html
 *
 * Other resources:
 * https://www.unicode.org/cldr/charts/43/supplemental/language_plural_rules.html
 * https://cldr.unicode.org/index/cldr-spec/plural-rules
 *
 * @package PSBits\Foundation\Utility\Localization
 */
class PluralFormUtility
{
    private const array LANGUAGE_RULE_MAPPING = [
        'ach'   => 1,
        'af'    => 2,
        'ak'    => 1,
        'am'    => 1,
        'an'    => 2,
        'anp'   => 2,
        'ar'    => 16,
        'arn'   => 1,
        'as'    => 2,
        'ast'   => 2,
        'ay'    => 0,
        'az'    => 2,
        'be'    => 18,
        'bg'    => 2,
        'bn'    => 2,
        'bo'    => 0,
        'br'    => 1,
        'brx'   => 2,
        'bs'    => 18,
        'ca'    => 2,
        'cgg'   => 0,
        'cs'    => 6,
        'csb'   => 13,
        'cy'    => 9,
        'da'    => 2,
        'de'    => 2,
        'doi'   => 2,
        'dz'    => 0,
        'el'    => 2,
        'en'    => 2,
        'eo'    => 2,
        'es'    => 2,
        'et'    => 2,
        'eu'    => 2,
        'fa'    => 1,
        'ff'    => 2,
        'fi'    => 2,
        'fil'   => 1,
        'fo'    => 2,
        'fr'    => 1,
        'fur'   => 2,
        'fy'    => 2,
        'ga'    => 12,
        'gd'    => 14,
        'gl'    => 2,
        'gu'    => 2,
        'gun'   => 1,
        'ha'    => 2,
        'he'    => 2,
        'hi'    => 2,
        'hne'   => 2,
        'hr'    => 18,
        'hu'    => 2,
        'hy'    => 2,
        'ia'    => 2,
        'id'    => 0,
        'is'    => 4,
        'it'    => 2,
        'ja'    => 0,
        'jbo'   => 0,
        'jv'    => 3,
        'ka'    => 0,
        'kk'    => 2,
        'kl'    => 2,
        'km'    => 0,
        'kn'    => 2,
        'ko'    => 0,
        'ku'    => 2,
        'kw'    => 7,
        'ky'    => 2,
        'lb'    => 2,
        'ln'    => 1,
        'lo'    => 0,
        'lt'    => 15,
        'lv'    => 8,
        'mai'   => 2,
        'me'    => 18,
        'mfe'   => 1,
        'mg'    => 1,
        'mi'    => 1,
        'mk'    => 4,
        'ml'    => 2,
        'mn'    => 2,
        'mni'   => 2,
        'mnk'   => 5,
        'mr'    => 2,
        'ms'    => 0,
        'mt'    => 17,
        'my'    => 0,
        'nah'   => 2,
        'nap'   => 2,
        'nb'    => 2,
        'ne'    => 2,
        'nl'    => 2,
        'nn'    => 2,
        'no'    => 2,
        'nso'   => 2,
        'oc'    => 1,
        'or'    => 2,
        'pa'    => 2,
        'pap'   => 2,
        'pl'    => 13,
        'pms'   => 2,
        'ps'    => 2,
        'pt'    => 2,
        'pt_BR' => 1,
        'rm'    => 2,
        'ro'    => 10,
        'ru'    => 18,
        'rw'    => 2,
        'sah'   => 0,
        'sat'   => 2,
        'sco'   => 2,
        'sd'    => 2,
        'se'    => 2,
        'si'    => 2,
        'sk'    => 6,
        'sl'    => 11,
        'so'    => 2,
        'son'   => 2,
        'sq'    => 2,
        'sr'    => 18,
        'su'    => 0,
        'sv'    => 2,
        'sw'    => 2,
        'ta'    => 2,
        'te'    => 2,
        'tg'    => 1,
        'th'    => 0,
        'ti'    => 1,
        'tk'    => 2,
        'tr'    => 1,
        'tt'    => 0,
        'ug'    => 0,
        'uk'    => 18,
        'ur'    => 2,
        'uz'    => 1,
        'vi'    => 0,
        'wa'    => 1,
        'wo'    => 0,
        'yo'    => 2,
    ];

    /**
     * @param string    $languageKey ISO code of language
     * @param int|float $quantity
     *
     * @return int Returns 0 if no rule is defined for given language.
     */
    public static function getPluralForm(string $languageKey, int|float $quantity): int
    {
        // Example: If $languageKey is "de_CH" and there is no rule defined for it, try "de".
        if (!isset(static::LANGUAGE_RULE_MAPPING[$languageKey])) {
            $languageKeyParts = preg_split('/[_-]/', $languageKey);
            $languageKey = (string)array_shift($languageKeyParts);
        }

        if (!isset(static::LANGUAGE_RULE_MAPPING[$languageKey])) {
            return 0;
        }

        return match (static::LANGUAGE_RULE_MAPPING[$languageKey]) {
            0 => 0, // nplurals=1; plural=0;
            1 => (1 < $quantity) ? 1 : 0, // nplurals=2; plural=(n > 1);
            2 => (1 !== $quantity) ? 1 : 0, // nplurals=2; plural=(n != 1);
            3 => (0 !== $quantity) ? 1 : 0, // nplurals=2; plural=(n != 0);
            4 => (1 !== $quantity % 10 || 11 === $quantity % 100) ? 1 : 0, // nplurals=2; plural=(n%10!=1 || n%100==11);
            5 => (0 === $quantity) ? 0 : ((1 === $quantity) ? 1 : 2), // nplurals=3; plural=(n==0 ? 0 : n==1 ? 1 : 2);
            6 => (1 === $quantity) ? 0 : ((2 <= $quantity && 4 >= $quantity) ? 1 : 2), // nplurals=3; plural=(n==1) ? 0 : (n>=2 && n<=4) ? 1 : 2;
            7 => (1 === $quantity) ? 0 : ((2 === $quantity) ? 1 : (($quantity === 3) ? 2 : 3)), // nplurals=4; plural=(n==1) ? 0 : (n==2) ? 1 : (n == 3) ? 2 : 3;
            8 => (1 === $quantity % 10 && 11 !== $quantity % 100) ? 0 : (0 !== $quantity ? 1 : 2), // nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n != 0 ? 1 : 2);
            9 => (1 === $quantity) ? 0 : ((2 === $quantity) ? 1 : ((8 !== $quantity && 11 !== $quantity) ? 2 : 3)), // nplurals=4; plural=(n==1) ? 0 : (n==2) ? 1 : (n != 8 && n != 11) ? 2 : 3;
            10 => (1 === $quantity) ? 0 : ((0 === $quantity || (0 < $quantity % 100 && 20 > $quantity % 100)) ? 1 : 2), // nplurals=3; plural=(n==1 ? 0 : (n==0 || (n%100 > 0 && n%100 < 20)) ? 1 : 2);
            11 => (1 === $quantity % 100) ? 0 : ((2 === $quantity % 100) ? 1 : ((3 === $quantity % 100 || 4 === $quantity % 100) ? 2 : 3)), // nplurals=4; plural=(n%100==1 ? 0 : n%100==2 ? 1 : n%100==3 || n%100==4 ? 2 : 3);
            12 => (1 === $quantity) ? 0 : ((2 === $quantity) ? 1 : ((2 < $quantity && 7 > $quantity) ? 2 :((6 < $quantity && 11 > $quantity) ? 3 : 4))), // nplurals=5; plural=n==1 ? 0 : n==2 ? 1 : (n>2 && n<7) ? 2 :(n>6 && n<11) ? 3 : 4;
            13 => (1 === $quantity) ? 0 : ((2 <= $quantity % 10 && 4 >= $quantity % 10 && (10 > $quantity % 100 || 20 <= $quantity % 100)) ? 1 : 2), // nplurals=3; plural=(n==1) ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2;
            14 => (1 === $quantity || 11 === $quantity) ? 0 : ((2 === $quantity || 12 === $quantity) ? 1 : ((2 < $quantity && 20 > $quantity) ? 2 : 3)), // nplurals=4; plural=(n==1 || n==11) ? 0 : (n==2 || n==12) ? 1 : (n > 2 && n < 20) ? 2 : 3;
            15 => (1 === $quantity % 10 && 11 !== $quantity % 100) ? 0 : ((2 <= $quantity % 10 && (10 > $quantity % 100 || 20 <= $quantity % 100)) ? 1 : 2), // nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n%10>=2 && (n%100<10 || n%100>=20) ? 1 : 2);
            16 => (0 === $quantity) ? 0 : ((1 === $quantity) ? 1 : ((2 === $quantity) ? 2 : ((3 <= $quantity % 100 && 10 >= $quantity % 100) ? 3 : ((11 <= $quantity % 100) ? 4 : 5)))), // nplurals=6; plural=(n==0 ? 0 : n==1 ? 1 : n==2 ? 2 : n%100>=3 && n%100<=10 ? 3 : n%100>=11 ? 4 : 5);
            17 => (1 === $quantity) ? 0 : ((0 === $quantity || (1 < $quantity % 100 && 11 > $quantity % 100)) ? 1 : ((10 < $quantity % 100 && 20 > $quantity % 100) ? 2 : 3)), // nplurals=4; plural=(n==1 ? 0 : n==0 || ( n%100>1 && n%100<11) ? 1 : (n%100>10 && n%100<20 ) ? 2 : 3);
            18 => (1 === $quantity % 10 && 11 !== $quantity % 100) ? 0 : ((2 <= $quantity % 10 && 4 >= $quantity % 10 && (10 > $quantity % 100 || 20 <= $quantity % 100) ? 1 : 2)), // nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2);
        };
    }
}
