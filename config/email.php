<?php

declare(strict_types=1);

return [
    'mail' => [
        'host' => 'smtp.lima-city.de', // Beispiel
        'port' => 465,
        'encryption' => 'tls', // WICHTIG: 'tls' oder 'starttls'
        'user' => 'no-reply@twokinds.4lima.de',
        'pass' => 'Dein-Passwort',
        'from' => 'no-reply@twokinds.4lima.de',
        'send_board_notification' => false, // Wirft alte Admin-Mails raus
    ],
];
