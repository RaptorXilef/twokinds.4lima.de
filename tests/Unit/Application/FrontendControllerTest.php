<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\FrontendController;
use App\Application\Http\ServerRequest;
use App\Application\Middleware\SecurityHeadersMiddleware;
use App\Application\Response\HtmlResponse;
use App\Application\Routing\ActionRegistry;
use App\Application\Routing\UniversalActionFactory;
use App\Application\Session\SessionManager;
use App\Contracts\Config\ConfigInterface;
use App\Infrastructure\Utils\SystemClock;
use Closure;
use PHPUnit\Framework\MockObject\Stub;

\uses()->group('application', 'controller');

function setupFrontendControllerTest(mixed $test, array $configMap = []): object
{
    $stub = Closure::bind(fn (string $c): Stub => $test->createStub($c), $test, $test::class);

    if (\session_status() === \PHP_SESSION_NONE) {
        \session_start();
    }
    $_SESSION = [];

    $config = $stub(ConfigInterface::class);
    $config->method('getBaseUrl')->willReturn('https://tk.local');
    $config->method('get')->willReturnMap(\array_merge([
        ['root_path', null, \sys_get_temp_dir()],
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

    // Simulate API request match
    $app->registry->method('match')->willReturn(['class' => 'App\\Application\\Actions\\Api\\Admin\\DeleteUserAction', 'params' => [], 'requiresAuth' => true]);

    $controller = new FrontendController($app->config, $app->factory, $app->security, $app->session);

    \ob_start();
    $controller->handleRequest(new ServerRequest(server: ['REQUEST_URI' => '/api/delete_user']));
    $output = \ob_get_clean();

    \expect(\http_response_code())->toBe(503)
        ->and($output)->toContain('{"success":false,"error":"System wird gewartet."}');
})->covers(FrontendController::class);

\it('bypasses maintenance mode for allowed routes like login', function (): void {
    $app = setupFrontendControllerTest($this, [
        ['maintenance_mode', false, true],
    ]);

    $app->registry->method('match')->willReturn(['class' => \App\Application\Actions\Api\Admin\LoginAction::class, 'params' => [], 'requiresAuth' => false]);

    // We expect the factory to create the action, proving maintenance mode was bypassed
    $app->factory->expects($this->once())->method('create')->willReturn(new class implements \App\Application\Contracts\ActionInterface {
        public function execute(ServerRequest $request): mixed
        {
            return new HtmlResponse('Login Page', 200);
        }
    });

    $controller = new FrontendController($app->config, $app->factory, $app->security, $app->session);

    \ob_start();
    $controller->handleRequest(new ServerRequest(server: ['REQUEST_URI' => '/api/admin_login']));
    $output = \ob_get_clean();

    \expect($output)->toBe('Login Page');
})->covers(FrontendController::class);

\it('executes pipeline and returns 404 if action not found', function (): void {
    $app = setupFrontendControllerTest($this);

    $app->registry->method('match')->willReturn(null); // No match

    $controller = new FrontendController($app->config, $app->factory, $app->security, $app->session);

    \ob_start();
    $controller->handleRequest(new ServerRequest(server: ['REQUEST_URI' => '/unknown-page']));
    $output = \ob_get_clean();

    \expect(\http_response_code())->toBe(404)
        ->and($output)->toBe('404 Not Found');
})->covers(FrontendController::class);
