<?php
// admin/archive_generator.php
// Führt die Archivgenerierung aus

require_once __DIR__ . '/../src/config/app_config.php';
require_once INCLUDES_DIR . '/functions.php'; // Enthält checkAdminLogin, generateImageLinks

// Login-Prüfung
checkAdminLogin();

// Konfiguration für den Header
$pageTitle = 'Twokinds - Adminbereich - Archivgenerator';
$mainContentHeader = 'Archivgenerator';
$is_admin_area = true;

// Variable aus dem URL-Parameter auslesen
$archiveNumber = isset($_GET['archiveNumber']) ? (int)$_GET['archiveNumber'] : 0;

// Header einbinden
loadTemplate(LAYOUT_TEMPLATES_DIR . '/header.php', [
    'pageTitle' => $pageTitle,
    'mainContentHeader' => $mainContentHeader,
    'is_admin_area' => $is_admin_area
]);

if ($archiveNumber > 0) {
    // Daten für den Generator laden
    // NEU: data_loader.php und generator_logic.php
    require_once ADMIN_ARCHIVE_GENERATOR_DIR . '/data_loader.php'; // Lädt $startupPictures, $headings
    require_once ADMIN_ARCHIVE_GENERATOR_DIR . '/generator_logic.php'; // Enthält die Logik, z.B. generateArchiveFile

    // Lade die spezifischen Archivvariablen für die gegebene Nummer
    $archiveData = loadArchiveVariables($archiveNumber);

    if ($archiveData) {
        $startupPictures = $archiveData['startupPictures'];
        $headings = $archiveData['headings'];

        // Generiere die Archivdatei
        generateArchiveFile(
            $archiveNumber,
            $startupPictures,
            $headings,
            ARCHIVE_INPUT_DIR_ADMIN, // Dieses Verzeichnis scheint für alte Logik zu sein. Prüfe ob benötigt.
            ARCHIVE_OUTPUT_FILE_ADMIN,
            THUMBNAILS_DIR_ADMIN, // Dieses Verzeichnis ist der Pfad zu den Thumbnails für den Admin, nicht für die generierten Links
            THUMBNAILS_DIR_ARCHIV_RELATIVE,
            COMIC_PHP_FILE_DIR_ARCHIV_RELATIVE
        );

        // Navigation für den Generator
        $archiveNumberSubOne = $archiveNumber - 1;
        $archiveNumberAddOne = $archiveNumber + 1;

        // Output-Template laden
        loadTemplate(ADMIN_TEMPLATES_DIR . '/archive_generator/output.php', [
            'archiveNumber' => $archiveNumber,
            'archiveNumberSubOne' => $archiveNumberSubOne,
            'archiveNumberAddOne' => $archiveNumberAddOne
        ]);

    } else {
        echo '<p style="color: red;">Fehler: Archivdaten für Nummer ' . htmlspecialchars($archiveNumber) . ' konnten nicht geladen werden.</p>';
    }
} else {
    echo '<p style="color: red;">Keine Archivnummer zum Generieren angegeben. Bitte kehren Sie zur <a href="archive_generator_start.php">Startseite des Archivgenerators</a> zurück.</p>';
}

// Existierende Generatoren anzeigen (wie zuvor, aber Pfade angepasst)
echo '</br></br><h2>Existierende Generatoren:</h2>';
echo '</br><p>Ordnerpfad: ' . htmlspecialchars(str_replace(APP_ROOT, 'tk', ARCHIVE_VARS_DIR)) . ' </p></br>';

$archiveVarsFiles = scandir(ARCHIVE_VARS_DIR);
foreach ($archiveVarsFiles as $file) {
    if (is_file(ARCHIVE_VARS_DIR . '/' . $file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        echo htmlspecialchars($file) . '<br>';
    }
}

// Footer einbinden
loadTemplate(LAYOUT_TEMPLATES_DIR . '/footer.php', [
    'is_admin_area' => $is_admin_area
]);
?>