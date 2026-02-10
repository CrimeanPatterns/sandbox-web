<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude(['bootstrap', 'storage', 'vendor'])
    ->name('*.php')
    ->name('_ide_helper')
    ->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
        'fopen_flags' => false,
        'protected_to_private' => false,
        'list_syntax' => ['syntax' => 'short'],
        'visibility_required' => [
            'elements' => ['property', 'method', 'const'],
        ],
        'ternary_to_null_coalescing' => true,
        'ordered_class_elements' => [
            'sort_algorithm' => 'none',
        ],
        'concat_space' => [
            'spacing' => 'one',
        ],
        'single_quote' => false,
        'yoda_style' => false,
        'blank_line_before_statement' => [
            'statements' => ['break', 'case', 'continue', 'declare', 'default', 'do', 'exit', 'for', 'foreach', 'goto', 'if', 'include', 'include_once', 'require', 'require_once', 'return', 'switch', 'throw', 'try', 'while', 'yield', 'yield_from'],
        ],
        'array_indentation' => true,
        'phpdoc_align' => false,
        'increment_style' => false,
        'native_function_invocation' => false,
        'operator_linebreak' => [
            'position' => 'beginning',
            'only_booleans' => true,
        ],
        // preserve /** Desc('...') */ annotations extractable
        'phpdoc_to_comment' => false,
        'no_multiline_whitespace_around_double_arrow' => false,
    ])
    ->setCacheFile(__DIR__ . '/.php_cs.cache')
    ->setFinder($finder)
;
