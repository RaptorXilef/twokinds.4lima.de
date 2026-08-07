<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test Case Configuration
|--------------------------------------------------------------------------
| Architektur-spezifische Gruppierungen für gezielte Test-Ausführungen.
*/

\uses()
    ->group('unit')
    ->in('Unit');

\uses()
    ->group('feature')
    ->in('Feature');

// DDD Layer Groups
\uses()->group('core')->in('Unit/Core');
\uses()->group('application')->in('Unit/Application');
\uses()->group('infrastructure')->in('Unit/Infrastructure');
