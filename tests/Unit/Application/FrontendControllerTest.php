<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Contracts\ActionInterface;
use App\Application\FrontendController;
use App\Application\Http\ServerRequest;
use App\Application\Middleware\SecurityHeadersMiddleware;
use App\Application\Response\HtmlResponse;
use App\Application\Response\JsonResponse;
use App\Application\Routing\ActionRegistry;
use App\Application\Routing\UniversalActionFactory;
use App\Application\Session\SessionManager;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\DependencyInjection\ContainerInterface;
use App\Contracts\System\RouteCacheInterface;
use App\Infrastructure\Utils\SystemClock;
use Closure;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;

\uses()->group('application', 'controller');

\beforeEach(function (): void {
    if (\session_status() === \PHP_SESSION_NONE) {
        \session_start();
    }
    $_SESSION = [];
});

\it('returns 503 JSON response when API is accessed during maintenance mode', function (): void {
    $stub = Closure::bind(fn (string $c): Stub => $this->createStub($c), $this, self::class);

    $config = $stub(ConfigInterface::class);
    $config->method('getBaseUrl')->willReturn('https://tk.local');
    $config->method('get')->willReturnCallback(function (string $key, mixed $default = null) {
        return match ($key) {
            'maintenance_mode_admin' => true,
            'admin_dev_mode' => false,
            default => $default,
        };
    });

    $cache = $stub(RouteCacheInterface::class);
    $cache->method('load')->willReturn([
        'exact' => ['GET' => ['/api/delete_user' => ['class' => 'App\\Application\\Actions\\Api\\Admin\\DeleteUserAction', 'auth' => true]]],
        'dynamic' => [],
    ]);

    $registry = new ActionRegistry($config, $cache);

    // STUB: Hier rufen wir ->get() nicht auf, da die Pipeline durch Wartung abgebrochen wird.
    $container = $stub(ContainerInterface::class);
    $factory = new UniversalActionFactory($registry, $container);

    $session = new SessionManager(new SystemClock());
    $security = new SecurityHeadersMiddleware($session);

    $controller = new FrontendController($config, $factory, $security, $session);

    $response = $controller->handleRequest(new ServerRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/delete_user']));

    \expect($response)->toBeInstanceOf(JsonResponse::class)
        ->and($response->statusCode)->toBe(503)
        ->and($response->data['error'])->toBe('System wird gewartet.');
})->covers(FrontendController::class);

\it('bypasses maintenance mode for allowed routes like login', function (): void {
    $stub = Closure::bind(fn (string $c): Stub => $this->createStub($c), $this, self::class);
    $mock = Closure::bind(fn (string $c): MockObject => $this->createMock($c), $this, self::class);

    $config = $stub(ConfigInterface::class);
    $config->method('getBaseUrl')->willReturn('https://tk.local');
    $config->method('get')->willReturnCallback(function (string $key, mixed $default = null) {
        return match ($key) {
            'maintenance_mode' => true,
            'admin_dev_mode' => false,
            default => $default,
        };
    });

    $cache = $stub(RouteCacheInterface::class);
    $cache->method('load')->willReturn([
        'exact' => ['POST' => ['/api/admin_login' => ['class' => 'MockedLoginAction', 'auth' => false]]],
        'dynamic' => [],
    ]);

    $registry = new ActionRegistry($config, $cache);

    // MOCK: Wir erwarten explizit, dass die Factory aufgerufen wird, da Wartung umgangen wird.
    $container = $mock(ContainerInterface::class);
    $container->expects($this->once())->method('get')->willReturn(new class implements ActionInterface {
        public function execute(ServerRequest $request): mixed
        {
            return new HtmlResponse('Login Page', 200);
        }
    });

    $factory = new UniversalActionFactory($registry, $container);

    $session = new SessionManager(new SystemClock());
    $security = new SecurityHeadersMiddleware($session);

    $controller = new FrontendController($config, $factory, $security, $session);
    $response = $controller->handleRequest(new ServerRequest(server: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/admin_login']));

    \expect($response)->toBeInstanceOf(HtmlResponse::class)
        ->and($response->html)->toBe('Login Page');
})->covers(FrontendController::class);

\it('executes pipeline and returns 404 if action not found', function (): void {
    $stub = Closure::bind(fn (string $c): Stub => $this->createStub($c), $this, self::class);

    $config = $stub(ConfigInterface::class);
    $config->method('getBaseUrl')->willReturn('https://tk.local');
    $config->method('get')->willReturnCallback(function (string $key, mixed $default = null) {
        return match ($key) {
            'maintenance_mode' => false,
            'maintenance_mode_admin' => false,
            'admin_dev_mode' => false,
            default => $default,
        };
    });

    $cache = $stub(RouteCacheInterface::class);
    $cache->method('load')->willReturn(['exact' => [], 'dynamic' => []]);

    $registry = new ActionRegistry($config, $cache);

    // STUB: Bei 404 wird der Container nach Error404Action gefragt, aber kein Mock Requirement gesetzt
    $container = $stub(ContainerInterface::class);
    $factory = new UniversalActionFactory($registry, $container);

    $session = new SessionManager(new SystemClock());
    $security = new SecurityHeadersMiddleware($session);

    $controller = new FrontendController($config, $factory, $security, $session);
    $response = $controller->handleRequest(new ServerRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/unknown-page']));

    \expect($response)->toBeInstanceOf(HtmlResponse::class)
        ->and($response->statusCode)->toBe(404)
        ->and($response->html)->toContain('404');
})->covers(FrontendController::class);
