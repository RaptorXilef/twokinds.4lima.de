<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Actions\Frontend;

use App\Application\Actions\Frontend\Error403Action;
use App\Application\Actions\Frontend\Error404Action;
use App\Application\Actions\Frontend\ForgotPasswordAction;
use App\Application\Actions\Frontend\ImprintAction;
use App\Application\Actions\Frontend\LoginAction;
use App\Application\Actions\Frontend\PrivacyAction;
use App\Application\Actions\Frontend\ProjectInfoAction;
use App\Application\Actions\Frontend\RegisterAction;
use App\Application\Actions\Frontend\ResendVerificationAction;
use App\Application\Actions\Frontend\ResetPasswordAction;
use App\Application\Http\ServerRequest;
use App\Application\Response\HtmlResponse;
use App\Application\Session\SessionManager;
use App\Application\View\TemplateRenderer;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\Security\RateLimiterInterface;
use App\Contracts\Storage\MagicLinkRepositoryInterface;
use App\Contracts\Storage\RoleRepositoryInterface;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Contracts\System\AssetHelperInterface;
use App\Contracts\System\ImageStorageInterface;
use App\Contracts\System\JsonHelperInterface;
use App\Contracts\System\SystemInfoInterface;
use App\Contracts\Utils\ClockInterface;
use App\Core\Service\AuthService;
use App\Core\Service\MagicLinkService;
use App\Infrastructure\Utils\SystemClock;

\uses()->group('application', 'actions', 'frontend');

\beforeEach(function (): void {
    if (\session_status() === \PHP_SESSION_NONE) {
        \session_start();
    }
    $_SESSION = [];
});

\it('renders simple static pages correctly with real templates', function (string $class, string $title, int $code): void {
    $config = $this->createStub(ConfigInterface::class);
    $config->method('get')->willReturnMap([
        ['root_path', null, \realpath(__DIR__ . '/../../../../../')],
        ['site_title', null, 'Test Title'],
        ['site_description', null, 'Test Desc'],
        ['base_url', null, 'http://localhost'],
        ['email_user', null, 'admin'],
        ['email_domain', null, 'test.com'],
    ]);
    $config->method('getBaseUrl')->willReturn('http://localhost');

    $sessionManager = new SessionManager(new SystemClock());

    $renderer = new TemplateRenderer(
        $config,
        $this->createStub(ImageStorageInterface::class),
        $this->createStub(JsonHelperInterface::class),
        $sessionManager,
        $this->createStub(SystemInfoInterface::class),
        $this->createStub(AssetHelperInterface::class),
    );

    $action = new $class($renderer);
    $response = $action->execute(new ServerRequest());

    \expect($response)->toBeInstanceOf(HtmlResponse::class)
        ->and($response->statusCode)->toBe($code)
        ->and($response->html)->toContain(\htmlspecialchars($title));
})->with([
    [ImprintAction::class, 'Impressum & Lizenz', 200],
    [PrivacyAction::class, 'Datenschutzerklärung', 200],
    [ProjectInfoAction::class, 'Über das Projekt & FAQ', 200],
    [Error403Action::class, 'Fehler 403', 403],
    [Error404Action::class, 'Fehler 404', 404],
])->covers(ImprintAction::class, PrivacyAction::class, ProjectInfoAction::class, Error403Action::class, Error404Action::class);

\it('renders auth static pages correctly with real templates', function (string $class, string $title, array $deps): void {
    $config = $this->createStub(ConfigInterface::class);
    $config->method('get')->willReturnMap([
        ['root_path', null, \realpath(__DIR__ . '/../../../../../')],
        ['site_title', null, 'Test Title'],
        ['site_description', null, 'Test Desc'],
        ['base_url', null, 'http://localhost'],
        ['email_user', null, 'admin'],
        ['email_domain', null, 'test.com'],
    ]);
    $config->method('getBaseUrl')->willReturn('http://localhost');

    $sessionManager = new SessionManager(new SystemClock());

    $renderer = new TemplateRenderer(
        $config,
        $this->createStub(ImageStorageInterface::class),
        $this->createStub(JsonHelperInterface::class),
        $sessionManager,
        $this->createStub(SystemInfoInterface::class),
        $this->createStub(AssetHelperInterface::class),
    );

    $dependencies = [$renderer];

    if (\in_array('auth', $deps, true)) {
        // Echter AuthService, der aber aufgrund leerer $_SESSION = Gast ist
        $auth = new AuthService(
            $config,
            $this->createStub(RoleRepositoryInterface::class),
            $this->createStub(RateLimiterInterface::class),
            $sessionManager,
            $this->createStub(UserRepositoryInterface::class),
        );
        $dependencies[] = $auth;
    }
    if (\in_array('magic', $deps, true)) {
        // Echter MagicLinkService mit leerem Repository (token nicht gefunden)
        $magic = new MagicLinkService(
            $this->createStub(ClockInterface::class),
            $config,
            $this->createStub(MagicLinkRepositoryInterface::class),
        );
        $dependencies[] = $magic;
    }

    $action = new $class(...$dependencies);
    $request = new ServerRequest(get: ['token' => 'dummy']);
    $response = $action->execute($request);

    \expect($response)->toBeInstanceOf(HtmlResponse::class)
        ->and($response->statusCode)->toBe(200)
        ->and($response->html)->toContain(\htmlspecialchars($title));
})->with([
    [LoginAction::class, 'Einloggen', ['auth']],
    [RegisterAction::class, 'Konto erstellen', ['auth']],
    [ForgotPasswordAction::class, 'Passwort vergessen', []],
    [ResendVerificationAction::class, 'Bestätigungsmail erneut anfordern', []],
    [ResetPasswordAction::class, 'Neues Passwort festlegen', ['magic']],
])->covers(LoginAction::class, RegisterAction::class, ForgotPasswordAction::class, ResendVerificationAction::class, ResetPasswordAction::class);
