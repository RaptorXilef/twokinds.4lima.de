<?php

/**
 * Haupteinstiegspunkt der Anwendung.
 *
 * Path: public/index.php
 *
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */

declare(strict_types=1);

use App\Application\FrontendController;
use App\Application\Http\ServerRequest;

// Lade das Bootstrap-Fundament (DI-Container, Config, etc.)
$container = require_once __DIR__ . '/../src/Bootstrap/app.php';

// Erstelle das Request-Objekt aus den globalen PHP-Variablen
$req = new ServerRequest($_GET, $_POST, $_FILES, $_SERVER, [], $_COOKIE);

// Übergebe die Kontrolle an den FrontendController
$container->get(FrontendController::class)->handleRequest($req);
