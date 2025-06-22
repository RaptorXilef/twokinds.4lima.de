<?php
// admin/index.php
require_once __DIR__ . '/../src/config/app_config.php';
require_once INCLUDES_DIR . '/functions.php';

// Login-Prüfung
checkAdminLogin(); // Leitet bei fehlendem Login um

// Daten für das Layout-Template
$pageTitle = 'Twokinds - Adminbereich - Startseite';
$mainContentHeader = 'Willkommen im Adminbereich';
$is_admin_area = true; // Wichtig für die Menüauswahl im Header

// Header einbinden
loadTemplate(LAYOUT_TEMPLATES_DIR . '/header.php', [
    'pageTitle' => $pageTitle,
    'mainContentHeader' => $mainContentHeader,
    'is_admin_area' => $is_admin_area
]);
?>

<div class="linksbuendig">
    <p>Willkommen, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
    <p>Bitte wählen Sie eine Aktion im Menü auf der linken Seite aus.</p>
    <br><br><br>
</div>

<?php
// Anleitung einbinden
loadTemplate(ADMIN_TEMPLATES_DIR . '/instructions.php');

// Footer einbinden
loadTemplate(LAYOUT_TEMPLATES_DIR . '/footer.php', [
    'is_admin_area' => $is_admin_area
]);
?>