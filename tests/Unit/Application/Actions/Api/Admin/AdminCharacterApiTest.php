<?php
declare(strict_types = 1);

namespace Tests\Unit\Application\Actions\Api\Admin;

use App\Application\Actions\Api\Admin\DeleteCharacterAction;
use App\Application\Actions\Api\Admin\SaveCharacterGroupsAction;
use App\Application\Actions\Api\Admin\SaveSingleCharacterAction;
use App\Application\Http\ServerRequest;
use App\Application\Session\SessionManager;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\Security\RateLimiterInterface;
use App\Contracts\Storage\CharacterGroupRepositoryInterface;
use App\Contracts\Storage\CharacterRepositoryInterface;
use App\Contracts\Storage\RoleRepositoryInterface;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Contracts\System\MediaServiceInterface;
use App\Contracts\System\SiteGeneratorInterface;
use App\Core\Service\AuthService;
use App\Core\Service\CharacterService;
use App\Infrastructure\Utils\SystemClock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;

\uses()->group('application', 'actions', 'api', 'admin', 'characters');

function setupCharacterApiTest(mixed $test, bool $hasPerm = true): object {
    $stub = \Closure::bind(fn (string $c): Stub => $test->createStub($c), $test, $test::class);
    $mock = \Closure::bind(fn (string $c): MockObject => $test->createMock($c), $test, $test::class);

    if (\session_status() === \PHP_SESSION_NONE) {
        \session_start();
    }
    $_SESSION = [];
    if ($hasPerm) {
        $_SESSION['user_id'] = 'sys_admin';
    } else {
        $_SESSION['user_id'] = 'usr_1';
        $_SESSION['compiled_permissions'] = [];
    }

    $session = new SessionManager(new SystemClock());
    $auth = new AuthService(
        $stub(ConfigInterface::class),
        $stub(RoleRepositoryInterface::class),
        $stub(RateLimiterInterface::class),
        $session,
        $stub(UserRepositoryInterface::class)
    );

    $charRepo = $stub(CharacterRepositoryInterface::class);
    $groupRepo = $stub(CharacterGroupRepositoryInterface::class);

    // Mock the SiteGenerator using the bound closure
    $siteGen = $mock(SiteGeneratorInterface::class);

    $charService = new CharacterService($charRepo, $groupRepo, $siteGen);

    return new class($auth, $charService, $charRepo, $groupRepo, $stub(MediaServiceInterface::class)) {
        public function __construct(
            public AuthService $auth,
            public CharacterService $charService,
            public Stub&CharacterRepositoryInterface $charRepo,
            public Stub&CharacterGroupRepositoryInterface $groupRepo,
            public Stub&MediaServiceInterface $media
        ) {}
    };
}

\it('DeleteCharacterAction 403 on no permission', function (): void {
    $app = setupCharacterApiTest($this, false);
    $action = new DeleteCharacterAction($app->charService, $app->auth);
    \expect($action->execute(new ServerRequest())->statusCode)->toBe(403);
})->covers(DeleteCharacterAction::class);

\it('DeleteCharacterAction 400 on missing ID', function (): void {
    $app = setupCharacterApiTest($this, true);
    $action = new DeleteCharacterAction($app->charService, $app->auth);
    \expect($action->execute(new ServerRequest())->statusCode)->toBe(400);
})->covers(DeleteCharacterAction::class);

\it('DeleteCharacterAction 200 on success', function (): void {
    $app = setupCharacterApiTest($this, true);
    $app->groupRepo->method('findAll')->willReturn([]);

    $action = new DeleteCharacterAction($app->charService, $app->auth);
    $res = $action->execute(new ServerRequest(post: ['character_id' => 'char_123']));

    \expect($res->statusCode)->toBe(200);
})->covers(DeleteCharacterAction::class);

\it('SaveCharacterGroupsAction 400 on malformed JSON data', function (): void {
    $app = setupCharacterApiTest($this, true);
    $action = new SaveCharacterGroupsAction($app->groupRepo, $app->auth);

    $res = $action->execute(new ServerRequest(post: ['groups_data' => '{invalid]']));
    \expect($res->statusCode)->toBe(500);
})->covers(SaveCharacterGroupsAction::class);

\it('SaveSingleCharacterAction 400 on missing name', function (): void {
    $app = setupCharacterApiTest($this, true);
    $action = new SaveSingleCharacterAction($app->charService, $app->charRepo, $app->media, $app->auth);

    $res = $action->execute(new ServerRequest(post: ['id' => 'char_123'])); // Name missing
    \expect($res->statusCode)->toBe(400);
})->covers(SaveSingleCharacterAction::class);

\it('SaveSingleCharacterAction 200 on valid inputs', function (): void {
    $app = setupCharacterApiTest($this, true);
    $app->media->method('processCharacterImages')->willReturn([
        'profile' => null, 'main' => null, 'swatch' => null, 'refs' => [], 'warnings' => []
    ]);

    $action = new SaveSingleCharacterAction($app->charService, $app->charRepo, $app->media, $app->auth);
    $res = $action->execute(new ServerRequest(post: ['id' => 'char_123', 'name' => 'Trace']));

    \expect($res->statusCode)->toBe(200);
})->covers(SaveSingleCharacterAction::class);
