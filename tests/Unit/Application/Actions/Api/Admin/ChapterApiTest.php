<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Actions\Api\Admin;

use App\Application\Actions\Api\Admin\DeleteChapterAction;
use App\Application\Actions\Api\Admin\SaveChapterAction;
use App\Application\Http\ServerRequest;
use App\Contracts\Storage\ChapterRepositoryInterface;
use App\Core\Service\AuthService;
use Closure;
use PHPUnit\Framework\MockObject\Stub;

\uses()->group('application', 'actions', 'api', 'admin');

function setupChapterApiTest(mixed $test, bool $hasPerm = true): object
{
    $stub = Closure::bind(fn (string $c): Stub => $test->createStub($c), $test, $test::class);
    $auth = $stub(AuthService::class);
    $auth->method('hasPermission')->willReturn($hasPerm);

    return new class($auth, $stub(ChapterRepositoryInterface::class)) {
        public function __construct(
            public Stub&AuthService $auth,
            public Stub&ChapterRepositoryInterface $repo,
        ) {
        }
    };
}

\it('DeleteChapterAction 403 on no permission', function (): void {
    $app = setupChapterApiTest($this, false);
    $action = new DeleteChapterAction($app->repo, $app->auth);
    \expect($action->execute(new ServerRequest())->statusCode)->toBe(403);
})->covers(DeleteChapterAction::class);

\it('DeleteChapterAction 400 on missing id', function (): void {
    $app = setupChapterApiTest($this, true);
    $action = new DeleteChapterAction($app->repo, $app->auth);
    \expect($action->execute(new ServerRequest())->statusCode)->toBe(400);
})->covers(DeleteChapterAction::class);

\it('SaveChapterAction 400 on missing data', function (): void {
    $app = setupChapterApiTest($this, true);
    $action = new SaveChapterAction($app->repo, $app->auth);
    \expect($action->execute(new ServerRequest())->statusCode)->toBe(400);
})->covers(SaveChapterAction::class);

\it('SaveChapterAction 200 on valid data', function (): void {
    $app = setupChapterApiTest($this, true);
    $action = new SaveChapterAction($app->repo, $app->auth);
    $res = $action->execute(new ServerRequest(post: ['chapter_id' => '1', 'title' => 'Prologue']));
    \expect($res->statusCode)->toBe(200);
})->covers(SaveChapterAction::class);
