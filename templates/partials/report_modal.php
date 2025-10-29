<?php
/**
 * @file      ROOT/templates/partials/report_modal.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   1.0.0
 * @since     1.0.0 Initiale Erstellung
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

// Englische Bild-URL (Original) holen
$imageEnUrl = get_cached_image_path($currentComicId, 'url_originalbild');
if (!$imageEnUrl) {
    // Da dies eine externe URL ist, verwenden wir einen Standard-Platzhalter, wenn sie fehlt.
    $imageEnUrl = 'https://placehold.co/600x400/cccccc/333333?text=Original+fehlt';
}

// Transkript bereinigen (HTML-Tags entfernen) für die Textarea
$plainTranscript = $comicData['transcript'] ?? '';
$plainTranscript = strip_tags($plainTranscript);
$plainTranscript = html_entity_decode($plainTranscript, ENT_QUOTES, 'UTF-8');

?>

<!-- Das Modal-Overlay -->
<div id="report-modal" class="modal report-modal" style="display: none;" role="dialog"
    aria-labelledby="report-modal-title" aria-modal="true"
    data-comic-id="<?php echo htmlspecialchars($currentComicId); ?>">

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
            <div class="honeypot-field" style="display:none;" aria-hidden="true">
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
            <div id="transcript-suggestion-container" style="display: none;">
                <label for="report-transcript-suggestion">Transkript-Vorschlag (Optional)</label>
                <p style="font-size: 0.9em; margin-bottom: 5px;">Bearbeite das Transkript direkt, um eine Korrektur
                    vorzuschlagen.</p>
                <textarea id="report-transcript-suggestion" name="report_transcript_suggestion"
                    rows="8"><?php echo htmlspecialchars($plainTranscript); ?></textarea>
                <!-- Verstecktes Feld, um das Original-Transkript mitzusenden (für Diff-Vergleich im Admin-Bereich) -->
                <textarea id="report-transcript-original" name="report_transcript_original" style="display: none;"
                    readonly><?php echo htmlspecialchars($plainTranscript); ?></textarea>
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
                    <img src="<?php echo htmlspecialchars($imageEnUrl); ?>" alt="Referenzbild Englisch" loading="lazy"
                        onerror="this.onerror=null; this.src='https://placehold.co/600x400/cccccc/333333?text=Original+nicht+ladbar';">
                </div>
            </div>

            <!-- Buttons -->
            <div class="modal-buttons">
                <button type="submit" id="report-submit-button" class="button">Meldung absenden</button>
                <button type="button" class="button" data-action="close-report-modal">Abbrechen</button>
            </div>

            <!-- Statusmeldungen (für Erfolg/Fehler) -->
            <div id="report-status-message" class="status-message" style="display: none; margin-top: 15px;"></div>
        </form>
    </div>
</div>