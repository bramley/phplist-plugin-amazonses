<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('plugins/AmazonSes/vendor')
;

$config = new PhpCsFixer\Config();

return $config->setRules([
        '@PSR1' => true,
        '@PSR2' => true,
        '@Symfony' => true,
        'concat_space' => false,
        'phpdoc_no_alias_tag' => false,
        'yoda_style' => false,
        'array_syntax' => false,
        'no_superfluous_phpdoc_tags' => false,
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
            'imports_order' => ['class', 'function', 'const']
        ],
        'blank_line_after_namespace' => true,
        'visibility_required' => false,
    ])
    ->setFinder($finder)
;
