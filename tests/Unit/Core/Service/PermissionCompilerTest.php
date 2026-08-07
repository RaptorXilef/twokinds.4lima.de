<?php

declare(strict_types=1);

use App\Core\Service\PermissionCompiler;

\covers(PermissionCompiler::class);

\beforeEach(function (): void {
    $this->compiler = new PermissionCompiler();

    $this->structure = [
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
});

\it('denies everything if no permissions are given', function (): void {
    $result = $this->compiler->compile($this->structure, []);

    \expect($result['comics.manage'])->toBeFalse()
        ->and($result['comics.edit'])->toBeFalse()
        ->and($result['system.manage'])->toBeFalse();
});

\it('allows everything if wildcard is present', function (): void {
    $result = $this->compiler->compile($this->structure, ['*']);

    \expect($result['comics.manage'])->toBeTrue()
        ->and($result['comics.edit'])->toBeTrue()
        ->and($result['system.manage'])->toBeTrue();
});

\it('inherits parent permissions to children automatically', function (): void {
    // Wenn 'comics.manage' erlaubt ist, müssen 'edit' und 'delete' auch true sein
    $result = $this->compiler->compile($this->structure, ['comics.manage']);

    \expect($result['comics.manage'])->toBeTrue()
        ->and($result['comics.edit'])->toBeTrue()
        ->and($result['comics.delete'])->toBeTrue()
        ->and($result['system.manage'])->toBeFalse(); // Anderer Zweig bleibt false
});

\it('respects explicit deny rules on inherited children', function (): void {
    // Erlaubt das Verwalten von Comics, aber verbietet explizit das Löschen (Minus davor)
    $result = $this->compiler->compile($this->structure, ['comics.manage', '-comics.delete']);

    \expect($result['comics.manage'])->toBeTrue()
        ->and($result['comics.edit'])->toBeTrue()
        ->and($result['comics.delete'])->toBeFalse(); // Durch explizites Deny verboten!
});
