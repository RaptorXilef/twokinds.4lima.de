<?php

declare(strict_types=1);

use App\Core\Service\PermissionCompiler;

function getCompilerTestStructure(): array
{
    return [
        'comics' => [
            'key'      => 'comics.manage',
            'children' => [
                'edit'   => ['key' => 'comics.edit'],
                'delete' => ['key' => 'comics.delete'],
            ],
        ],
        'system' => [
            'key' => 'system.manage',
        ],
    ];
}

\it('denies everything if no permissions are given', function (): void {
    $compiler = new PermissionCompiler();
    $result   = $compiler->compile(\getCompilerTestStructure(), []);

    \expect($result['comics.manage'])->toBeFalse()
        ->and($result['comics.edit'])->toBeFalse()
        ->and($result['system.manage'])->toBeFalse();
})->covers(PermissionCompiler::class);

\it('allows everything if wildcard is present', function (): void {
    $compiler = new PermissionCompiler();
    $result   = $compiler->compile(\getCompilerTestStructure(), ['*']);

    \expect($result['comics.manage'])->toBeTrue()
        ->and($result['comics.edit'])->toBeTrue()
        ->and($result['system.manage'])->toBeTrue();
})->covers(PermissionCompiler::class);

\it('inherits parent permissions to children automatically', function (): void {
    // Wenn 'comics.manage' erlaubt ist, müssen 'edit' und 'delete' auch true sein
    $compiler = new PermissionCompiler();
    $result   = $compiler->compile(\getCompilerTestStructure(), ['comics.manage']);

    \expect($result['comics.manage'])->toBeTrue()
        ->and($result['comics.edit'])->toBeTrue()
        ->and($result['comics.delete'])->toBeTrue()
        ->and($result['system.manage'])->toBeFalse(); // Anderer Zweig bleibt false
})->covers(PermissionCompiler::class);

\it('respects explicit deny rules on inherited children', function (): void {
    // Erlaubt das Verwalten von Comics, aber verbietet explizit das Löschen (Minus davor)
    $compiler = new PermissionCompiler();
    $result   = $compiler->compile(\getCompilerTestStructure(), ['comics.manage', '-comics.delete']);

    \expect($result['comics.manage'])->toBeTrue()
        ->and($result['comics.edit'])->toBeTrue()
        ->and($result['comics.delete'])->toBeFalse(); // Durch explizites Deny verboten!
})->covers(PermissionCompiler::class);
