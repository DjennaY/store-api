<?php

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'declare_strict_types' => true,
        'strict_param' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_unused_imports' => true,
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
            'imports_order' => ['class', 'function', 'const'],
        ],
        'visibility_required' => true,
        'no_useless_else' => true,
        'binary_operator_spaces' => [
            'default' => 'single_space',
        ],
        'return_type_declaration' => ['space_before' => 'none'],
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()->in(['src', 'tests'])
    );
