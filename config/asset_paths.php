<?php
// config/asset_paths.php
// Diese Datei gibt ein Array mit den Pfaden zu CSS- und JS-Dateien zurück.
// Sie wird als PHP-Datei geladen, um dynamische Werte wie $baseUrl und date('Ymd') zu verarbeiten.

// Stellen Sie sicher, dass $baseUrl hier verfügbar ist.
// Diese Variable sollte in einer übergeordneten Datei (z.B. header.php) gesetzt werden.
if (!isset($baseUrl)) {
    // Fallback oder Fehlerbehandlung, falls $baseUrl nicht definiert ist.
    error_log("FEHLER: \$baseUrl ist in asset_paths.php nicht definiert.");
    $baseUrl = '/'; // Setze einen Fallback auf den Server-Root
}

return [
    "css" => [
        "main" => [
            "original" => "https://cdn.twokinds.keenspot.com/css/main.css",
            "local" => $baseUrl . "src/layout/css/main.css?c=" . date('Ymd')
        ],
        "main_dark" => [
            "original" => "https://cdn.twokinds.keenspot.com/css/main_dark.css",
            "local" => $baseUrl . "src/layout/css/main_dark.css?c=" . date('Ymd')
        ]
    ],
    "js" => [
        "jquery" => [
            "original" => "https://code.jquery.com/jquery-3.3.1.min.js",
            "local" => $baseUrl . "src/layout/js/jquery-3.3.1.min.js" // Kein Cache-Buster für jQuery
        ],
        "common" => [
            "original" => "https://cdn.twokinds.keenspot.com/js/common.js?c=20201116", // Original mit festem Buster
            "local" => $baseUrl . "src/layout/js/common.js?c=" . date('Ymd')
        ],
        "archive" => [
            "original" => "https://cdn.twokinds.keenspot.com/js/archive.js?c=20201116", // Original mit festem Buster
            "local" => $baseUrl . "src/layout/js/archive.js?c=" . date('Ymd')
        ],
        "comic" => [
            "original" => "https://cdn.twokinds.keenspot.com/js/comic.js?c=20201116", // Original mit festem Buster
            "local" => $baseUrl . "src/layout/js/comic.js?c=" . date('Ymd')
        ]
    ]
];
