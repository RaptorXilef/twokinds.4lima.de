<?php

declare(strict_types=1);

require_once __DIR__ . '/Setup.php';

use App\Scripts\Setup;

// Hier darf Logik stehen, da keine Symbole deklariert werden.
Setup::runConsole($argv ?? []);
