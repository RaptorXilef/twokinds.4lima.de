<?php
/**
 * @file      ROOT/templates/partials/report_modal.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   2.1.0
 * @since     1.0.0 Initiale Erstellung
 * @since     1.1.0 CSP-Fix: Alle Inline-Styles entfernt und in CSS ausgelagert.
 * @since     1.1.0 Feature: Transkript-Vorschlag zeigt nun HTML-Tags als Text an (statt sie zu entfernen).
 * @since     1.1.0 Feature: "Original-Transkript"-Textarea als sichtbare Referenz hinzugefügt.
 * @since     1.2.0 Fix: Zeilenumbrüche und Whitespace *innerhalb* von <p>-Tags werden normalisiert (zu einer Zeile).
 * @since     1.2.1 Fix: Entfernt *alle* verbleibenden Zeilenumbrüche (zwischen Tags), um Text in eine Zeile zu zwingen.
 * @since     2.0.0 Umstellung auf Summernote-Editor für Vorschläge und HTML-Display für Original.
 * @since     2.1.0 Fügt data-Attribut für Original-Dateinamen hinzu, um dynamisches Laden von EN-Bildern zu ermöglichen.
 *
 * @description Dieses Template enthält das HTML-Struktur für das Fehlermelde-Modal auf den Comic-Seiten.
 * Es wird von src/renderer/renderer_comic_page.php eingebunden.
 * Die Variablen $comicData, $currentComicId, $nonce, $dateiendungPHP und die Funktion get_cached_image_path()
 * werden vom Renderer (renderer_comic_page.php) bereitgestellt.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// Sicherstellen, dass die Comic-Daten vorhanden sind, bevor wir versuchen, darauf zuzugreifen.
if (!isset($comicData) || !isset($currentComicId)) {
    if ($debugMode) {
        error_log("FEHLER [report_modal.php]: \$comicData oder \$currentComicId wurde nicht vom Renderer bereitgestellt.");
    }
    // Verhindere das Rendern des Modals, wenn die Daten fehlen.
    return;
}

// Deutsche Bild-URL (Low-Res) holen
$imageDeUrl = get_cached_image_path($currentComicId, 'lowres');
if (!$imageDeUrl) {
    $imageDeUrl = get_cached_image_path('placeholder', 'lowres'); // Fallback
}
$fullImageDeUrl = str_starts_with($imageDeUrl, 'http') ? $imageDeUrl : DIRECTORY_PUBLIC_URL . '/' . ltrim($imageDeUrl, '/');

// === GEÄNDERT V2.1.0 ===
// $imageEnUrl wird nicht mehr direkt verwendet, wir übergeben stattdessen den Dateinamen an das JS.
// $urlOriginalbildFilename wird von renderer_comic_page.php / index.php bereitgestellt.
$originalFilename = $urlOriginalbildFilename ?? '';

// Platzhalter-Bild, das angezeigt wird, während JS das Originalbild sucht.
$imageEnPlaceholder = 'https://placehold.co/600x400/cccccc/333333?text=Original+wird+geladen...';
// === ENDE ÄNDERUNG ===

?>

<!-- Das Modal-Overlay -->
<!-- GEÄNDERT V2.1.0: data-original-filename hinzugefügt -->
<div id="report-modal" class="modal report-modal" role="dialog" aria-labelledby="report-modal-title" aria-modal="true"
    data-comic-id="<?php echo htmlspecialchars($currentComicId); ?>"
    data-original-filename="<?php echo htmlspecialchars($originalFilename); ?>">

    <!-- Der Overlay-Hintergrund (zum Schließen) -->
    <div class="modal-overlay" tabindex="-1" data-action="close-report-modal"></div>

    <!-- Modal-Inhalt -->
    <div class="modal-content report-modal-content">

        <!-- Schließen-Button (X) -->
        <button class="modal-close" data-action="close-report-modal" aria-label="Schließen">&times;</button>

        <h2 id="report-modal-title">Fehler melden (Comic: <?php echo htmlspecialchars($currentComicId); ?>)</h2>
        <p>Hier kannst du Fehler im Transkript oder im Comicbild melden.</p>

        <!-- Formular -->
        <form id="report-form" class="admin-form">
            <!-- CSRF Token (wird von JS erwartet, falls API erweitert wird) -->
            <input type="hidden" name="csrf_token" value="">

            <!-- Honeypot-Feld (Bot-Abwehr) -->
            <div class="honeypot-field" aria-hidden="true">
                <label for="report-honeypot">Bitte nicht ausfüllen</label>
                <input type="text" id="report-honeypot" name="report_honeypot" tabindex="-1">
            </div>

            <!-- Dein Name (Optional) -->
            <div>
                <label for="report-name">Dein Name/Pseudonym (Optional, falls du erwähnt werden möchtest)</label>
                <input type="text" id="report-name" name="report_name" autocomplete="name">
            </div>

            <!-- Fehlertyp (Pflichtfeld) -->
            <div>
                <label for="report-type">Fehler-Typ (Pflichtfeld)</label>
                <select id="report-type" name="report_type" required>
                    <option value="" disabled selected>Bitte auswählen...</option>
                    <option value="transcript">Transkript-Fehler</option>
                    <option value="image">Bild-Fehler</option>
                    <option value="other">Sonstiges</option>
                </select>
            </div>

            <!-- Fehlerbeschreibung (Optional, aber empfohlen) -->
            <div>
                <label for="report-description">Fehlerbeschreibung</label>
                <textarea id="report-description" name="report_description" rows="4"
                    placeholder="Bitte beschreibe den Fehler kurz. (z.B. 'Tippfehler in Zeile 3' oder 'Bild lädt nicht')"></textarea>
            </div>

            <!-- Transkript-Vorschlag (Optional) -->
            <div id="transcript-suggestion-container">
                <label for="report-transcript-suggestion">Transkript-Vorschlag (Optional)</label>
                <p id="report-suggestion-info">Bearbeite das Transkript direkt im Editor (WYSIWYG oder Code-Ansicht).
                </p>

                <!-- Diese Textarea wird von Summernote übernommen -->
                <textarea id="report-transcript-suggestion" name="report_transcript_suggestion" rows="8"></textarea>

                <!-- Sichtbares Original-Transkript als gerendertes HTML -->
                <div class="original-transcript-box">
                    <label>Original-Transkript (als Referenz)</label>
                    <!-- Dieses Div zeigt das formatierte HTML an -->
                    <div id="report-transcript-original-display" class="transcript-display-box"></div>
                </div>

                <!-- Verstecktes Feld, um das Original-Transkript mitzusenden (wird von JS befüllt) -->
                <textarea id="report-transcript-original" name="report_transcript_original" style="display: none;"
                    readonly></textarea>
            </div>

            <!-- Referenzbilder -->
            <div class="report-images-container">
                <div>
                    <label>Aktuelles Bild (DE)</label>
                    <img src="<?php echo htmlspecialchars($fullImageDeUrl); ?>" alt="Referenzbild Deutsch"
                        loading="lazy">
                </div>
                <div>
                    <label>Original (EN)</label>
                    <!-- GEÄNDERT V2.1.0: ID hinzugefügt und src auf Platzhalter gesetzt -->
                    <img id="report-modal-image-en" src="<?php echo htmlspecialchars($imageEnPlaceholder); ?>"
                        alt="Referenzbild Englisch" loading="lazy">
                </div>
            </div>

            <!-- Buttons -->
            <div class="modal-buttons">
                <button type="submit" id="report-submit-button" class="button">Meldung absenden</button>
                <button type="button" class="button" data-action="close-report-modal">Abbrechen</button>
            </div>

            <!-- Statusmeldungen (für Erfolg/Fehler) -->
            <div id="report-status-message" class="status-message"></div>
        </form>
    </div>
</div>