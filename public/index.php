<?php

/**
 * Haupteinstiegspunkt der Anwendung.
 *
 * Path: public/index.php
 *
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */

declare(strict_types=1);

use App\Application\Contracts\ResponseInterface;
use App\Application\FrontendController;
use App\Application\Http\ServerRequest;
use App\Bootstrap\Container;

// Lade das Bootstrap-Fundament (DI-Container, Config, etc.)
$container = require_once __DIR__ . '/../src/Bootstrap/app.php';
\assert($container instanceof Container);

/** @var array<string, mixed> $get */
$get = $_GET;
/** @var array<string, mixed> $post */
$post = $_POST;
/** @var array<string, mixed> $files */
$files = $_FILES;
/** @var array<string, mixed> $server */
$server = $_SERVER;
/** @var array<string, mixed> $cookie */
$cookie = $_COOKIE;

// Erstelle das Request-Objekt aus den globalen PHP-Variablen
$req = new ServerRequest($get, $post, $files, $server, [], $cookie);

// Übergebe die Kontrolle an den FrontendController
$controller = $container->get(FrontendController::class);
\assert($controller instanceof FrontendController);

// Empfange das Response-Objekt sauber und führe erst GANZ am Ende send() (und damit exit;) aus.
$response = $controller->handleRequest($req);

if ($response instanceof ResponseInterface) {
    $response->send();
}
