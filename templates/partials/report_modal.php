<?php

/**
 * @file      ROOT/templates/partials/report_modal.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 *
 * @since     4.0.0
 *  - Initiale Erstellung
 *  - CSP-Fix: Alle Inline-Styles entfernt und in CSS ausgelagert.
 *  - Feature: Transkript-Vorschlag zeigt nun HTML-Tags als Text an (statt sie zu entfernen).
 *  - Feature: "Original-Transkript"-Textarea als sichtbare Referenz hinzugefügt.
 *  - Fix: Zeilenumbrüche und Whitespace *innerhalb* von <p>-Tags werden normalisiert (zu einer Zeile).
 *  - Fix: Entfernt *alle* verbleibenden Zeilenumbrüche (zwischen Tags), um Text in eine Zeile zu zwingen.
 *  - Umstellung auf Summernote-Editor für Vorschläge und HTML-Display für Original.
 *  - Fügt data-Attribut für Original-Dateinamen hinzu, um dynamisches Laden von EN-Bildern zu ermöglichen.
 * @since     5.0.0
 *  - refactor(HTML): Layout auf .modal-advanced-layout umgestellt (Sticky Header/Footer), analog zum Admin-Editor.
 *
 * @description Dieses Template enthält das HTML-Struktur für das Fehlermelde-Modal auf den Comic-Seiten.
 * Es wird von src/renderer/renderer_comic_page.php eingebunden.
 * Die Variablen $comicData, $currentComicId, $nonce, $dateiendungPHP und die Funktion get_cached_image_path()
 * werden vom Renderer (renderer_comic_page.php) bereitgestellt.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;
// Sicherstellen, dass die Comic-Daten vorhanden sind.
if (!isset($comicData) || !isset($currentComicId)) {
    if ($debugMode) {
        error_log("FEHLER [report_modal.php]: \$comicData oder \$currentComicId fehlt.");
    }
    return;
}

// Deutsche Bild-URL holen
$imageDeUrl = get_cached_image_path($currentComicId, 'lowres');
if (!$imageDeUrl) {
    $imageDeUrl = get_cached_image_path('placeholder', 'lowres');
}
$fullImageDeUrl = str_starts_with($imageDeUrl, 'http') ? $imageDeUrl : DIRECTORY_PUBLIC_URL . '/' . ltrim($imageDeUrl, '/');
$originalFilename = $urlOriginalbildFilename ?? '';
$imageEnPlaceholder = 'https://placehold.co/600x400/cccccc/333333?text=Original+wird+geladen...';

// Vorbereitung der Daten für JS
// Wir greifen auf die vom Renderer bereitgestellten Variablen zu.
$comicEntry = $comicData[$currentComicId] ?? [];

$debugData = [
    'comic-id'      => $currentComicId,
    'comic-name'    => $comicEntry['name'] ?? 'Nicht im Index',
    'comic-date'    => $currentComicId,
    'url-lowres'    => $fullImageDeUrl,
    'url-hires'     => get_cached_image_path($currentComicId, 'highres') ?: 'Nicht vorhanden',
    // Hier nutzen wir die Variable, die auch für den EN-Button genutzt wird:
    'url-original'  => $urlOriginalbild ?? 'Nicht ermittelbar',
    'characters'    => implode(', ', $comicEntry['characters'] ?? ['Keine']),
];
?>

<div id="report-modal" class="modal report-modal" role="dialog" aria-labelledby="report-modal-title" aria-modal="true"
    data-comic-id="<?php echo htmlspecialchars($currentComicId); ?>"
    data-original-filename="<?php echo htmlspecialchars($originalFilename); ?>"
    <?php foreach ($debugData as $key => $val) : ?>
        data-debug-<?php echo $key; ?>="<?php echo htmlspecialchars((string)$val); ?>"
    <?php endforeach; ?>
>

    <div class="modal-overlay" tabindex="-1" data-action="close-report-modal"></div>

    <div class="modal-content report-modal-content modal-advanced-layout">

        <div class="modal-header-wrapper">
            <h2 id="report-modal-title">Fehler melden (Comic: <?php echo htmlspecialchars($currentComicId); ?>)</h2>
            <button class="modal-close" data-action="close-report-modal" aria-label="Schließen">&times;</button>
        </div>

        <div class="modal-scroll-content">
            <p>Hier kannst du Fehler im Transkript oder im Comicbild melden.</p>

<?php $explicitApiUrl = Url::getBaseUrl() . '/api/submit_report.php';?>

            <form id="report-form" class="admin-form"
                data-api-url="<?php echo htmlspecialchars($explicitApiUrl); ?>"
                method="POST"
                enctype="multipart/form-data">

                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">

                <div class="honeypot-field" aria-hidden="true">
                    <label for="report-honeypot">Bitte nicht ausfüllen</label>
                    <input type="text" id="report-honeypot" name="report_honeypot" tabindex="-1">
                </div>

                <div class="form-group">
                    <label for="report-name">Dein Name/Pseudonym (Optional)</label>
                    <input type="text" id="report-name" name="report_name" autocomplete="name">
                </div>

                <div class="form-group">
                    <label for="report-type">Fehler-Typ (Pflichtfeld)</label>
                    <select id="report-type" name="report_type" required>
                        <option value="" disabled selected>Bitte auswählen...</option>
                        <option value="transcript">Transkript-Fehler</option>
                        <option value="image">Bild-Fehler</option>
                        <option value="other">Sonstiges</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="report-description">Fehlerbeschreibung</label>
                    <textarea id="report-description" name="report_description" rows="4"
                        placeholder="Bitte beschreibe den Fehler kurz."></textarea>
                </div>

                <div id="transcript-suggestion-container" class="form-group">
                    <label for="report-transcript-suggestion">Transkript-Vorschlag (Optional)</label>
                    <p id="report-suggestion-info">Bearbeite das Transkript direkt im Editor.</p>

                    <textarea id="report-transcript-suggestion" name="report_transcript_suggestion" rows="8"></textarea>

                    <div class="original-transcript-box">
                        <label>Original-Transkript (als Referenz)</label>
                        <div id="report-transcript-original-display" class="transcript-display-box"></div>
                    </div>

                    <textarea id="report-transcript-original" name="report_transcript_original" style="display: none;" readonly></textarea>
                </div>

                <div class="report-images-container form-group">
                    <div>
                        <label>Aktuelles Bild (DE)</label>
                        <img src="<?php echo htmlspecialchars($fullImageDeUrl); ?>" alt="Referenzbild Deutsch" loading="lazy">
                    </div>
                    <div>
                        <label>Original (EN)</label>
                        <img id="report-modal-image-en" src="<?php echo htmlspecialchars($imageEnPlaceholder); ?>" alt="Referenzbild Englisch" loading="lazy">
                    </div>
                </div>

                <div class="form-group debug-info-section">
            <label for="report-debug-info">System-Informationen (Telemetrie)</label>
            <textarea id="report-debug-info" name="report_debug_info" rows="8" readonly
                      style="font-size: 0.75rem; font-family: 'Courier New', monospace;"></textarea>
            <p class="input-help">Diese technischen Daten werden automatisch angehängt.</p>
        </div>

                <div id="report-status-message" class="status-message"></div>
            </form>
        </div>

        <div class="modal-footer-actions">
            <div class="modal-buttons">
                <button type="submit" form="report-form" id="report-submit-button" class="button button-green">Meldung absenden</button>
                <button type="button" class="button delete" data-action="close-report-modal">Abbrechen</button>
            </div>
        </div>

    </div>
</div>
