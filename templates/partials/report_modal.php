<?php
/**
 * @file      ROOT/templates/partials/report_modal.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   1.2.1
 * @since     1.0.0 Initiale Erstellung
 * @since     1.1.0 CSP-Fix: Alle Inline-Styles entfernt und in CSS ausgelagert.
 * @since     1.1.0 Feature: Transkript-Vorschlag zeigt nun HTML-Tags als Text an (statt sie zu entfernen).
 * @since     1.1.0 Feature: "Original-Transkript"-Textarea als sichtbare Referenz hinzugefügt.
 * @since     1.2.0 Fix: Zeilenumbrüche und Whitespace *innerhalb* von <p>-Tags werden normalisiert (zu einer Zeile).
 * @since     1.2.1 Fix: Entfernt *alle* verbleibenden Zeilenumbrüche (zwischen Tags), um Text in eine Zeile zu zwingen.
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

// === TRANSKRIPT-LOGIK (GEÄNDERT V1.2.1) ===
$rawTranscript = $comicTranscript ?? 'Kein Transkript verfügbar.';
// Zuerst Entities dekodieren (z.B. &amp; -> &)
$rawTranscript = html_entity_decode($rawTranscript, ENT_QUOTES, 'UTF-8');

// LOGIK (V1.2.0): Zeilenumbrüche und Whitespace *innerhalb* von <p>-Tags normalisieren
// 's' Modifikator, damit '.' auch Zeilenumbrüche matcht
$rawTranscriptForTextarea = preg_replace_callback(
    '/(<p[^>]*>)(.*?)(<\/p>)/is',
    function ($matches) {
        $tagOpen = $matches[1]; // <p> oder <p class="...">
        $content = $matches[2]; // Inhalt (kann andere Tags und \n enthalten)
        $tagClose = $matches[3]; // </p>
    
        // Ersetze alle Whitespace-Sequenzen (inkl. \n, \r, \t) durch ein einzelnes Leerzeichen
        $contentNormalized = preg_replace('/\s+/s', ' ', $content);
        // trim(), um führende/nachfolgende Leerzeichen zu entfernen
        $contentNormalized = trim($contentNormalized);

        // Baue den Tag wieder zusammen
        return $tagOpen . $contentNormalized . $tagClose;
    },
    $rawTranscript
);

// Fallback: Wenn preg_replace_callback fehlschlägt (null zurückgibt)
if ($rawTranscriptForTextarea === null) {
    $rawTranscriptForTextarea = $rawTranscript;
    if ($debugMode) {
        error_log("FEHLER [report_modal.php]: preg_replace_callback für Transkript-Normalisierung fehlgeschlagen.");
    }
}

// NEUE LOGIK (V1.2.1): Ersetze *alle* verbleibenden Zeilenumbrüche (die z.B. ZWISCHEN </p> und <p> stehen)
// durch ein einzelnes Leerzeichen.
$rawTranscriptForTextarea = preg_replace('/[\r\n]+/', ' ', $rawTranscriptForTextarea);
// Entferne abschließend führende/nachfolgende Leerzeichen aus dem gesamten Block.
$rawTranscriptForTextarea = trim($rawTranscriptForTextarea);
// === ENDE LOGIK ===

?>

<!-- Das Modal-Overlay -->
<!-- HINWEIS: style="display: none;" ENTFERNT (CSP-Fix) -->
<div id="report-modal" class="modal report-modal" role="dialog" aria-labelledby="report-modal-title" aria-modal="true"
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
            <!-- HINWEIS: style="display:none;" ENTFERNT (CSP-Fix) -->
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
            <!-- HINWEIS: style="display: none;" ENTFERNT (CSP-Fix) -->
            <div id="transcript-suggestion-container">
                <label for="report-transcript-suggestion">Transkript-Vorschlag (Optional)</label>
                <!-- HINWEIS: style="..." ENTFERNT, ID HINZUGEFÜGT (CSP-Fix) -->
                <p id="report-suggestion-info">Bearbeite das Transkript direkt, um eine Korrektur
                    vorzuschlagen. HTML-Tags (z.B. &lt;p&gt;) werden als Text angezeigt.</p>
                <textarea id="report-transcript-suggestion" name="report_transcript_suggestion"
                    rows="8"><?php echo htmlspecialchars($rawTranscriptForTextarea); // Verwendet V1.2.1 Variable ?></textarea>

                <!-- NEU: Sichtbares Original-Transkript als Referenz -->
                <div class="original-transcript-box">
                    <label for="report-transcript-original">Original-Transkript (als Referenz)</label>
                    <!-- HINWEIS: style="display: none;" ENTFERNT, rows="5" HINZUGEFÜGT (CSP-Fix & Feature) -->
                    <textarea id="report-transcript-original" name="report_transcript_original" rows="5"
                        readonly><?php echo htmlspecialchars($rawTranscriptForTextarea); // Verwendet V1.2.1 Variable ?></textarea>
                </div>
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
            <!-- HINWEIS: style="..." ENTFERNT (CSP-Fix) -->
            <div id="report-status-message" class="status-message"></div>
        </form>
    </div>
</div>