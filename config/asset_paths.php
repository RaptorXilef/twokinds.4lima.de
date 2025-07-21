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
    ],
    "images" => [
        "characters" => [
            "base" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/" // Platzhalter für lokale Charakterbilder
            ],
            "faces" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/faces/",
                "local" => $baseUrl . "assets/img/faces_local_placeholder/" // Platzhalter für lokale Face-Icons
            ],
            // Spezifische Charakterbilder können hier hinzugefügt werden, falls sie nicht dem Basispfad folgen
            "trace_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Trace2025.png",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/Trace2025.png"
            ],
            "flora_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Flora2025.png",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/Flora2025.png"
            ],
            "keith_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Keith2025.png",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/Keith2025.png"
            ],
            "natani_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Natani2025.png",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/Natani2025.png"
            ],
            "zen_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Zen2025.png",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/Zen2025.png"
            ],
            "sythe_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Sythe2025.png",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/Sythe2025.png"
            ],
            "nibbly_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Nibbly2025.png",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/Nibbly2025.png"
            ],
            "raine_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Raine2025.png",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/Raine2025.png"
            ],
            "laura_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Laura2025.png",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/Laura2025.png"
            ],
            "saria_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Saria.jpg",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/Saria.jpg"
            ],
            "eric_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Eric.jpg",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/Eric.jpg"
            ],
            "kathrin_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Kathrin.jpg",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/Kathrin.jpg"
            ],
            "mike_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Mike.jpg",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/Mike.jpg"
            ],
            "evals_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Evals.jpg",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/Evals.jpg"
            ],
            "maddie_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Maddie.png",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/Maddie.png"
            ],
            "maren_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Maren.jpg",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/Maren.jpg"
            ],
            "karen_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Karen.jpg",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/Karen.jpg"
            ],
            "red_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/RedHairedGuy.jpg",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/RedHairedGuy.jpg"
            ],
            "alaric_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Alaric.jpg",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/Alaric.jpg"
            ],
            "nora_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/LadyNora.jpg",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/LadyNora.jpg"
            ],
            "reni_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Reni2025.png",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/Reni2025.png"
            ],
            "adira_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Adira2025.png",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/Adira2025.png"
            ],
            "maeve_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Maeve2025.png",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/Maeve2025.png"
            ],
            "mask_image" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/mask.png",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/mask.png"
            ],
            "villains_image" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Villians.jpg",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/Villians.jpg"
            ],
            "evil_trace_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/EvilTrace.jpg",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/EvilTrace.jpg"
            ],
            // Spezifische Swatch-Bilder
            "trace_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/TraceSwatch.gif",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/swatches/TraceSwatch.gif"
            ],
            "flora_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/FloraSwatch.gif",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/swatches/FloraSwatch.gif"
            ],
            "keith_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/KeithSwatch.gif",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/swatches/KeithSwatch.gif"
            ],
            "natani_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/NataniSwatch.gif",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/swatches/NataniSwatch.gif"
            ],
            "zen_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/ZenSwatch.gif",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/swatches/ZenSwatch.gif"
            ],
            "sythe_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/SytheSwatch.gif",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/swatches/SytheSwatch.gif"
            ],
            "nibbly_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/MrsNibblySwatch.gif",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/swatches/MrsNibblySwatch.gif"
            ],
            "raine_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/reniref_thumbnail.png", // Raine hat eine spezielle Ref Sheet URL
                "local" => $baseUrl . "assets/img/characters_local_placeholder/reniref_thumbnail.png"
            ],
            "laura_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/LauraSwatch.gif",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/swatches/LauraSwatch.gif"
            ],
            "saria_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/SariaSwatch.gif",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/swatches/SariaSwatch.gif"
            ],
            "eric_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/EricSwatch.gif",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/swatches/EricSwatch.gif"
            ],
            "kathrin_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/KathrinSwatch.gif",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/swatches/KathrinSwatch.gif"
            ],
            "mike_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/MikeSwatch.gif",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/swatches/MikeSwatch.gif"
            ],
            "evals_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/EvalsSwatch.gif",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/swatches/EvalsSwatch.gif"
            ],
            "maddie_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/MaddySwatch.gif",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/swatches/MaddySwatch.gif"
            ],
            "maren_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/MarenSwatch.gif",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/swatches/MarenSwatch.gif"
            ],
            "karen_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/KarenSwatch.gif",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/swatches/KarenSwatch.gif"
            ],
            "red_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/RedHairedGuySwatch.gif",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/swatches/RedHairedGuySwatch.gif"
            ],
            "alaric_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/AlaricSwatch.gif",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/swatches/AlaricSwatch.gif"
            ],
            "nora_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/LadyNoraSwatch.gif",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/swatches/LadyNoraSwatch.gif"
            ],
            "reni_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/reniref_thumbnail.png", // Reni hat eine spezielle Ref Sheet URL
                "local" => $baseUrl . "assets/img/characters_local_placeholder/reniref_thumbnail.png"
            ],
            "adira_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/adiramaeveref_thumbnail.png", // Adira hat eine spezielle Ref Sheet URL
                "local" => $baseUrl . "assets/img/characters_local_placeholder/adiramaeveref_thumbnail.png"
            ],
            "maeve_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/adiramaeveref_thumbnail.png", // Maeve hat eine spezielle Ref Sheet URL
                "local" => $baseUrl . "assets/img/characters_local_placeholder/adiramaeveref_thumbnail.png"
            ],
            "evil_trace_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/TraceSwatch.gif",
                "local" => $baseUrl . "assets/img/characters_local_placeholder/swatches/TraceSwatch.gif"
            ]
        ]
    ]
];
