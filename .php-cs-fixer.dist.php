<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$rules = [
    '@PSR2' => true,
    'array_syntax' => [
        'syntax' => 'short',
    ],
    'blank_line_before_statement' => true,
    'cast_spaces' => true,
    'concat_space' => [
        'spacing' => 'none',
    ],
    'ereg_to_preg' => true,
    'no_blank_lines_after_phpdoc' => true,
    'no_short_bool_cast' => true,
    'no_unneeded_control_parentheses' => true,
    'no_unused_imports' => true,
    'no_whitespace_in_blank_line' => true,
    'ordered_imports' => true,
    'phpdoc_align' => false,
    'phpdoc_indent' => true,
    'general_phpdoc_tag_rename' => true,
    'phpdoc_inline_tag_normalizer' => true,
    'phpdoc_tag_type' => true,
    'phpdoc_no_access' => true,
    'phpdoc_no_package' => true,
    'phpdoc_order' => true,
    'phpdoc_scalar' => true,
    'phpdoc_separation' => true,
    'phpdoc_to_comment' => true,
    'phpdoc_trim' => true,
    'phpdoc_types' => true,
    'phpdoc_var_without_name' => true,
    'self_accessor' => true,
    'single_quote' => true,
    'space_after_semicolon' => true,
    'standardize_not_equals' => true,
    'ternary_operator_spaces' => true,
    'trailing_comma_in_multiline' => true,
    'trim_array_spaces' => true,
    'unary_operator_spaces' => true,
    'line_ending' => true,
    'blank_line_after_namespace' => true,
];

$config = new Config();

return $config
    ->setRules($rules)
    ->setFinder(Finder::create()->in(__DIR__.'/src'))
    ->setUsingCache(true)
    ->setRiskyAllowed(true);
