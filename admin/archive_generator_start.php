<?php
// admin/archive_generator_start.php
// Startseite des Archivgenerators (Formular zur Eingabe der Nummer)

require_once __DIR__ . '/../src/config/app_config.php';
require_once INCLUDES_DIR . '/functions.php';

// Login-Prüfung
checkAdminLogin();

// Konfiguration für den Header
$pageTitle = 'Twokinds - Adminbereich - Archivgenerator Start';
$mainContentHeader = 'Archivgenerator starten';
$extraHeadContent = '<style>.error { color: red; }</style>';
$is_admin_area = true;

$errorText = '';
$archiveNumberIdInput = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['archiveNumber']) && !empty($_POST['archiveNumber'])) {
        $archiveNumberIdInput = (int)$_POST['archiveNumber'];

        // Redirect zur archive_generator.php mit der Nummer im URL
        $redirectUrl = 'archive_generator.php?archiveNumber=' . urlencode($archiveNumberIdInput);
        header('Location: ' . $redirectUrl);
        exit;
    } else {
        $errorText = 'Bitte geben Sie eine Nummer ein.';
    }
}

// Fallback: Wenn die Nummer aus der Session kam (vom alten System)
if (isset($_SESSION['archiveNumberIdInput'])) {
    $archiveNumberIdInput = $_SESSION['archiveNumberIdInput'];
    unset($_SESSION['archiveNumberIdInput']); // Nummer aus der Session löschen
}

// Header einbinden
loadTemplate(LAYOUT_TEMPLATES_DIR . '/header.php', [
    'pageTitle' => $pageTitle,
    'mainContentHeader' => $mainContentHeader,
    'extraHeadContent' => $extraHeadContent,
    'is_admin_area' => $is_admin_area
]);

// Formular-Template laden
loadTemplate(ADMIN_TEMPLATES_DIR . '/archive_generator/form.php', [
    'errorText' => $errorText,
    'archiveNumberIdInput' => $archiveNumberIdInput
]);

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