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
use PHPUnit\Framework\MockObject\Stub;

\uses()->group('application', 'controller');

\beforeEach(function (): void {
    if (\session_status() === \PHP_SESSION_NONE) {
        \session_start();
    }
    $_SESSION = [];
});

function setupFrontendControllerTest(mixed $test, array $configMap = []): object
{
    $stub = Closure::bind(fn (string $c): Stub => $test->createStub($c), $test, $test::class);

    $config = $stub(ConfigInterface::class);
    $config->method('getBaseUrl')->willReturn('https://tk.local');
    $config->method('get')->willReturnCallback(function (string $key, mixed $default = null) use ($configMap) {
        if (isset($configMap[$key])) {
            return $configMap[$key];
        }

        return match ($key) {
            'root_path' => \dirname(__DIR__, 3),
            'maintenance_mode' => false,
            'maintenance_mode_admin' => false,
            'admin_dev_mode' => false,
            default => $default,
        };
    });

    $cache = $stub(RouteCacheInterface::class);
    $cache->method('load')->willReturn(['exact' => [], 'dynamic' => []]);
    $registry = new ActionRegistry($config, $cache);

    // STUB statt Mock, das löst die 4 verbliebenen PHPUnit Notices!
    $container = $stub(ContainerInterface::class);
    $factory = new UniversalActionFactory($registry, $container);

    $session = new SessionManager(new SystemClock());
    $security = new SecurityHeadersMiddleware($session);

    return new class($config, $factory, $security, $session, $registry, $container) {
        public function __construct(
            public Stub&ConfigInterface $config,
            public UniversalActionFactory $factory,
            public SecurityHeadersMiddleware $security,
            public SessionManager $session,
            public ActionRegistry $registry,
            public Stub $container,
        ) {
        }
    };
}

\it('returns 503 JSON response when API is accessed during maintenance mode', function (): void {
    $app = setupFrontendControllerTest($this, [
        'maintenance_mode_admin' => true,
    ]);

    $stub = Closure::bind(fn (string $c): Stub => $this->createStub($c), $this, self::class);
    $cache = $stub(RouteCacheInterface::class);
    $cache->method('load')->willReturn([
        'exact' => ['GET' => ['/api/delete_user' => ['class' => 'App\\Application\\Actions\\Api\\Admin\\DeleteUserAction', 'auth' => true]]],
        'dynamic' => [],
    ]);

    $registry = new ActionRegistry($app->config, $cache);
    $factory = new UniversalActionFactory($registry, $app->container);

    $controller = new FrontendController($app->config, $factory, $app->security, $app->session);
    $response = $controller->handleRequest(new ServerRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/delete_user']));

    \expect($response)->toBeInstanceOf(JsonResponse::class)
        ->and($response->statusCode)->toBe(503)
        ->and($response->data['error'])->toBe('System wird gewartet.');
})->covers(FrontendController::class);

\it('bypasses maintenance mode for allowed routes like login', function (): void {
    $app = setupFrontendControllerTest($this, [
        'maintenance_mode' => true,
    ]);

    $app->container->method('get')->willReturn(new class implements ActionInterface {
        public function execute(ServerRequest $request): mixed
        {
            return new HtmlResponse('Login Page', 200);
        }
    });

    $stub = Closure::bind(fn (string $c): Stub => $this->createStub($c), $this, self::class);
    $cache = $stub(RouteCacheInterface::class);
    $cache->method('load')->willReturn([
        'exact' => ['POST' => ['/api/admin_login' => ['class' => 'MockedLoginAction', 'auth' => false]]],
        'dynamic' => [],
    ]);

    $registry = new ActionRegistry($app->config, $cache);
    $factory = new UniversalActionFactory($registry, $app->container);

    $controller = new FrontendController($app->config, $factory, $app->security, $app->session);
    $response = $controller->handleRequest(new ServerRequest(server: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/admin_login']));

    \expect($response)->toBeInstanceOf(HtmlResponse::class)
        ->and($response->html)->toBe('Login Page');
})->covers(FrontendController::class);

\it('executes pipeline and returns 404 if action not found', function (): void {
    $app = setupFrontendControllerTest($this);

    $controller = new FrontendController($app->config, $app->factory, $app->security, $app->session);
    $response = $controller->handleRequest(new ServerRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/unknown-page']));

    \expect($response)->toBeInstanceOf(HtmlResponse::class)
        ->and($response->statusCode)->toBe(404)
        ->and($response->html)->toContain('404');
})->covers(FrontendController::class);
