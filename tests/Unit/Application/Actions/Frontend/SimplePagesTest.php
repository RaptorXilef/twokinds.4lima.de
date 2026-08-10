<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Actions\Frontend;

use App\Application\Actions\Frontend\ForgotPasswordAction;
use App\Application\Actions\Frontend\LoginAction;
use App\Application\Actions\Frontend\RegisterAction;
use App\Application\Actions\Frontend\ResendVerificationAction;
use App\Application\Actions\Frontend\ResetPasswordAction;
use App\Application\Http\ServerRequest;
use App\Application\Response\HtmlResponse;
use App\Application\Session\SessionManager;
use App\Application\View\TemplateRenderer;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\System\AssetHelperInterface;
use App\Contracts\System\ImageStorageInterface;
use App\Contracts\System\JsonHelperInterface;
use App\Contracts\System\SystemInfoInterface;
use App\Core\Service\AuthService;
use App\Core\Service\MagicLinkService;
use App\Infrastructure\Utils\SystemClock;

\uses()->group('application', 'actions', 'frontend', 'auth');

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

    if (\session_status() === \PHP_SESSION_NONE) {
        \session_start();
    }

    $renderer = new TemplateRenderer(
        $config,
        $this->createStub(ImageStorageInterface::class),
        $this->createStub(JsonHelperInterface::class),
        new SessionManager(new SystemClock()),
        $this->createStub(SystemInfoInterface::class),
        $this->createStub(AssetHelperInterface::class),
    );

    $dependencies = [$renderer];

    // Inject specific dependencies based on class requirements
    if (\in_array('auth', $deps, true)) {
        $auth = $this->createStub(AuthService::class);
        $auth->method('isLoggedIn')->willReturn(false); // Simulate guest user
        $dependencies[] = $auth;
    }
    if (\in_array('magic', $deps, true)) {
        $magic = $this->createStub(MagicLinkService::class);
        $magic->method('peekToken')->willReturn(null); // Simulate invalid/no token
        $dependencies[] = $magic;
    }

    $action = new $class(...$dependencies);

    // Pass a dummy token for reset password to avoid undefined index warnings
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
