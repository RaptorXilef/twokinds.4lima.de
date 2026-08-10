<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Actions\Api\System;

use App\Application\Actions\Api\System\CronBackupAction;
use App\Application\Http\ServerRequest;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\System\BackupServiceInterface;

\uses()->group('application', 'actions', 'api');

\it('CronBackupAction blocks if token is missing or invalid', function (): void {
    $config = $this->createMock(ConfigInterface::class); // Changed from createStub to createMock
    $config->method('get')->with('cron_secret')->willReturn('secret123');

    $backup = $this->createMock(BackupServiceInterface::class);
    $backup->expects($this->never())->method('createBackup');

    $action = new CronBackupAction($backup, $config);

    $res1 = $action->execute(new ServerRequest());
    \expect($res1->statusCode)->toBe(403)->and($res1->data['error'])->toContain('Token ungültig');

    $res2 = $action->execute(new ServerRequest(get: ['token' => 'wrong']));
    \expect($res2->statusCode)->toBe(403)->and($res2->data['error'])->toContain('Token ungültig');
})->covers(CronBackupAction::class);

\it('CronBackupAction executes backup if token is valid', function (): void {
    $config = $this->createMock(ConfigInterface::class); // Changed from createStub to createMock
    $config->method('get')->with('cron_secret')->willReturn('secret123');

    $backup = $this->createMock(BackupServiceInterface::class);
    $backup->expects($this->once())->method('createBackup')->willReturn('backup.zip');

    $action = new CronBackupAction($backup, $config);
    $res = $action->execute(new ServerRequest(get: ['token' => 'secret123']));

    \expect($res->statusCode)->toBe(200)
        ->and($res->data['message'])->toContain('backup.zip');
})->covers(CronBackupAction::class);
