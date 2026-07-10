<?php

declare(strict_types=1);

require_once __DIR__ . '/php-cs-fixer/ClassBlockSeparationFixer.php';

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/../../Classes',
        __DIR__ . '/../../Configuration',
    ])
    ->name('*.php');

return (new PhpCsFixer\Config())->setRiskyAllowed(true)
    ->registerCustomFixers([
        new PSBits\Foundation\PhpCsFixer\Fixer\ClassBlockSeparationFixer(),
    ])
    ->setRules([
        '@PSR12'                          => true,
        'attribute_empty_parentheses'     => true,
        'binary_operator_spaces'          => [
            'operators' => [
                '.='  => 'align_single_space_minimal',
                '='   => 'align_single_space_minimal',
                '=>'  => 'align_by_scope',
                '??=' => 'align_single_space_minimal',
            ],
        ],
        'blank_line_before_statement'     => [
            'statements' => [
                'break',
                'continue',
                'declare',
                'do',
                'exit',
                'for',
                'foreach',
                'goto',
                'if',
                'return',
                'switch',
                'throw',
                'try',
                'while',
            ],
        ],
        'class_attributes_separation'     => [
            'elements' => [
                'case'         => 'none',
                'const'        => 'only_if_meta',
                'method'       => 'one',
                'property'     => 'only_if_meta',
                'trait_import' => 'only_if_meta',
            ],
        ],
        'declare_strict_types'            => true,
        'function_declaration'            => [
            'closure_fn_spacing'       => 'none',
            'closure_function_spacing' => 'none',
        ],
        'method_argument_space'           => [
            'after_heredoc' => false,
            'on_multiline'  => 'ensure_fully_multiline',
        ],
        'no_extra_blank_lines'            => [
            'tokens' => [
                'attribute',
                'break',
                'case',
                'comma',
                'continue',
                'curly_brace_block',
                'default',
                'extra',
                'parenthesis_brace_block',
                'return',
                'square_brace_block',
                'switch',
                'throw',
                'use',
            ],
        ],
        'no_trailing_whitespace'          => true,
        'no_unused_imports'               => true,
        'no_unneeded_control_parentheses' => true,
        'ordered_imports'                 => [
            'imports_order'  => [
                'class',
                'function',
                'const',
            ],
            'sort_algorithm' => 'alpha',
        ],
        'PSBits/class_block_separation'   => true,
        'single_quote'                    => true,
        'single_space_around_construct'   => [
            'constructs_followed_by_a_single_space' => ['named_argument'],
        ],
        'spaces_inside_parentheses'       => false,
        'trailing_comma_in_multiline'     => [
            'elements' => [
                'arrays',
            ],
        ],
        'yoda_style'                      => [
            'equal'            => true,
            'identical'        => true,
            'less_and_greater' => true,
        ],
    ])
    ->setFinder($finder);
