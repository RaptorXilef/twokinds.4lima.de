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
                "local" => $baseUrl . "assets/img/charaktere/characters/" // Aktualisierter Basispfad für lokale Charakterbilder
            ],
            "faces" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/faces/",
                "local" => $baseUrl . "assets/img/charaktere/faces/" // Aktualisierter Basispfad für lokale Face-Icons
            ],
            // Spezifische Charakterbilder (Porträts)
            "trace_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Trace2025.png",
                "local" => $baseUrl . "assets/img/charaktere/characters/Trace2025.png"
            ],
            "flora_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Flora2025.png",
                "local" => $baseUrl . "assets/img/charaktere/characters/Flora2025.png"
            ],
            "keith_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Keith2025.png",
                "local" => $baseUrl . "assets/img/charaktere/characters/Keith2025.png"
            ],
            "natani_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Natani2025.png",
                "local" => $baseUrl . "assets/img/charaktere/characters/Natani2025.png"
            ],
            "zen_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Zen2025.png",
                "local" => $baseUrl . "assets/img/charaktere/characters/Zen2025.png"
            ],
            "sythe_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Sythe2025.png",
                "local" => $baseUrl . "assets/img/charaktere/characters/Sythe2025.png"
            ],
            "nibbly_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Nibbly2025.png",
                "local" => $baseUrl . "assets/img/charaktere/characters/Nibbly2025.png"
            ],
            "raine_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Raine2025.png",
                "local" => $baseUrl . "assets/img/charaktere/characters/Raine2025.png"
            ],
            "laura_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Laura2025.png",
                "local" => $baseUrl . "assets/img/charaktere/characters/Laura2025.png"
            ],
            "saria_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Saria.jpg",
                "local" => $baseUrl . "assets/img/charaktere/characters/Saria.jpg"
            ],
            "eric_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Eric.jpg",
                "local" => $baseUrl . "assets/img/charaktere/characters/Eric.jpg"
            ],
            "kathrin_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Kathrin.jpg",
                "local" => $baseUrl . "assets/img/charaktere/characters/Kathrin.jpg"
            ],
            "mike_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Mike.jpg",
                "local" => $baseUrl . "assets/img/charaktere/characters/Mike.jpg"
            ],
            "evals_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Evals.jpg",
                "local" => $baseUrl . "assets/img/charaktere/characters/Evals.jpg"
            ],
            "maddie_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Maddie.png",
                "local" => $baseUrl . "assets/img/charaktere/characters/Maddie.png"
            ],
            "maren_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Maren.jpg",
                "local" => $baseUrl . "assets/img/charaktere/characters/Maren.jpg"
            ],
            "karen_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Karen.jpg",
                "local" => $baseUrl . "assets/img/charaktere/characters/Karen.jpg"
            ],
            "red_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/RedHairedGuy.jpg",
                "local" => $baseUrl . "assets/img/charaktere/characters/RedHairedGuy.jpg"
            ],
            "alaric_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Alaric.jpg",
                "local" => $baseUrl . "assets/img/charaktere/characters/Alaric.jpg"
            ],
            "nora_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/LadyNora.jpg",
                "local" => $baseUrl . "assets/img/charaktere/characters/LadyNora.jpg"
            ],
            "reni_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Reni2025.png",
                "local" => $baseUrl . "assets/img/charaktere/characters/Reni2025.png"
            ],
            "adira_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Adira2025.png",
                "local" => $baseUrl . "assets/img/charaktere/characters/Adira2025.png"
            ],
            "maeve_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Maeve2025.png",
                "local" => $baseUrl . "assets/img/charaktere/characters/Maeve2025.png"
            ],
            "mask_image" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/mask.png",
                "local" => $baseUrl . "assets/img/charaktere/characters/mask.png"
            ],
            "villains_image" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/Villians.jpg",
                "local" => $baseUrl . "assets/img/charaktere/characters/Villians.jpg"
            ],
            "evil_trace_portrait" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/EvilTrace.jpg",
                "local" => $baseUrl . "assets/img/charaktere/characters/EvilTrace.jpg"
            ],
            // Spezifische Swatch-Bilder (Farbfelder)
            "trace_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/TraceSwatch.gif",
                "local" => $baseUrl . "assets/img/charaktere/swatches/TraceSwatch.gif"
            ],
            "flora_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/FloraSwatch.gif",
                "local" => $baseUrl . "assets/img/charaktere/swatches/FloraSwatch.gif"
            ],
            "keith_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/KeithSwatch.gif",
                "local" => $baseUrl . "assets/img/charaktere/swatches/KeithSwatch.gif"
            ],
            "natani_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/NataniSwatch.gif",
                "local" => $baseUrl . "assets/img/charaktere/swatches/NataniSwatch.gif"
            ],
            "zen_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/ZenSwatch.gif",
                "local" => $baseUrl . "assets/img/charaktere/swatches/ZenSwatch.gif"
            ],
            "sythe_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/SytheSwatch.gif",
                "local" => $baseUrl . "assets/img/charaktere/swatches/SytheSwatch.gif"
            ],
            "nibbly_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/MrsNibblySwatch.gif",
                "local" => $baseUrl . "assets/img/charaktere/swatches/MrsNibblySwatch.gif"
            ],
            "raine_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/RaineSwatch.gif",
                "local" => $baseUrl . "assets/img/charaktere/swatches/RaineSwatch.gif"
            ],
            "laura_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/LauraSwatch.gif",
                "local" => $baseUrl . "assets/img/charaktere/swatches/LauraSwatch.gif"
            ],
            "saria_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/SariaSwatch.gif",
                "local" => $baseUrl . "assets/img/charaktere/swatches/SariaSwatch.gif"
            ],
            "eric_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/EricSwatch.gif",
                "local" => $baseUrl . "assets/img/charaktere/swatches/EricSwatch.gif"
            ],
            "kathrin_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/KathrinSwatch.gif",
                "local" => $baseUrl . "assets/img/charaktere/swatches/KathrinSwatch.gif"
            ],
            "mike_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/MikeSwatch.gif",
                "local" => $baseUrl . "assets/img/charaktere/swatches/MikeSwatch.gif"
            ],
            "evals_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/EvalsSwatch.gif",
                "local" => $baseUrl . "assets/img/charaktere/swatches/EvalsSwatch.gif"
            ],
            "maddie_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/MaddySwatch.gif",
                "local" => $baseUrl . "assets/img/charaktere/swatches/MaddySwatch.gif"
            ],
            "maren_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/MarenSwatch.gif",
                "local" => $baseUrl . "assets/img/charaktere/swatches/MarenSwatch.gif"
            ],
            "karen_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/KarenSwatch.gif",
                "local" => $baseUrl . "assets/img/charaktere/swatches/KarenSwatch.gif"
            ],
            "red_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/RedHairedGuySwatch.gif",
                "local" => $baseUrl . "assets/img/charaktere/swatches/RedHairedGuySwatch.gif"
            ],
            "alaric_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/AlaricSwatch.gif",
                "local" => $baseUrl . "assets/img/charaktere/swatches/AlaricSwatch.gif"
            ],
            "nora_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/LadyNoraSwatch.gif",
                "local" => $baseUrl . "assets/img/charaktere/swatches/LadyNoraSwatch.gif"
            ],
            "reni_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/reniref_thumbnail.png", // Reni hat eine spezielle Ref Sheet URL als Swatch
                "local" => $baseUrl . "assets/img/charaktere/swatches/reniref_thumbnail.png"
            ],
            "adira_maeve_swatch" => [ // Adira und Maeve teilen sich diesen Swatch
                "original" => "https://cdn.twokinds.keenspot.com/img/adiramaeveref_thumbnail.png",
                "local" => $baseUrl . "assets/img/charaktere/swatches/adiramaeveref_thumbnail.png"
            ],
            "evil_trace_swatch" => [
                "original" => "https://cdn.twokinds.keenspot.com/img/characters/swatches/TraceSwatch.gif",
                "local" => $baseUrl . "assets/img/charaktere/swatches/TraceSwatch.gif"
            ],
            // Neue Einträge für Ref-Sheets
            // Wenn kein Original-Ref-Sheet existiert, ist 'original' null oder ein generischer Platzhalter
            "trace_ref_sheet" => [
                "original" => null, // Kein spezifisches Original-Ref-Sheet bekannt
                "local" => $baseUrl . "assets/img/charaktere/ref_sheets/TraceRefSheet.png"
            ],
            "flora_ref_sheet" => [
                "original" => null,
                "local" => $baseUrl . "assets/img/charaktere/ref_sheets/FloraRefSheet.png"
            ],
            "keith_ref_sheet" => [
                "original" => null,
                "local" => $baseUrl . "assets/img/charaktere/ref_sheets/KeithRefSheet.png"
            ],
            "natani_ref_sheet" => [
                "original" => null,
                "local" => $baseUrl . "assets/img/charaktere/ref_sheets/NataniRefSheet.png"
            ],
            "zen_ref_sheet" => [
                "original" => null,
                "local" => $baseUrl . "assets/img/charaktere/ref_sheets/ZenRefSheet.png"
            ],
            "sythe_ref_sheet" => [
                "original" => null,
                "local" => $baseUrl . "assets/img/charaktere/ref_sheets/SytheRefSheet.png"
            ],
            "nibbly_ref_sheet" => [
                "original" => null,
                "local" => $baseUrl . "assets/img/charaktere/ref_sheets/NibblyRefSheet.png"
            ],
            "raine_ref_sheet" => [
                "original" => "https://www.deviantart.com/twokinds/art/Raine-Reference-Sheet-XXXXXXX", // Platzhalter für Raines Ref Sheet
                "local" => $baseUrl . "assets/img/charaktere/ref_sheets/RaineRefSheet.png"
            ],
            "laura_ref_sheet" => [
                "original" => null,
                "local" => $baseUrl . "assets/img/charaktere/ref_sheets/LauraRefSheet.png"
            ],
            "saria_ref_sheet" => [
                "original" => null,
                "local" => $baseUrl . "assets/img/charaktere/ref_sheets/SariaRefSheet.png"
            ],
            "eric_ref_sheet" => [
                "original" => null,
                "local" => $baseUrl . "assets/img/charaktere/ref_sheets/EricRefSheet.png"
            ],
            "kathrin_ref_sheet" => [
                "original" => null,
                "local" => $baseUrl . "assets/img/charaktere/ref_sheets/KathrinRefSheet.png"
            ],
            "mike_ref_sheet" => [
                "original" => null,
                "local" => $baseUrl . "assets/img/charaktere/ref_sheets/MikeRefSheet.png"
            ],
            "evals_ref_sheet" => [
                "original" => null,
                "local" => $baseUrl . "assets/img/charaktere/ref_sheets/EvalsRefSheet.png"
            ],
            "maddie_ref_sheet" => [
                "original" => null,
                "local" => $baseUrl . "assets/img/charaktere/ref_sheets/MaddieRefSheet.png"
            ],
            "maren_ref_sheet" => [
                "original" => null,
                "local" => $baseUrl . "assets/img/charaktere/ref_sheets/MarenRefSheet.png"
            ],
            "karen_ref_sheet" => [
                "original" => null,
                "local" => $baseUrl . "assets/img/charaktere/ref_sheets/KarenRefSheet.png"
            ],
            "red_ref_sheet" => [
                "original" => null,
                "local" => $baseUrl . "assets/img/charaktere/ref_sheets/RedRefSheet.png"
            ],
            "alaric_ref_sheet" => [
                "original" => null,
                "local" => $baseUrl . "assets/img/charaktere/ref_sheets/AlaricRefSheet.png"
            ],
            "nora_ref_sheet" => [
                "original" => null,
                "local" => $baseUrl . "assets/img/charaktere/ref_sheets/NoraRefSheet.png"
            ],
            "reni_ref_sheet" => [
                "original" => "https://www.deviantart.com/twokinds/art/Reni-Ref-Sheet-877690024",
                "local" => $baseUrl . "assets/img/charaktere/ref_sheets/ReniRefSheet.png"
            ],
            "adira_ref_sheet" => [
                "original" => "https://www.deviantart.com/twokinds/art/Adira-Reference-Sheet-803158843",
                "local" => $baseUrl . "assets/img/charaktere/ref_sheets/AdiraRefSheet.png"
            ],
            "maeve_ref_sheet" => [ // Maeve teilt das Ref Sheet mit Adira
                "original" => "https://www.deviantart.com/twokinds/art/Adira-Reference-Sheet-803158843",
                "local" => $baseUrl . "assets/img/charaktere/ref_sheets/MaeveRefSheet.png"
            ],
            "evil_trace_ref_sheet" => [
                "original" => null,
                "local" => $baseUrl . "assets/img/charaktere/ref_sheets/EvilTraceRefSheet.png"
            ]
        ]
    ]
];
