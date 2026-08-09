<?php

declare(strict_types=1);

use App\Application\Session\SessionManager;
use App\Infrastructure\Utils\SystemClock;

\uses()->group('application', 'session');

\beforeEach(function (): void {
    if (\session_status() === \PHP_SESSION_NONE) {
        \session_start();
    }
    $_SESSION = [];
});

\it('initializes session variables safely', function (): void {
    $clock = new SystemClock();
    $manager = new SessionManager($clock);

    \expect($_SESSION)->toHaveKey('session_created')
        ->and($_SESSION)->toHaveKey('last_activity')
        ->and($manager->getUserId())->toBe('');
})->covers(SessionManager::class);

\it('generates and retrieves CSRF tokens', function (): void {
    $manager = new SessionManager(new SystemClock());

    $token1 = $manager->initCsrfToken();
    \expect(\strlen($token1))->toBe(64);

    $token2 = $manager->getCsrfToken();
    \expect($token1)->toBe($token2);

    $manager->rotateCsrfToken();
    \expect($manager->getCsrfToken())->not->toBe($token1);
})->covers(SessionManager::class);

\it('handles adding and retrieving flash messages', function (): void {
    $manager = new SessionManager(new SystemClock());

    $manager->addFlash('success', 'Gespeichert!');
    $manager->addFlash('error', 'Fehler!');
    $manager->addFlash('error', 'Noch ein Fehler!');

    $flashes = $manager->getFlashes();

    \expect($flashes)->toHaveKey('success')
        ->and($flashes['success'][0])->toBe('Gespeichert!')
        ->and($flashes['error'])->toHaveCount(2);

    // Reading them should clear them from session
    $flashesAgain = $manager->getFlashes();
    \expect($flashesAgain)->toBeEmpty();
})->covers(SessionManager::class);

\it('stores and retrieves authentication data correctly', function (): void {
    $manager = new SessionManager(new SystemClock());

    $manager->setAuthSession('usr_555', 'admin', 'SuperChef', 'hashed_pass');

    \expect($manager->getUserId())->toBe('usr_555')
        ->and($manager->getAdminGroup())->toBe('admin')
        ->and($manager->getAdminUser())->toBe('SuperChef')
        ->and($manager->getAuthHash())->toBe('hashed_pass');
})->covers(SessionManager::class);

\it('clears form data', function (): void {
    $manager = new SessionManager(new SystemClock());

    $manager->setFormData(['email' => 'test@test.de']);
    \expect($manager->getFormData())->toBe(['email' => 'test@test.de']);

    $manager->clearFormData();
    \expect($manager->getFormData())->toBeEmpty();
})->covers(SessionManager::class);
