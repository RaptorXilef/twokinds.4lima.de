<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\System;

use App\Contracts\Config\ConfigInterface;
use App\Contracts\Storage\ChapterRepositoryInterface;
use App\Contracts\Storage\CharacterRepositoryInterface;
use App\Contracts\Storage\ComicRepositoryInterface;
use App\Core\Entity\ComicPage;
use App\Core\ValueObject\ComicId;
use App\Infrastructure\System\StaticSiteGenerator;

\uses()->group('infrastructure', 'system', 'xml');

\it('generates sitemap and rss xml files automatically on destruction', function (): void {
    $comicRepo = $this->createMock(ComicRepositoryInterface::class);
    $comicRepo->method('findAll')->willReturn([
        new ComicPage(new ComicId('20260810'), 'Comicseite', 'Test', '', null, [], '', '', [], 1234567890),
    ]);

    // WICHTIG: findAll MUSS ein Array zurückgeben, ansonsten kracht die foreach-Schleife!
    $charRepo = $this->createStub(CharacterRepositoryInterface::class);
    $charRepo->method('findAll')->willReturn([]);

    $chapRepo = $this->createStub(ChapterRepositoryInterface::class);
    $chapRepo->method('findAll')->willReturn([]);

    $config = $this->createMock(ConfigInterface::class);
    $tempDir = \sys_get_temp_dir() . '/tk_test_' . \uniqid();
    \mkdir($tempDir . '/public', 0o777, true);

    $config->method('get')->willReturnMap([
        ['root_path', null, $tempDir],
        ['site_title', null, 'Test Title'],
        ['site_description', null, 'Test Desc'],
        ['rss_max_items', 25, 25],
    ]);
    $config->method('getBaseUrl')->willReturn('https://tk.local');

    $generator = new StaticSiteGenerator(
        $comicRepo,
        $chapRepo,
        $config,
        $charRepo,
    );

    $generator->generateAll();

    // Trigger destructor explicitly to write files
    unset($generator);

    \expect(\file_exists($tempDir . '/public/sitemap.xml'))->toBeTrue()
        ->and(\file_exists($tempDir . '/public/rss.xml'))->toBeTrue();

    // Cleanup
    \unlink($tempDir . '/public/sitemap.xml');
    \unlink($tempDir . '/public/rss.xml');
    \rmdir($tempDir . '/public');
    \rmdir($tempDir);
})->covers(StaticSiteGenerator::class);
