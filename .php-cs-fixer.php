<?php

$finder = new PhpCsFixer\Finder()
    ->in(__DIR__)
;

return new PhpCsFixer\Config()
    ->setRules([
        '@PhpCsFixer' => true,
        'align_multiline_comment' => false,
    ])
    ->setFinder($finder)
    ;
