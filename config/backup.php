<?php

/**
 * Backup Einstellungen
 *
 * Diese Datei definiert die Verbindungseinstellungen für automatische Backups
 * auf einen externen FPT Server/Speicher.
 *
 * Path: config/backup.php
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */

declare(strict_types=1);

return [

    'backup' => [
        'retention_days' => 365, // Tage bis Backup automatisch gelöscht wird
        'zip_password'   => '',
        'ftp'            => [
            'enabled' => false,
            'host'    => '',
            'port'    => 21,
            'user'    => '',
            'pass'    => '',
            'path'    => '/backups/',
            'ssl'     => true,
        ],
    ],

];
