<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\PhpCsFixer\Fixer;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Analyzer\ArgumentsAnalyzer;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\FCT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;
use SplFileInfo;

use const T_CALLABLE;
use const T_NS_SEPARATOR;
use const T_PRIVATE;
use const T_PROTECTED;
use const T_PUBLIC;
use const T_STATIC;
use const T_STRING;
use const T_VAR;
use const T_VARIABLE;
use const T_WHITESPACE;

use function array_key_first;
use function count;
use function max;
use function strlen;

/**
 * Aligns names of consecutive class property declarations and of consecutive
 * parameter declarations in constructor method signatures in columns, padding
 * shorter and trimming longer declarations.
 */
final class PropertyNamesAlignmentFixer extends AbstractFixer
{
    /**
     * Token kinds that may be part of a declaration preceding a variable name.
     */
    private const array DECLARATION_PREFIX_TYPES = [
        T_CALLABLE,
        T_NS_SEPARATOR,
        T_PRIVATE,
        T_PROTECTED,
        T_PUBLIC,
        T_STATIC,
        T_STRING,
        T_VAR,
        CT::T_ARRAY_TYPEHINT,
        CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PRIVATE,
        CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PROTECTED,
        CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PUBLIC,
        CT::T_DISJUNCTIVE_NORMAL_FORM_TYPE_PARENTHESIS_CLOSE,
        CT::T_DISJUNCTIVE_NORMAL_FORM_TYPE_PARENTHESIS_OPEN,
        CT::T_NULLABLE_TYPE,
        CT::T_TYPE_ALTERNATION,
        CT::T_TYPE_INTERSECTION,
        FCT::T_PRIVATE_SET,
        FCT::T_PROTECTED_SET,
        FCT::T_PUBLIC_SET,
        FCT::T_READONLY,
    ];

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Aligns names of consecutive class property declarations and consecutive parameter declarations in constructor method signatures in columns.',
            [
                new CodeSample(
                    "<?php\nclass Demo\n{\n    protected string \$defaultLabelPath = '';\n    protected string \$tableName = '';\n    protected readonly NameResolver \$nameResolver;\n}\n"
                ),
            ],
        );
    }

    public function getName(): string
    {
        return 'PSBits/property_names_alignment';
    }

    /**
     * {@inheritdoc}
     *
     * Must run after ClassBlockSeparationFixer, MethodArgumentSpaceFixer.
     * Must run before BinaryOperatorSpacesFixer.
     */
    public function getPriority(): int
    {
        return 20;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound([T_VARIABLE]);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        $elementsByClass = [];

        foreach ((new TokensAnalyzer($tokens))->getClassyElements() as $index => $element) {
            $element['index'] = $index;
            $elementsByClass[$element['classIndex']][] = $element;
        }

        foreach ($elementsByClass as $elements) {
            $this->fixProperties($tokens, $elements);
            $this->fixConstructorParameters($tokens, $elements);
        }
    }

    /**
     * Align the variable names of all candidates of a block in one column. The
     * column sits right after the longest declaration prefix of the block plus a
     * single space, so shorter declarations are padded and longer ones trimmed.
     *
     * @param list<array{0: int, 1: int, 2: int}> $block [variableIndex, firstDeclIndex, separatorEndIndex]
     */
    private function alignBlock(Tokens $tokens, array $block): void
    {
        if ([] === $block) {
            return;
        }

        $naturalColumns = [];
        $targetColumn = 0;

        foreach ($block as $position => [$variableIndex, $firstDeclIndex]) {
            $previousToken = $tokens[$variableIndex - 1];

            if (!$previousToken->isGivenKind(T_WHITESPACE) || str_contains($previousToken->getContent(), "\n")) {
                continue;
            }

            $column = $this->getVariableColumn($tokens, $variableIndex, $firstDeclIndex);
            $naturalColumns[$position] = $column - strlen($previousToken->getContent()) + 1;
            $targetColumn = max($targetColumn, $naturalColumns[$position]);
        }

        if ([] === $naturalColumns) {
            return;
        }

        foreach ($naturalColumns as $position => $naturalColumn) {
            $variableIndex = $block[$position][0];

            $tokens[$variableIndex - 1] = new Token(
                [
                    T_WHITESPACE,
                    str_repeat(' ', $targetColumn - $naturalColumn + 1),
                ]
            );
        }
    }

    private function alignConstructorParameters(Tokens $tokens, int $functionIndex): void
    {
        $openParenthesisIndex = $tokens->getNextTokenOfKind($functionIndex, ['(']);
        $closeParenthesisIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS, $openParenthesisIndex);

        $arguments = (new ArgumentsAnalyzer())->getArguments($tokens, $openParenthesisIndex, $closeParenthesisIndex);

        /** @var list<array{0: int, 1: int, 2: int}> $block [variableIndex, firstDeclIndex, separatorEndIndex] */
        $block = [];

        foreach ($arguments as $argumentStartIndex => $argumentEndIndex) {
            $variableIndexes = $tokens->findGivenKind(T_VARIABLE, $argumentStartIndex, $argumentEndIndex + 1);
            $variableIndex = [] === $variableIndexes ? null : array_key_first($variableIndexes);

            if (null === $variableIndex) {
                $this->alignBlock($tokens, $block);
                $block = [];

                continue;
            }

            $firstDeclIndex = $this->findDeclarationStart($tokens, $variableIndex, $argumentStartIndex);
            $declarationEndIndex = $tokens->getPrevNonWhitespace($argumentEndIndex);

            if (!$this->isOnOwnLine($tokens, $firstDeclIndex) || !$this->isSingleLine(
                    $tokens,
                    $firstDeclIndex,
                    $declarationEndIndex
                )) {
                $this->alignBlock($tokens, $block);
                $block = [];

                continue;
            }

            if ([] !== $block && !$this->isGapClean($tokens, $block[count($block) - 1][2], $firstDeclIndex)) {
                $this->alignBlock($tokens, $block);
                $block = [];
            }

            $block[] = [
                $variableIndex,
                $firstDeclIndex,
                $argumentEndIndex,
            ];
        }

        $this->alignBlock($tokens, $block);
    }

    /**
     * Find the first token of the declaration of a variable.
     */
    private function findDeclarationStart(Tokens $tokens, int $variableIndex, ?int $limit = null): int
    {
        $index = $variableIndex;

        while (true) {
            $previousMeaningfulIndex = $tokens->getPrevMeaningfulToken($index);

            if (null === $previousMeaningfulIndex || (null !== $limit && $previousMeaningfulIndex <= $limit) || !$tokens[$previousMeaningfulIndex]->isGivenKind(
                    self::DECLARATION_PREFIX_TYPES
                )) {
                break;
            }

            $index = $previousMeaningfulIndex;
        }

        return $index;
    }

    /**
     * @param list<array{classIndex: int, index: int, token: Token, type: string}> $elements
     */
    private function fixConstructorParameters(Tokens $tokens, array $elements): void
    {
        foreach ($elements as $element) {
            if ('method' === $element['type'] && '__construct' === $this->getMethodName($tokens, $element['index'])) {
                $this->alignConstructorParameters($tokens, $element['index']);
            }
        }
    }

    /**
     * @param list<array{classIndex: int, index: int, token: Token, type: string}> $elements
     */
    private function fixProperties(Tokens $tokens, array $elements): void
    {
        /** @var list<array{0: int, 1: int, 2: int}> $block [variableIndex, firstDeclIndex, separatorEndIndex] */
        $block = [];

        foreach ($elements as $element) {
            if ('property' !== $element['type']) {
                $this->alignBlock($tokens, $block);
                $block = [];

                continue;
            }

            $variableIndex = $element['index'];
            $firstDeclIndex = $this->findDeclarationStart($tokens, $variableIndex);
            $separatorEndIndex = $this->getPropertyEnd($tokens, $variableIndex);

            if (null === $separatorEndIndex || !$this->isOnOwnLine($tokens, $firstDeclIndex) || !$this->isSingleLine(
                    $tokens,
                    $firstDeclIndex,
                    $separatorEndIndex
                )) {
                $this->alignBlock($tokens, $block);
                $block = [];

                continue;
            }

            if ([] !== $block && !$this->isGapClean($tokens, $block[count($block) - 1][2], $firstDeclIndex)) {
                $this->alignBlock($tokens, $block);
                $block = [];
            }

            $block[] = [
                $variableIndex,
                $firstDeclIndex,
                $separatorEndIndex,
            ];
        }

        $this->alignBlock($tokens, $block);
    }

    /**
     * Return the name of a method or null if the function is not named.
     */
    private function getMethodName(Tokens $tokens, int $functionIndex): ?string
    {
        $nameIndex = $tokens->getNextMeaningfulToken($functionIndex);

        return $tokens[$nameIndex]->isGivenKind(T_STRING) ? $tokens[$nameIndex]->getContent() : null;
    }

    /**
     * Find the terminating semicolon of a property declaration, or return null if the
     * declaration is not a simple single statement (e.g. property hooks).
     */
    private function getPropertyEnd(Tokens $tokens, int $variableIndex): ?int
    {
        $endIndex = $tokens->getNextTokenOfKind($variableIndex, [';']);

        if (null === $endIndex || [] !== $tokens->findGivenKind(
                CT::T_PROPERTY_HOOK_BRACE_OPEN,
                $variableIndex,
                $endIndex
            )) {
            return null;
        }

        return $endIndex;
    }

    /**
     * Return the zero based column at which a variable starts within its line.
     */
    private function getVariableColumn(Tokens $tokens, int $variableIndex, int $firstDeclIndex): int
    {
        $column = 0;

        $previousIndex = $firstDeclIndex - 1;

        if ($tokens[$previousIndex]->isGivenKind(T_WHITESPACE)) {
            $content = $tokens[$previousIndex]->getContent();
            $lineBreakPosition = strrpos($content, "\n");

            if (false !== $lineBreakPosition) {
                $column = strlen(substr($content, $lineBreakPosition + 1));
            }
        }

        for ($i = $firstDeclIndex; $i < $variableIndex; ++$i) {
            $column += strlen($tokens[$i]->getContent());
        }

        return $column;
    }

    /**
     * Check that only a single line break, single line comments or a separating
     * comma occur between two consecutive declarations.
     */
    private function isGapClean(Tokens $tokens, int $afterIndex, int $beforeIndex): bool
    {
        for ($i = $afterIndex + 1; $i < $beforeIndex; ++$i) {
            $token = $tokens[$i];

            if ($token->isGivenKind(T_WHITESPACE)) {
                if (substr_count($token->getContent(), "\n") > 1) {
                    return false;
                }

                continue;
            }

            if ($token->isComment()) {
                if (str_contains($token->getContent(), "\n")) {
                    return false;
                }

                continue;
            }

            if ($token->equals(',')) {
                continue;
            }

            return false;
        }

        return true;
    }

    /**
     * Check that a declaration starts on its own line.
     */
    private function isOnOwnLine(Tokens $tokens, int $firstDeclIndex): bool
    {
        $previousIndex = $firstDeclIndex - 1;

        return $tokens[$previousIndex]->isGivenKind(T_WHITESPACE) && str_contains(
                $tokens[$previousIndex]->getContent(),
                "\n"
            );
    }

    /**
     * Check that a declaration spans exactly one line.
     */
    private function isSingleLine(Tokens $tokens, int $startIndex, int $endIndex): bool
    {
        for ($i = $startIndex; $i <= $endIndex; ++$i) {
            if (str_contains($tokens[$i]->getContent(), "\n")) {
                return false;
            }
        }

        return true;
    }
}
