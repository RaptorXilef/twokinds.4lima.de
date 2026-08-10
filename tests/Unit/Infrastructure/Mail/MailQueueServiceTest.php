<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Mail;

use App\Contracts\Mail\MailServiceInterface;
use App\Contracts\Storage\MailQueueRepositoryInterface;
use App\Core\Entity\MailJob;
use App\Infrastructure\Mail\MailQueueService;
use Closure;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;

\uses()->group('infrastructure', 'mail');

function setupMailQueueTest(mixed $test): object
{
    $mock = Closure::bind(fn (string $c): MockObject => $test->createMock($c), $test, $test::class);
    $stub = Closure::bind(fn (string $c): Stub => $test->createStub($c), $test, $test::class);

    return new class($mock, $stub) {
        public function __construct(
            public mixed $mockMaker,
            public mixed $stubMaker,
        ) {
        }
    };
}

\it('enqueues a normal template with priority 10', function (): void {
    $app = setupMailQueueTest($this);

    $repo = ($app->mockMaker)(MailQueueRepositoryInterface::class);
    $realMail = ($app->stubMaker)(MailServiceInterface::class);

    $repo->expects($this->once())
        ->method('enqueue')
        ->with($this->callback(fn (MailJob $job) => $job->priority === 10 && $job->template === 'normal_mail'));

    $service = new MailQueueService($repo, $realMail);
    $service->sendTemplate('test@test.de', 'Sub', 'normal_mail', []);
})->covers(MailQueueService::class);

\it('enqueues forgot_password with priority 100', function (): void {
    $app = setupMailQueueTest($this);

    $repo = ($app->mockMaker)(MailQueueRepositoryInterface::class);
    $realMail = ($app->stubMaker)(MailServiceInterface::class);

    $repo->expects($this->once())
        ->method('enqueue')
        ->with($this->callback(fn (MailJob $job) => $job->priority === 100 && $job->template === 'forgot_password'));

    $service = new MailQueueService($repo, $realMail);
    $service->sendTemplate('test@test.de', 'Sub', 'forgot_password', []);
})->covers(MailQueueService::class);

\it('enqueues report_resolved with priority 50', function (): void {
    $app = setupMailQueueTest($this);

    $repo = ($app->mockMaker)(MailQueueRepositoryInterface::class);
    $realMail = ($app->stubMaker)(MailServiceInterface::class);

    $repo->expects($this->once())
        ->method('enqueue')
        ->with($this->callback(fn (MailJob $job) => $job->priority === 50 && $job->template === 'report_resolved'));

    $service = new MailQueueService($repo, $realMail);
    $service->sendTemplate('test@test.de', 'Sub', 'report_resolved', []);
})->covers(MailQueueService::class);

\it('processes queue and delegates to real service', function (): void {
    $app = setupMailQueueTest($this);

    $repo = ($app->stubMaker)(MailQueueRepositoryInterface::class);
    $realMail = ($app->mockMaker)(MailServiceInterface::class);

    $repo->method('processBatch')
        ->willReturnCallback(function (int $limit, callable $processor) {
            $processor('test@test.de', 'Sub', 'tpl', ['a' => 1]);

            return 1;
        });

    $realMail->expects($this->once())
        ->method('sendTemplate')
        ->willReturn(true);

    $service = new MailQueueService($repo, $realMail);
    $count = $service->processQueue(5);

    \expect($count)->toBe(1);
})->covers(MailQueueService::class);

\it('processes queue and throws exception if real service returns error string', function (): void {
    $app = setupMailQueueTest($this);

    $repo = ($app->stubMaker)(MailQueueRepositoryInterface::class);
    $realMail = ($app->stubMaker)(MailServiceInterface::class);

    $repo->method('processBatch')
        ->willReturnCallback(function (int $limit, callable $processor) {
            $processor('test@test.de', 'Sub', 'tpl', []);

            return 0;
        });

    $realMail->method('sendTemplate')->willReturn('SMTP Timeout Connection Error');

    $service = new MailQueueService($repo, $realMail);
    $service->processQueue(5);
})->throws(Exception::class, 'SMTP Timeout Connection Error')->covers(MailQueueService::class);
