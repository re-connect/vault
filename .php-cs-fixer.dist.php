<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'fully_qualified_strict_types' => [
            'import_symbols' => true,
            'leading_backslash_in_global_namespace' => true,
        ],
        'global_namespace_import' => [
            'import_classes' => null,
            'import_functions' => false,
            'import_constants' => false,
        ],
    ])
    ->setFinder($finder)
;
