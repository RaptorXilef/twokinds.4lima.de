<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude(['var', 'vendor', 'tools']); // Ordner ignorieren

return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS' => true, // Der aktuelle Standard
        'array_syntax' => ['syntax' => 'short'],
    ])
    ->setFinder($finder);
