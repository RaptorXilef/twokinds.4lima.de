<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Actions\Api\System;

use App\Application\Actions\Api\System\ProcessMailQueueAction;
use App\Application\Http\ServerRequest;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\Mail\MailServiceInterface;
use App\Contracts\Storage\MagicLinkRepositoryInterface;
use App\Contracts\Storage\UserRepositoryInterface;

\uses()->group('application', 'actions', 'api');

\it('ProcessMailQueueAction triggers all cleanup tasks on valid token', function (): void {
    $config = $this->createStub(ConfigInterface::class);
    $config->method('get')->with('cron_secret')->willReturn('secret123');

    $mail = $this->createMock(MailServiceInterface::class);
    $mail->expects($this->once())->method('processQueue')->willReturn(5);

    $userRepo = $this->createMock(UserRepositoryInterface::class);
    $userRepo->expects($this->once())->method('deleteUnverifiedAccounts')->with(60)->willReturn(2);

    $magicRepo = $this->createMock(MagicLinkRepositoryInterface::class);
    $magicRepo->expects($this->once())->method('deleteExpired')->willReturn(3);

    $action = new ProcessMailQueueAction($mail, $config, $userRepo, $magicRepo);
    $res = $action->execute(new ServerRequest(get: ['token' => 'secret123']));

    \expect($res->statusCode)->toBe(200)
        ->and($res->data['sent_count'])->toBe(5)
        ->and($res->data['deleted_unverified_users'])->toBe(2)
        ->and($res->data['deleted_links'])->toBe(3);
})->covers(ProcessMailQueueAction::class);
