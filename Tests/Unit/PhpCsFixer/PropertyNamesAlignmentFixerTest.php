<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Tests\Unit\PhpCsFixer;

use Generator;
use PhpCsFixer\FixerFactory;
use PhpCsFixer\RuleSet\RuleSet;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PSBits\Foundation\PhpCsFixer\Fixer\PropertyNamesAlignmentFixer;
use SplFileInfo;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class PropertyNamesAlignmentFixer
 *
 * @package PSBits\Foundation\Tests\Unit\PhpCsFixer
 */
class PropertyNamesAlignmentFixerTest extends UnitTestCase
{
    public static function alignPropertiesDataProvider(): Generator
    {
        yield 'property names are aligned by the rightmost name' => [
            '<?php
class Foo
{
    protected string $defaultLabelPath = \'\';
    protected string $tableName = \'\';
    protected readonly NameResolver $nameResolver;
}
',
            '<?php
class Foo
{
    protected string                $defaultLabelPath = \'\';
    protected string                $tableName = \'\';
    protected readonly NameResolver $nameResolver;
}
',
        ];
        yield 'a single property is left as-is' => [
            '<?php
class Foo
{
    protected string $name = \'\';
}
',
            '<?php
class Foo
{
    protected string $name = \'\';
}
',
        ];
        yield 'mixed modifiers and types are aligned by the rightmost name' => [
            '<?php
class Foo
{
    public static string $a = \'\';
    public static int $b = 0;
    public static string|int $c = \'\';
}
',
            '<?php
class Foo
{
    public static string     $a = \'\';
    public static int        $b = 0;
    public static string|int $c = \'\';
}
',
        ];
    }

    public static function unchangedCodeDataProvider(): Generator
    {
        yield 'a method between properties breaks the block' => [
            '<?php
class Foo
{
    protected string $a = \'\';

    public function bar(): void
    {
    }

    protected string $b = \'\';
}
',
        ];
        yield 'a blank line between properties breaks the block' => [
            '<?php
class Foo
{
    protected string $a = \'\';

    protected string $b = \'\';
}
',
        ];
        yield 'a doc comment between properties breaks the block' => [
            '<?php
class Foo
{
    protected string $a = \'\';

    /**
     * The b.
     */
    protected string $b = \'\';
}
',
        ];
        yield 'a multiline property default breaks the block' => [
            '<?php
class Foo
{
    protected string $a = \'\';
    protected array $b = [
        1,
        2,
    ];
    protected string $c = \'\';
}
',
        ];
        yield 'an aligned block is left as-is' => [
            '<?php
class Foo
{
    protected string                $defaultLabelPath = \'\';
    protected string                $tableName        = \'\';
    protected readonly NameResolver $nameResolver;
}
',
        ];
    }

    public static function alignConstructorParametersDataProvider(): Generator
    {
        yield 'promoted property names are aligned by the rightmost name' => [
            '<?php
class Foo
{
    public function __construct(
        protected readonly ExtensionInformationService $extensionInformationService,
        protected readonly PackageManager $packageManager,
    ) {
    }
}
',
            '<?php
class Foo
{
    public function __construct(
        protected readonly ExtensionInformationService $extensionInformationService,
        protected readonly PackageManager              $packageManager,
    ) {
    }
}
',
        ];
        yield 'promoted and non promoted parameter names are aligned together' => [
            '<?php
class Foo
{
    public function __construct(
        protected string $a,
        string $b,
    ) {
    }
}
',
            '<?php
class Foo
{
    public function __construct(
        protected string $a,
        string           $b,
    ) {
    }
}
',
        ];
        yield 'a single line parameter list is left as-is' => [
            '<?php
class Foo
{
    public function __construct(protected string $a, protected string $b)
    {
    }
}
',
            '<?php
class Foo
{
    public function __construct(protected string $a, protected string $b)
    {
    }
}
',
        ];
        yield 'a multiline parameter breaks the block' => [
            '<?php
class Foo
{
    public function __construct(
        protected string $a,
        protected array $b = [
            1,
        ],
        protected string $c,
    ) {
    }
}
',
            '<?php
class Foo
{
    public function __construct(
        protected string $a,
        protected array $b = [
            1,
        ],
        protected string $c,
    ) {
    }
}
',
        ];
    }

    #[Test]
    #[DataProvider('alignPropertiesDataProvider')]
    public function propertyNamesAreAligned(string $input, string $expected): void
    {
        self::assertSame(
            $expected,
            $this->applyFixer(new PropertyNamesAlignmentFixer(), $input)
        );
    }

    #[Test]
    #[DataProvider('unchangedCodeDataProvider')]
    public function codeThatDoesNotRequireAlignmentIsLeftAsIs(string $input): void
    {
        self::assertSame(
            $input,
            $this->applyFixer(new PropertyNamesAlignmentFixer(), $input)
        );
    }

    public static function trimExcessWhitespaceDataProvider(): Generator
    {
        yield 'an over aligned block of properties is trimmed' => [
            '<?php
class Foo
{
    protected string          $a = \'\';
    protected string          $b = \'\';
}
',
            '<?php
class Foo
{
    protected string $a = \'\';
    protected string $b = \'\';
}
',
        ];
        yield 'a single over aligned property is trimmed' => [
            '<?php
class Foo
{
    protected string          $a = \'\';
}
',
            '<?php
class Foo
{
    protected string $a = \'\';
}
',
        ];
        yield 'mixed padding aligns to the longest prefix and trims the excess' => [
            '<?php
class Foo
{
    protected string $a = \'\';
    protected readonly NameResolver          $b;
}
',
            '<?php
class Foo
{
    protected string                $a = \'\';
    protected readonly NameResolver $b;
}
',
        ];
        yield 'over aligned constructor parameters are trimmed' => [
            '<?php
class Foo
{
    public function __construct(
        protected bool    $a,
        protected int     $b,
    ) {
    }
}
',
            '<?php
class Foo
{
    public function __construct(
        protected bool $a,
        protected int  $b,
    ) {
    }
}
',
        ];
    }

    #[Test]
    #[DataProvider('alignConstructorParametersDataProvider')]
    public function constructorParameterNamesAreAligned(string $input, string $expected): void
    {
        self::assertSame(
            $expected,
            $this->applyFixer(new PropertyNamesAlignmentFixer(), $input)
        );
    }

    #[Test]
    #[DataProvider('trimExcessWhitespaceDataProvider')]
    public function excessWhitespaceIsTrimmed(string $input, string $expected): void
    {
        self::assertSame(
            $expected,
            $this->applyFixer(new PropertyNamesAlignmentFixer(), $input)
        );
    }

    #[Test]
    public function fixingIsIdempotent(): void
    {
        $input = '<?php
class Foo
{
    protected string $defaultLabelPath = \'\';
    protected string $tableName = \'\';
    protected readonly NameResolver $nameResolver;

    protected string          $x = \'\';
    protected string          $y = \'\';
}
';

        $fixedOnce = $this->applyFixer(new PropertyNamesAlignmentFixer(), $input);

        self::assertSame(
            $fixedOnce,
            $this->applyFixer(new PropertyNamesAlignmentFixer(), $fixedOnce)
        );
    }

    /**
     * The alignment must run before binary_operator_spaces, so the padding inserted
     * by this fixer is taken into account when the assignment signs are aligned.
     */
    #[Test]
    public function alignmentCooperatesWithBinaryOperatorSpaces(): void
    {
        $input = '<?php
class Foo
{
    protected string $defaultLabelPath = \'\';
    protected string $tableName = \'\';
    protected readonly NameResolver $nameResolver;
}
';
        $expected = '<?php
class Foo
{
    protected string                $defaultLabelPath = \'\';
    protected string                $tableName        = \'\';
    protected readonly NameResolver $nameResolver;
}
';

        $fixers = (new FixerFactory())
            ->registerBuiltInFixers()
            ->registerCustomFixers([new PropertyNamesAlignmentFixer()])
            ->useRuleSet(
                new RuleSet(
                    [
                        'binary_operator_spaces'          => [
                            'operators' => [
                                '=' => 'align_single_space_minimal',
                            ],
                        ],
                        'PSBits/property_names_alignment' => true,
                    ]
                )
            )
            ->getFixers();

        \usort($fixers, static fn($a, $b): int => [$b->getPriority(), $b->getName()] <=> [$a->getPriority(), $a->getName()]);

        $tokens = Tokens::fromCode($input);

        foreach ($fixers as $fixer) {
            if ($fixer->isCandidate($tokens)) {
                $fixer->fix(new SplFileInfo(__FILE__), $tokens);
            }
        }

        self::assertSame($expected, $tokens->generateCode());
    }

    private function applyFixer(PropertyNamesAlignmentFixer $fixer, string $code): string
    {
        $tokens = Tokens::fromCode($code);

        $fixer->fix(new SplFileInfo(__FILE__), $tokens);

        return $tokens->generateCode();
    }
}
