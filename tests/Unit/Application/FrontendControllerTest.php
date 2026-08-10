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
    $config->method('get')->willReturnMap(\array_merge([
        ['root_path', null, \realpath(__DIR__ . '/../../../')],
        ['maintenance_mode', false, false],
        ['maintenance_mode_admin', false, false],
    ], $configMap));

    $registry = $stub(ActionRegistry::class);
    $factory = $test->createMock(UniversalActionFactory::class);
    $factory->method('getRegistry')->willReturn($registry);

    $session = new SessionManager(new SystemClock());
    $security = new SecurityHeadersMiddleware($session);

    return new class($config, $factory, $security, $session, $registry) {
        public function __construct(
            public Stub&ConfigInterface $config,
            public mixed $factory,
            public SecurityHeadersMiddleware $security,
            public SessionManager $session,
            public Stub&ActionRegistry $registry,
        ) {
        }
    };
}

\it('returns 503 JSON response when API is accessed during maintenance mode', function (): void {
    $app = setupFrontendControllerTest($this, [
        ['maintenance_mode_admin', false, true], // Block admin API
    ]);

    $app->registry->method('match')->willReturn(['class' => 'App\\Application\\Actions\\Api\\Admin\\DeleteUserAction', 'params' => [], 'requiresAuth' => true]);

    $controller = new FrontendController($app->config, $app->factory, $app->security, $app->session);
    $response = $controller->handleRequest(new ServerRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/delete_user']));

    \expect($response)->toBeInstanceOf(JsonResponse::class)
        ->and($response->statusCode)->toBe(503)
        ->and($response->data['error'])->toBe('System wird gewartet.');
})->covers(FrontendController::class);

\it('bypasses maintenance mode for allowed routes like login', function (): void {
    $app = setupFrontendControllerTest($this, [
        ['maintenance_mode', false, true],
    ]);

    $app->registry->method('match')->willReturn(['class' => \App\Application\Actions\Api\Admin\LoginAction::class, 'params' => [], 'requiresAuth' => false]);

    $app->factory->expects($this->once())->method('create')->willReturn(new class implements ActionInterface {
        public function execute(ServerRequest $request): mixed
        {
            return new HtmlResponse('Login Page', 200);
        }
    });

    $controller = new FrontendController($app->config, $app->factory, $app->security, $app->session);
    $response = $controller->handleRequest(new ServerRequest(server: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/admin_login']));

    \expect($response)->toBeInstanceOf(HtmlResponse::class)
        ->and($response->html)->toBe('Login Page');
})->covers(FrontendController::class);

\it('executes pipeline and returns 404 if action not found', function (): void {
    $app = setupFrontendControllerTest($this);

    $app->registry->method('match')->willReturn(null); // No match

    $controller = new FrontendController($app->config, $app->factory, $app->security, $app->session);
    $response = $controller->handleRequest(new ServerRequest(server: ['REQUEST_URI' => '/unknown-page']));

    \expect($response)->toBeInstanceOf(HtmlResponse::class)
        ->and($response->statusCode)->toBe(404)
        ->and($response->html)->toBe('404 Not Found');
})->covers(FrontendController::class);
