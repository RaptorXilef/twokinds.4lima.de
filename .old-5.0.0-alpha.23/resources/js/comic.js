/**
 * Comic-Navigation, Lesezeichen und Fehlermeldung
 *
 * Dieses Skript wird von den einzelnen Comic-Seiten im /comic/ Verzeichnis aufgerufen.
 *
 * @file      ROOT/resources/js/comic.js /Minificed: ROOT/public/assets/js/comic.min.js
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   3.2.1
 * @since     2.5.0 Umstellung auf globale Pfad-Konstanten.
 * @since     2.5.1 Lesezeichen auf der Comicseite selbst ohne Meldung setzen und entfernen (nur deaktivieren, nicht löschen) [comic.js]
 * @since     2.5.2 J und K für den Wechsel der Comicseite deaktiviert
 * @since     3.0.0 Implementiert Summernote WYSIWYG-Editor im Report-Modal.
 * @since     3.1.0 Zentralisiert die Bild-Suchlogik (findExistingUrl) und wendet sie auf das Report-Modal an.
 * @since     3.2.0 Logik zur Bildsuche wird jetzt global (am window-Objekt) bereitgestellt, um Redundanz in Inline-Skripten zu entfernen.
 * @since     3.2.1 Fetch beim Laden des englischen Originalbildes wieder entfernt, da dies aus Sicherheitsgründen auf Toms Server gesperrt ist.
 */

// IIFE bleibt
(() => {
    // === Bookmark Logic Konstanten ===
    const bookmarkButtonId = 'add-bookmark';
    const activeBookmarkClass = 'bookmarked';
    const bookmarkMaxEntries = 50;

    // === Report Modal Logic Konstanten ===
    const openReportModalButtonId = 'open-report-modal';
    const reportModalId = 'report-modal';
    const reportFormId = 'report-form';
    const closeReportModalSelector = '[data-action="close-report-modal"]';
    const reportTypeSelectId = 'report-type';
    const transcriptSuggestionContainerId = 'transcript-suggestion-container';
    const transcriptSuggestionTextareaId = 'report-transcript-suggestion'; // ID des <textarea> für Summernote
    const originalTranscriptDisplayId = 'report-transcript-original-display'; // ID des <div> für Original
    const originalTranscriptHiddenId = 'report-transcript-original'; // ID des versteckten <textarea>
    const reportStatusMessageId = 'report-status-message';
    const reportSubmitButtonId = 'report-submit-button';
    const comicTranscriptSelector = '.transcript-content';

    // === NEU: Konstanten für die Bild-Suche ===
    const reportModalImageEnId = 'report-modal-image-en';
    const placeholderImageLoading = 'https://placehold.co/600x400/cccccc/333333?text=Original+wird+geladen...';
    const placeholderImageNotFound = 'https://placehold.co/600x400/dc3545/ffffff?text=Original+nicht+gefunden';
    const originalImageUrlBase = 'https://cdn.twokinds.keenspot.com/comics/';
    const sketchImageUrlBase = 'https://twokindscomic.com/images/'; // Hinzugefügt für EN-Button
    const imageExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
    // === ENDE NEU ===

    // Debug-Modus
    const debugModeJsComic = typeof window.phpDebugMode !== 'undefined' ? window.phpDebugMode : false;

    // === Bild-Suchlogik hier zentralisiert ===
    /**
     * Prüft asynchron, ob eine URL erreichbar ist (Bild existiert).
     * Verwendet fetch(HEAD) als Primärversuch, fällt auf new Image() zurück.
     * (Kopiert von renderer_comic_page.php)
     */
    async function checkUrlExists(url) {
        // KORRIGIERT: Wir verwenden NUR noch new Image().
        // Der fetch()-Versuch wird durch CORS (auf cdn.twokinds.keenspot.com)
        // und CSP (auf twokindscomic.com, da nicht in 'connect-src') blockiert.
        // new Image() respektiert 'img-src', was korrekt konfiguriert ist.
        if (debugModeJsComic) {
            console.log(`DEBUG: checkUrlExists (using Image() fallback only): ${url}`);
        }
        return new Promise((resolve) => {
            const img = new Image();
            img.onload = () => resolve(true);
            img.onerror = () => resolve(false);
            img.src = url;
        });
    }

    /**
     * Sucht nach der korrekten Bild-URL, indem es Erweiterungen durchprobiert.
     */
    async function findExistingUrl(baseUrl, filename, extensions) {
        for (const ext of extensions) {
            const url = baseUrl + filename + '.' + ext;
            try {
                if (await checkUrlExists(url)) {
                    // Nutzt die lokale Funktion
                    if (debugModeJsComic) console.log(`DEBUG: findExistingUrl SUCCESS for ${url}`);
                    return url;
                }
            } catch (error) {
                if (debugModeJsComic) console.log(`DEBUG: findExistingUrl Check failed for ${url}:`, error);
            }
        }
        if (debugModeJsComic) console.log(`DEBUG: findExistingUrl FAILED for ${baseUrl}${filename}`);
        return null; // Explizit null zurückgeben, wenn nichts gefunden wurde
    }
    /**
     * Sucht nach der Sketch-URL.
     */
    async function findEnglishSketchUrl(baseFilename, sketchUrlBase, extensions) {
        try {
            const sketchFilename = baseFilename.substring(0, 8) + '_sketch';
            const sketchFoundUrl = await findExistingUrl(sketchUrlBase, sketchFilename, extensions); // Nutzt die lokale Funktion
            if (sketchFoundUrl && debugModeJsComic) console.log('DEBUG: Sketch online gefunden:', sketchFoundUrl);
            else if (debugModeJsComic) console.log('DEBUG: Kein Sketch online gefunden.');
            return sketchFoundUrl;
        } catch (err) {
            console.warn('Fehler bei Sketch-Suche:', err);
            return null;
        }
    }

    /**
     * Setzt das Haupt-Comicbild und den Link auf die englische Version/Sketch.
     * (Wird vom EN-Button-Listener aufgerufen)
     */
    async function setEnglishImage(mainUrl, toggleBtn, comicImage, comicLink, langToggleText) {
        if (!comicImage || !comicLink) return;
        let englishSrc = mainUrl;
        comicImage.src = englishSrc;
        const sketchUrlFromCache = toggleBtn.dataset.englishSketchUrlFromCache;
        let sketchFoundUrl = null;

        const setHref = (url) => {
            let englishHref = url;
            comicLink.href = englishHref;
            if (langToggleText) langToggleText.textContent = 'DE';
            // isGerman wird im Listener selbst verwaltet
        };

        if (sketchUrlFromCache) {
            try {
                if (await checkUrlExists(sketchUrlFromCache)) {
                    sketchFoundUrl = sketchUrlFromCache;
                    if (debugModeJsComic) console.log('DEBUG: Sketch aus Cache verwendet:', sketchFoundUrl);
                } else if (debugModeJsComic)
                    console.warn('DEBUG: Gespeicherter Sketch-Link nicht erreichbar:', sketchUrlFromCache);
            } catch (e) {
                console.warn('DEBUG: Fehler bei Prüfung des Sketch-Cache-Links:', e);
            }
        }

        if (!sketchFoundUrl) {
            sketchFoundUrl = await findEnglishSketchUrl(
                toggleBtn.dataset.englishFilename,
                sketchImageUrlBase,
                imageExtensions
            );
        }
        setHref(sketchFoundUrl || mainUrl);
        return {englishSrc, englishHref: sketchFoundUrl || mainUrl};
    }

    /**
     * Startet die Suche nach dem Originalbild (EN).
     * (Wird vom EN-Button-Listener aufgerufen)
     */
    async function runOriginalProbingLogic(toggleBtn, langToggleText) {
        const originalText = langToggleText ? langToggleText.textContent : 'EN';
        if (langToggleText) langToggleText.textContent = 'Lade...';
        try {
            const foundUrl = await findExistingUrl(
                originalImageUrlBase,
                toggleBtn.dataset.englishFilename,
                imageExtensions
            );
            if (foundUrl) {
                return foundUrl;
            } else {
                throw new Error('Kein englisches Bild gefunden');
            }
        } catch (err) {
            console.error('Fehler beim Finden des Originalbilds:', err);
            if (langToggleText) langToggleText.textContent = 'Original nicht gefunden';
            setTimeout(() => {
                if (langToggleText) langToggleText.textContent = originalText;
            }, 2000);
            return null;
        }
    }

    // === NEU V3.2.0: Funktionen global verfügbar machen ===
    // (comic.js wird VOR dem Inline-Skript geladen,
    // also sind diese beim DOMContentLoaded des Inline-Skripts verfügbar)
    window.checkUrlExists = checkUrlExists;
    window.findExistingUrl = findExistingUrl;
    window.findEnglishSketchUrl = findEnglishSketchUrl;
    window.setEnglishImage = setEnglishImage;
    window.runOriginalProbingLogic = runOriginalProbingLogic;
    // === ENDE NEU V3.2.0 ===

    document.addEventListener('DOMContentLoaded', async () => {
        // === NEU: Summernote initialisieren ===
        // Wir prüfen, ob jQuery und Summernote geladen wurden
        if (typeof $ !== 'undefined' && typeof $.fn.summernote !== 'undefined') {
            $('#' + transcriptSuggestionTextareaId).summernote({
                placeholder: 'Transkript-Vorschlag hier eingeben...',
                tabsize: 2,
                height: 200, // Höhe des Editors
                toolbar: [
                    // Minimales Toolbar-Set für Benutzer
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'clear']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['view', ['codeview']], // Erlaube Umschalten zur Code-Ansicht
                ],
            });
            // Initial leer lassen, wird beim Öffnen des Modals gefüllt
            $('#' + transcriptSuggestionTextareaId).summernote('code', '');
        } else {
            if (debugModeJsComic) console.error('[Report Modal] jQuery oder Summernote ist nicht geladen!');
        }

        // Key Navigation Logic
        var body = document.getElementsByTagName('body')[0];
        var navprev = document.querySelector('a.navprev');
        var navnext = document.querySelector('a.navnext');

        body.addEventListener('keyup', (e) => {
            if (e.key == 'ArrowLeft' && navprev) {
                parent.location = navprev.getAttribute('href');
            } else if (e.key == 'ArrowRight' && navnext) {
                parent.location = navnext.getAttribute('href');
            }
        });

        /*
        body.addEventListener("keyup", (e) => {
      if (e.key == "ArrowLeft" || e.key == "j" || e.key == "J" && navprev) {
        // J deaktiviert
        parent.location = navprev.getAttribute("href");
      } else if (
        e.key == "ArrowRight" || e.key == "k" || e.key == "K" && // K korrigiert // K deaktiviert
        navnext
      ) {
        parent.location = navnext.getAttribute("href");
      }
});
*/

        // Bookmark Init Logic
        const bookmarkButton = document.getElementById(bookmarkButtonId);
        const bookmarks = await getStoredBookmarks();

        // === Init Logic für Comic-Seite oder Lesezeichen-Seite (Original + Report Init) ===
        if (bookmarkButton) {
            // Auf einer Comic-Seite
            bookmarkButton.addEventListener('click', async (e) => {
                // Verwende e.target wie im Original, Validierung findet in toggleBookmark statt
                await toggleBookmark(e.target);
            });
            // Initialen Status setzen
            const initialId = bookmarkButton.getAttribute('data-id');
            if (initialId && bookmarks.has(initialId)) {
                setBookmarkButtonActive();
            }

            // === Report Modal Init ===
            initializeReportModal();
        } else if (document.getElementById('bookmarksPage')) {
            // Auf der Lesezeichen-Seite
            populateBookmarksPage(bookmarks);
            const removeAllBtn = document.getElementById('removeAll');
            const exportBtn = document.getElementById('export');
            const importBtn = document.getElementById('importButton');
            const importFile = document.getElementById('import');

            if (removeAllBtn) removeAllBtn.addEventListener('click', handleRemoveAllBookmarks);
            if (exportBtn) exportBtn.addEventListener('click', handleExportBookmarks);
            if (importBtn) importBtn.addEventListener('click', () => importFile?.click());
            if (importFile) importFile.addEventListener('change', handleImportBookmarks);

            if (bookmarks.size === 0) {
                if (removeAllBtn) removeAllBtn.disabled = true;
                if (exportBtn) exportBtn.disabled = true;
            }
        }
    }); // Ende DOMContentLoaded

    // ===========================================
    // === Funktion zum Initialisieren des Report Modals ===
    // ===========================================
    function initializeReportModal() {
        // (Konstanten für Modal-Elemente)
        const openReportModalButton = document.getElementById(openReportModalButtonId);
        const reportModal = document.getElementById(reportModalId);
        const reportForm = document.getElementById(reportFormId);
        const reportTypeSelect = document.getElementById(reportTypeSelectId);
        const transcriptSuggestionContainer = document.getElementById(transcriptSuggestionContainerId);
        // === GEÄNDERT: Referenzen auf neue Elemente ===
        const originalTranscriptDisplay = document.getElementById(originalTranscriptDisplayId);
        const originalTranscriptHidden = document.getElementById(originalTranscriptHiddenId);
        const reportStatusMessage = document.getElementById(reportStatusMessageId);
        const reportSubmitButton = document.getElementById(reportSubmitButtonId);
        const closeReportModalButtons = document.querySelectorAll(closeReportModalSelector);
        const imageEn = document.getElementById(reportModalImageEnId); // NEU V3.1.0

        if (openReportModalButton && reportModal && reportForm) {
            // --- Event Listeners ---
            openReportModalButton.addEventListener('click', openReportModal);
            closeReportModalButtons.forEach((button) => button.addEventListener('click', closeReportModal));
            reportModal.addEventListener('click', (event) => {
                if (event.target === reportModal.querySelector('.modal-overlay')) closeReportModal();
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && reportModal.style.display === 'flex') closeReportModal();
            });
            reportTypeSelect.addEventListener('change', () => {
                transcriptSuggestionContainer.style.display =
                    reportTypeSelect.value === 'transcript' ? 'block' : 'none';
            });
            reportForm.addEventListener('submit', handleReportSubmit);

            // --- Funktionen ---
            function openReportModal() {
                reportForm.reset();
                showReportStatus('', 'info', false); // Status leeren
                reportSubmitButton.disabled = false;

                // WICHTIG: Display-Status *nach* dem Reset setzen
                transcriptSuggestionContainer.style.display =
                    reportTypeSelect.value === 'transcript' ? 'block' : 'none';

                // === GEÄNDERT V3.2.0: Englisches Bild dynamisch laden (nutzt jetzt die lokale/globale Funktion) ===
                if (imageEn) {
                    const filename = reportModal.dataset.originalFilename;
                    imageEn.src = placeholderImageLoading; // Setze auf "Laden..."
                    imageEn.onerror = null;

                    if (filename) {
                        if (debugModeJsComic)
                            console.log(`[Report Modal] Suche Originalbild mit Dateiname: ${filename}`);
                        // Rufe die Suchfunktion auf (jetzt im Scope verfügbar)
                        findExistingUrl(originalImageUrlBase, filename, imageExtensions)
                            .then((url) => {
                                if (url) {
                                    imageEn.src = url;
                                    // NEU: Sobald das Bild gefunden wurde, Telemetrie aktualisieren!
                                    imageEn.onload = () => {
                                        if (debugTextarea) debugTextarea.value = collectDebugInfo();
                                    };
                                    // Setze einen onerror-Fallback für den Fall, dass das gefundene Bild doch fehlschlägt
                                    imageEn.onerror = () => {
                                        imageEn.src = placeholderImageNotFound;
                                    };
                                } else {
                                    // findExistingUrl hat null zurückgegeben (nichts gefunden)
                                    if (debugModeJsComic)
                                        console.warn('[Report Modal] findExistingUrl hat null zurückgegeben.');
                                    imageEn.src = placeholderImageNotFound;
                                    if (debugTextarea) debugTextarea.value = collectDebugInfo();
                                }
                            })
                            .catch((err) => {
                                // Schwerer Fehler während der Suche
                                if (debugModeJsComic) console.error('[Report Modal] Fehler bei findExistingUrl:', err);
                                imageEn.src = placeholderImageNotFound;
                                if (debugTextarea) debugTextarea.value = collectDebugInfo();
                            });
                    } else {
                        // Fallback, falls kein Dateiname im data-Attribut ist
                        if (debugModeJsComic)
                            console.warn("[Report Modal] Kein 'data-original-filename' am Modal gefunden.");
                        imageEn.src = 'https://placehold.co/600x400/cccccc/333333?text=Original+fehlt+(kein+Dateiname)';
                    }
                    reportModal.style.display = 'flex';
                    document.getElementById('report-name')?.focus();
                }
                // === ENDE GEÄNDERT V3.2.0 ===

                // === Summernote und Display-Div befüllen ===
                try {
                    const currentTranscriptElement = document.querySelector(comicTranscriptSelector);
                    const rawHtml = currentTranscriptElement ? currentTranscriptElement.innerHTML.trim() : '';

                    // 1. Fülle den Summernote Editor
                    if (typeof $ !== 'undefined' && typeof $.fn.summernote !== 'undefined') {
                        $('#' + transcriptSuggestionTextareaId).summernote('code', rawHtml);
                    } else {
                        // Fallback, falls Summernote nicht geladen ist (sollte nicht passieren)
                        const fallbackTextarea = document.getElementById(transcriptSuggestionTextareaId);
                        if (fallbackTextarea)
                            fallbackTextarea.value =
                                'Editor konnte nicht geladen werden. Bitte beschreibe den Fehler stattdessen.';
                    }

                    // 2. Fülle das sichtbare "Original" Display
                    if (originalTranscriptDisplay) {
                        originalTranscriptDisplay.innerHTML = rawHtml;
                    }

                    // 3. Fülle das *versteckte* Textarea
                    if (originalTranscriptHidden) {
                        originalTranscriptHidden.value = rawHtml;
                    }
                } catch (e) {
                    console.error('[Report Modal] Fehler beim Befüllen des Transkripts:', e);
                    // Fehler-Fallback
                    if (typeof $ !== 'undefined' && typeof $.fn.summernote !== 'undefined') {
                        $('#' + transcriptSuggestionTextareaId).summernote('code', '');
                    }
                    if (originalTranscriptDisplay)
                        originalTranscriptDisplay.innerHTML = '<p>Fehler beim Laden des Original-Transkripts.</p>';
                    if (originalTranscriptHidden) originalTranscriptHidden.value = '';
                }

                // === NEU: Debug-Informationen sammeln ===
                const debugTextarea = document.getElementById('report-debug-info');
                if (debugTextarea) {
                    debugTextarea.value = collectDebugInfo();
                }

                reportModal.style.display = 'flex';
                document.getElementById('report-name')?.focus();
            }

            function collectDebugInfo() {
                const m = document.getElementById('report-modal');
                const d = m.dataset;

                // 1. Hires-URL: Jetzt exakt über die von dir genannte ID
                const hiresLinkElement = document.getElementById('comic-image-link');
                const actualHiresUrl = hiresLinkElement ? hiresLinkElement.href : 'Nicht im DOM gefunden';

                // 2. Comic-Name: Fallback-Kette
                const comicName =
                    d.debugComicName || document.querySelector('h1')?.innerText.split('|')[0].trim() || 'N/A';

                // 3. Original-URL (Das Ergebnis der JS-Suche)
                const probedImageEn = document.getElementById('report-modal-image-en');
                let currentOriginalUrl = 'Wird gesucht...';
                if (probedImageEn && probedImageEn.src) {
                    // Falls der Placeholder noch drin ist, zeigen wir "Lade..."
                    currentOriginalUrl = probedImageEn.src.includes('placehold.co')
                        ? 'Suche läuft...'
                        : probedImageEn.src;
                }

                // 4. Charaktere (Duplikate entfernen)
                const charElements = document.querySelectorAll('#char-display-wrapper a span');
                const activeChars =
                    charElements.length > 0
                        ? [...new Set(Array.from(charElements).map((el) => el.innerText.trim()))].join(', ')
                        : 'Keine Charaktere gefunden';

                // 5. Transkript Snippet
                const transcriptEl = document.querySelector('.transcript-content');
                const transcriptSnippet = transcriptEl
                    ? transcriptEl.innerText.substring(0, 300).trim().replace(/\s+/g, ' ')
                    : 'N/A';

                const consentData = localStorage.getItem('cookie_consent') || 'Nicht gesetzt';

                let info = `--- COMIC TELEMETRY (DYNAMIC) ---\n`;
                info += `ID:         ${d.debugComicId}\n`;
                info += `Name:       ${comicName}\n`;
                info += `Lowres:     ${d.debugUrlLowres}\n`;
                info += `Hires:      ${actualHiresUrl}\n`; // FIX: Jetzt korrekt befüllt!
                info += `Original:   ${currentOriginalUrl}\n`; // Reaktiv nach Load
                info += `Characters: ${activeChars}\n`;
                info += `Transcript: ${transcriptSnippet}...\n\n`;

                info += `--- ENVIRONMENT ---\n`;
                info += `Browser/OS: ${navigator.userAgent}\n`;
                info += `Resolution: ${window.screen.width}x${window.screen.height} (DPR: ${window.devicePixelRatio})\n`;
                info += `Viewport:   ${window.innerWidth}x${window.innerHeight}\n`;
                info += `Theme:      ${window.matchMedia('(prefers-color-scheme: dark)').matches ? 'Dark' : 'Light'}\n\n`;

                info += `--- STORAGE & COOKIES ---\n`;
                info += `Consent:    ${consentData}\n`;
                info += `Cookies:    ${document.cookie || 'Keine Cookies lesbar'}\n`;

                return info;
            }

            function closeReportModal() {
                reportModal.style.display = 'none';
                // Leere den Summernote-Editor beim Schließen, damit kein alter Inhalt verbleibt
                if (typeof $ !== 'undefined' && typeof $.fn.summernote !== 'undefined') {
                    $('#' + transcriptSuggestionTextareaId).summernote('code', '');
                }

                // === NEU V3.1.0: Bild zurücksetzen ===
                if (imageEn) {
                    imageEn.src = placeholderImageLoading;
                    imageEn.onerror = null;
                }
            }

            async function handleReportSubmit(event) {
                event.preventDefault();
                reportSubmitButton.disabled = true;
                showReportStatus('', 'info', false);

                if (!reportTypeSelect.value) {
                    showReportStatus('Bitte wähle einen Fehlertyp aus.', 'red');
                    reportSubmitButton.disabled = false;
                    return;
                }
                const comicId = reportModal.dataset.comicId;
                if (!comicId) {
                    showReportStatus('Fehler: Comic-ID nicht gefunden.', 'red');
                    reportSubmitButton.disabled = false;
                    return;
                }

                const formData = new FormData(reportForm);
                const data = Object.fromEntries(formData.entries());
                data.comic_id = comicId;

                // === GEÄNDERT: HTML-Inhalt aus Summernote holen ===
                if (typeof $ !== 'undefined' && typeof $.fn.summernote !== 'undefined') {
                    const suggestionHtml = $('#' + transcriptSuggestionTextareaId).summernote('code');
                    data.report_transcript_suggestion = suggestionHtml;
                } else {
                    // Fallback, falls Summernote fehlgeschlagen ist
                    data.report_transcript_suggestion =
                        document.getElementById(transcriptSuggestionTextareaId).value || '';
                }
                // === ENDE ÄNDERUNG ===

                // Der Rest (Name, Description, Original) kommt korrekt aus dem FormData
                data.report_name = data.report_name || 'Anonym';
                data.report_description = data.report_description || '';
                data.report_transcript_original = data.report_transcript_original || '';
                // delete data.report_honeypot;

                // Hinweis: Honeypot wird mitgesendet, damit der Server ihn prüfen kann.

                // Sicherstellen, dass wir die URL vom Formular nehmen
                const form = event.currentTarget;
                const apiUrl = form.dataset.apiUrl || form.getAttribute('action');

                if (debugModeJsComic) {
                    console.log(`[Report Modal] Ziel-URL: ${apiUrl}`);
                }

                try {
                    const response = await fetch(apiUrl, {
                        method: 'POST',
                        redirect: 'manual', // Verhindert das automatische Folgen von Redirects für besseres Debugging
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify(data),
                    });

                    if (response.type === 'opaqueredirect' || response.status === 301 || response.status === 302) {
                        throw new Error(
                            "Server erzwingt einen Redirect. Bitte prüfe, ob die API-URL ein '.php' benötigt."
                        );
                    }

                    if (debugModeJsComic)
                        console.log('[Report Modal] API Antwort Status:', response.status, response.statusText);

                    let responseText = await response.text();
                    console.log('[DEBUG] Vollständige Antwort vom Server:', responseText); // Das hier einfügen!
                    let result = {};

                    if (response.headers.get('content-type')?.includes('application/json')) {
                        try {
                            result = JSON.parse(responseText);
                            if (debugModeJsComic) console.log('[Report Modal] API Antwort JSON:', result);
                        } catch (jsonError) {
                            if (debugModeJsComic) {
                                console.error('[Report Modal] Fehler beim Parsen der JSON-Antwort:', jsonError);
                                console.log('[Report Modal] Empfangener Text:', responseText);
                            }
                            throw new Error(
                                `Server antwortete mit JSON-Header, aber Parsen schlug fehl (Status ${
                                    response.status
                                }). Antwort-Anfang: ${responseText.substring(0, 200)}...`
                            );
                        }
                    } else {
                        if (debugModeJsComic)
                            console.log('[Report Modal] API antwortete nicht mit JSON. Antwort-Text:', responseText);
                        let errorMsg = `Server antwortete nicht mit JSON (Status ${response.status}).`;
                        if (responseText.toLowerCase().includes('not found') || response.status === 404) {
                            errorMsg = `API-Endpunkt nicht gefunden (${apiUrl}). Bitte Pfad prüfen.`;
                        } else if (responseText) {
                            errorMsg += ` Antwort-Anfang: ${responseText.substring(0, 200)}...`;
                        }
                        //throw new Error(errorMsg);
                        console.error('[DEBUG] Erwartete JSON, erhielt aber:', responseText.substring(0, 500));

                        // Wir ändern die Fehlermeldung temporär, um nicht zu raten:
                        throw new Error(`Server-Antwort war kein JSON (Status ${response.status}). Prüfe die Konsole!`);
                    }

                    if (response.ok && result.success) {
                        showReportStatus(result.message || 'Meldung erfolgreich gesendet!', 'green');
                        reportForm.reset(); // Formular zurücksetzen

                        // NEU: Auch Summernote und Display-Box leeren
                        if (typeof $ !== 'undefined' && typeof $.fn.summernote !== 'undefined') {
                            $('#' + transcriptSuggestionTextareaId).summernote('code', '');
                        }
                        if (originalTranscriptDisplay) originalTranscriptDisplay.innerHTML = '';

                        // NEU: Modal nach 1.5 Sekunden schließen, damit die Meldung gelesen werden kann
                        setTimeout(() => {
                            closeReportModal();
                        }, 1500);
                    } else {
                        showReportStatus(
                            result.message ||
                                `Fehler ${response.status}: ${response.statusText || 'Unbekannter Serverfehler'}`,
                            'red'
                        );
                    }
                } catch (error) {
                    console.error('[Report Modal] Fehler beim Senden des Reports (Fetch/Netzwerk/Parse):', error);
                    showReportStatus(`Ein Fehler ist aufgetreten: ${error.message}`, 'red');
                } finally {
                    reportSubmitButton.disabled = false;
                }
            }

            function showReportStatus(message, type, display = true) {
                if (display) {
                    reportStatusMessage.textContent = message;
                    reportStatusMessage.className = `status-message status-${type}`;
                    reportStatusMessage.style.display = 'block';
                } else {
                    reportStatusMessage.style.display = 'none';
                    reportStatusMessage.textContent = '';
                    reportStatusMessage.className = 'status-message';
                }
            }
        } else {
            if (document.getElementById(bookmarkButtonId)) {
                if (debugModeJsComic)
                    console.warn(
                        '[Report Modal] Elemente für das Report-Modal wurden nicht gefunden, obwohl der Button existiert.'
                    );
            }
        }
    } // Ende initializeReportModal

    // ===========================================
    // === Bookmark Functions (Rest des Skripts) ===
    // ===========================================
    async function getStoredBookmarks() {
        // Wie Original
        if (typeof window.localStorage == 'undefined') return new Map();
        try {
            const stored = window.localStorage.getItem('comicBookmarks');
            const parsedData = stored ? JSON.parse(stored) : [];
            return Array.isArray(parsedData) ? new Map(parsedData) : new Map();
        } catch (e) {
            console.error('[Bookmark/Lesezeichen] Fehler beim Laden:', e);
            window.localStorage.removeItem('comicBookmarks');
            return new Map();
        }
    }

    async function storeBookmarks(bookmarkMap) {
        // Wie Original
        if (typeof window.localStorage == 'undefined') return;
        try {
            const cleanedBookmarksArray = [];
            for (const [id, data] of bookmarkMap.entries()) {
                // Füge 'id' zum data-Objekt hinzu, falls es fehlt (aus älteren Versionen)
                if (data && !data.id) {
                    data.id = id;
                }
                // KORRIGIERT V5: Prüfe nur auf notwendige Felder, erlaube leere 'page'
                if (data && data.id === id && data.page !== undefined && data.thumb && data.permalink) {
                    data.added = data.added || Date.now();
                    cleanedBookmarksArray.push([id, data]);
                } else {
                    if (debugModeJsComic)
                        console.warn(
                            `[Bookmark/Lesezeichen] Ungültiges Lesezeichen ${id} beim Speichern entfernt:`,
                            data
                        );
                }
            }
            window.localStorage.setItem('comicBookmarks', JSON.stringify(cleanedBookmarksArray));
        } catch (e) {
            console.error('[Bookmark/Lesezeichen] Fehler beim Speichern:', e);
            // KORRIGIERT V5: Verwende nativen Alert als Fallback
            if (e.name === 'QuotaExceededError' || e.name === 'NS_ERROR_DOM_QUOTA_REACHED') {
                alert('Speicherlimit für Lesezeichen erreicht. Bitte lösche alte Lesezeichen.');
            } else {
                alert('Ein Fehler ist beim Speichern der Lesezeichen aufgetreten.');
            }
        }
    }

    async function toggleBookmark(targetElement) {
        // Erwartet das geklickte Element (e.target)
        // KORRIGIERT V5: Finde den Button, falls in ein inneres Element geklickt wurde (z.B. Textknoten)
        const button = targetElement.closest('button');

        if (!button || button.id !== bookmarkButtonId) {
            if (debugModeJsComic)
                console.error(
                    '[Bookmark/Lesezeichen] toggleBookmark: Klick-Ziel ist nicht der Lesezeichen-Button oder konnte nicht gefunden werden.',
                    targetElement
                );
            return;
        }
        if (debugModeJsComic) console.log('[Bookmark/Lesezeichen V5] Button Dataset:', button.dataset);

        const {id, page, permalink, thumb} = button.dataset;

        // KORRIGIERT V5: Validiere nur id, permalink und thumb. 'page' darf leer sein.
        if (!id || !permalink || !thumb) {
            console.error('[Bookmark/Lesezeichen] Fehlende kritische Datenattribute (id, permalink, thumb).', {
                id,
                page,
                permalink,
                thumb,
            });
            // KORRIGIERT V5: Verwende nativen Alert als Fallback
            alert('Fehler: Lesezeichen konnte nicht hinzugefügt/entfernt werden (fehlende Daten).');
            return;
        }
        // Gib 'page' einen Standardwert, falls es leer ist, bevor es verwendet wird. '' ist ok.
        const safePage = page || '';

        const bookmarks = await getStoredBookmarks();

        if (bookmarks.has(id)) {
            // KORRIGIERT V5: Verwende nativen Confirm als Fallback
            if (
                confirm(
                    `Das Lesezeichen für Seite "${
                        // Möchten Sie das Lesezeichen für Seite
                        safePage || id
                    }" wirklich entfernen?`
                )
            ) {
                bookmarks.delete(id);
                await storeBookmarks(bookmarks);
                setBookmarkButtonInactive();
                if (document.getElementById('bookmarksPage')) populateBookmarksPage(bookmarks);
                // KORRIGIERT V5: Verwende nativen Alert als Fallback
                // alert(`Lesezeichen für Seite "${safePage || id}" entfernt.`); // Meldung deaktiviert
            }
        } else {
            if (bookmarks.size >= bookmarkMaxEntries) {
                // KORRIGIERT V5: Verwende nativen Confirm als Fallback
                if (
                    confirm(
                        `Maximale Anzahl von ${bookmarkMaxEntries} Lesezeichen erreicht. Möchtest du das älteste Lesezeichen entfernen, um dieses hinzuzufügen?`
                    )
                ) {
                    const bookmarksSortedArray = [...bookmarks.entries()].sort(
                        (a, b) => (a[1].added || 0) - (b[1].added || 0)
                    ); // Sortiere nach 'added' (älteste zuerst)
                    if (bookmarksSortedArray.length > 0) {
                        const oldestId = bookmarksSortedArray[0][0];
                        bookmarks.delete(oldestId);
                        if (debugModeJsComic)
                            console.log('[Bookmark/Lesezeichen] Ältestes Lesezeichen entfernt:', oldestId);
                    }
                    bookmarks.set(id, {
                        id,
                        page: safePage,
                        permalink,
                        thumb,
                        added: Date.now(),
                    });
                    await storeBookmarks(bookmarks);
                    setBookmarkButtonActive();
                    if (document.getElementById('bookmarksPage')) populateBookmarksPage(bookmarks);
                    // KORRIGIERT V5: Verwende nativen Alert als Fallback
                    alert(`Lesezeichen für Seite "${safePage || id}" hinzugefügt.`); // Meldung ggf. deaktivieren
                }
            } else {
                // Normales Hinzufügen mit Zeitstempel
                bookmarks.set(id, {
                    id,
                    page: safePage,
                    permalink,
                    thumb,
                    added: Date.now(),
                });
                await storeBookmarks(bookmarks);
                setBookmarkButtonActive();
                if (document.getElementById('bookmarksPage')) populateBookmarksPage(bookmarks);
                // KORRIGIERT V5: Verwende nativen Alert als Fallback
                // alert(`Lesezeichen für Seite "${safePage || id}" hinzugefügt.`); // Meldung deaktiviert
            }
        }
    }

    function setBookmarkButtonActive() {
        // Original-Funktion
        const button = document.getElementById(bookmarkButtonId);
        if (button) {
            button.classList.add(activeBookmarkClass);
            button.title = 'Lesezeichen entfernt';
        }
    }
    function setBookmarkButtonInactive() {
        // Original-Funktion
        const button = document.getElementById(bookmarkButtonId);
        if (button) {
            button.classList.remove(activeBookmarkClass);
            button.title = 'Diese Seite mit Lesezeichen versehen';
        }
    }

    // ===========================================
    // === Bookmark Page Functions (Wie Original V1 + Verbesserungen V3/V4) ===
    // ===========================================
    function populateBookmarksPage(bookmarkMap) {
        const bookmarksSection = document.querySelector('#bookmarksWrapper');
        const noBookmarksTemplate = document.querySelector('#noBookmarks');
        const bookmarkWrapperTemplate = document.querySelector('#pageBookmarkWrapper');
        const pageBookmarkTemplate = document.querySelector('#pageBookmark');

        if (!bookmarksSection || !noBookmarksTemplate || !bookmarkWrapperTemplate || !pageBookmarkTemplate) {
            console.error('[Bookmark Page] Ein oder mehrere Templates fehlen.');
            return;
        }

        bookmarksSection.innerHTML = '';
        const removeAllBtn = document.getElementById('removeAll');
        const exportBtn = document.getElementById('export');

        if (!bookmarkMap || bookmarkMap.size === 0) {
            bookmarksSection.appendChild(noBookmarksTemplate.content.cloneNode(true));
            if (removeAllBtn) removeAllBtn.disabled = true;
            if (exportBtn) exportBtn.disabled = true;
            return;
        }

        const wrapper = bookmarkWrapperTemplate.content.cloneNode(true);
        bookmarksSection.appendChild(wrapper);
        const chapterLinksContainer = bookmarksSection.querySelector('.chapter-links');
        if (!chapterLinksContainer) {
            console.error("[Bookmark Page] Container '.chapter-links' nicht gefunden.");
            return;
        }

        // Sortiere nach hinzugefügtem Datum (neueste zuerst), falls vorhanden, sonst nach ID (Original Fallback)
        const bookmarksSortedArray = [...bookmarkMap.entries()].sort((a, b) => {
            const dateA = a[1]?.added || 0;
            const dateB = b[1]?.added || 0;
            if (dateB !== dateA) {
                return dateB - dateA;
            } // Neueste zuerst nach Zeitstempel
            // Fallback: Sortiere nach ID (Comic-Datum), neueste zuerst (Original)
            return (b[0] || '').localeCompare(a[0] || '');
        });
        const bookmarksSorted = new Map(bookmarksSortedArray);

        bookmarksSorted.forEach((b, id) => {
            if (!b || !b.id || b.page === undefined || !b.permalink || !b.thumb) {
                // Erlaube leere 'page'
                if (debugModeJsComic) console.warn(`[Bookmark Page] Ungültiges Objekt für ID ${id} übersprungen:`, b);
                return;
            }
            const bookmark = pageBookmarkTemplate.content.cloneNode(true);
            const link = bookmark.querySelector('a');
            link.href = b.permalink;

            const pageNumSpan = bookmark.querySelector('span');
            let pageName = '';
            // Text-Logik wie V4
            if (window.comicData && window.comicData[b.id]) {
                const comicDetails = window.comicData[b.id];
                const comicId = b.id;
                if (comicId && comicId.length === 8) {
                    const year = comicId.substring(0, 4),
                        month = comicId.substring(4, 6),
                        day = comicId.substring(6, 8);
                    const formattedDate = `${day}.${month}.${year}`;
                    pageName = `Seite vom ${formattedDate}`;
                    if (comicDetails.name && comicDetails.name.trim() !== '') pageName += `: ${comicDetails.name}`;
                } else pageName = b.page || `Seite ${b.id}`;
            } else pageName = b.page || `Seite ${b.id}`;

            const pageNumTextNode = document.createTextNode(pageName);
            const deleteButton = pageNumSpan.querySelector('.delete');
            if (deleteButton) pageNumSpan.insertBefore(pageNumTextNode, deleteButton);
            else pageNumSpan.appendChild(pageNumTextNode);
            link.title = pageName;

            const image = bookmark.querySelector('img');
            image.src = b.thumb;
            image.alt = pageName;
            image.onerror = function () {
                // Wie V4
                this.onerror = null;
                this.src = 'https://placehold.co/96x96/cccccc/333333?text=Bild%0AFehlt';
                if (debugModeJsComic) console.warn(`[Bookmark Page] Thumbnail ${b.id} Fehler: ${b.thumb}`);
            };

            const currentDeleteButton = bookmark.querySelector('.delete');
            if (currentDeleteButton) {
                currentDeleteButton.addEventListener('click', async (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    await handleRemoveBookmarkById(b.id, pageName); // pageName übergeben wie V4
                });
            }
            chapterLinksContainer.appendChild(bookmark);
        });

        if (removeAllBtn) removeAllBtn.disabled = false;
        if (exportBtn) exportBtn.disabled = false;
    }

    async function handleRemoveBookmarkById(id, pageName) {
        // Wie V4
        // KORRIGIERT V5: Verwende nativen Confirm
        if (confirm(`Möchten Sie das Lesezeichen für "${pageName}" wirklich entfernen?`)) {
            const bookmarks = await getStoredBookmarks();
            bookmarks.delete(id);
            await storeBookmarks(bookmarks);
            populateBookmarksPage(bookmarks);
            // KORRIGIERT V5: Verwende nativen Alert
            alert(`Lesezeichen für "${pageName}" entfernt.`);
        }
    }

    async function handleRemoveAllBookmarks() {
        // Wie V4
        // KORRIGIERT V5: Verwende nativen Confirm
        if (
            confirm(
                'Möchten Sie wirklich alle Lesezeichen entfernen? Diese Aktion kann nicht rückgängig gemacht werden.'
            )
        ) {
            await storeBookmarks(new Map());
            populateBookmarksPage(new Map());
            // KORRIGIERT V5: Verwende nativen Alert
            alert('Alle Lesezeichen wurden entfernt.');
            const removeAllBtn = document.getElementById('removeAll');
            const exportBtn = document.getElementById('export');
            if (removeAllBtn) removeAllBtn.disabled = true;
            if (exportBtn) exportBtn.disabled = true;
        }
    }

    async function handleExportBookmarks() {
        // Wie V4
        const bookmarks = await getStoredBookmarks();
        if (bookmarks.size === 0) {
            // KORRIGIERT V5: Verwende nativen Alert
            alert('Es gibt keine Lesezeichen zum Exportieren.');
            return;
        }
        const dataStr = JSON.stringify(Array.from(bookmarks.entries()), null, 2);
        const blob = new Blob([dataStr], {type: 'application/json'});
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        const date = new Date(),
            dateString = `${date.getFullYear()}${(date.getMonth() + 1)
                .toString()
                .padStart(2, '0')}${date.getDate().toString().padStart(2, '0')}`;
        a.href = url;
        a.download = `twokinds.4lima.de_bookmarks_${dateString}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        // KORRIGIERT V5: Verwende nativen Alert
        alert('Lesezeichen erfolgreich exportiert.');
    }

    async function handleImportBookmarks(event) {
        // Wie V4
        const file = event.target.files[0];
        if (!file) return;
        if (file.size > 1 * 1024 * 1024) {
            // KORRIGIERT V5: Verwende nativen Alert
            alert('Fehler: Importdatei > 1MB.');
            event.target.value = '';
            return;
        }
        if (file.type !== 'application/json') {
            // KORRIGIERT V5: Verwende nativen Alert
            alert('Fehler: Keine .json-Datei.');
            event.target.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = async (e) => {
            try {
                const importedData = JSON.parse(e.target.result);
                if (!Array.isArray(importedData)) throw new Error('Ungültiges JSON: Array erwartet.');

                const validBookmarksArray = [];
                let invalidCount = 0;
                importedData.forEach((item) => {
                    // KORRIGIERT V5: Erlaube leere page (page !== undefined)
                    if (
                        Array.isArray(item) &&
                        item.length === 2 &&
                        typeof item[0] === 'string' &&
                        typeof item[1] === 'object' &&
                        item[1] !== null &&
                        item[1].id === item[0] &&
                        item[1].page !== undefined &&
                        item[1].permalink &&
                        item[1].thumb
                    ) {
                        item[1].added = item[1].added || Date.now();
                        validBookmarksArray.push(item);
                    } else {
                        invalidCount++;
                        if (debugModeJsComic) console.warn('[Bookmark Import] Ungültiger Eintrag übersprungen:', item);
                    }
                });

                if (validBookmarksArray.length === 0)
                    throw new Error(
                        invalidCount > 0 ? 'Keine gültigen Einträge gefunden.' : 'Datei enthält keine Lesezeichen.'
                    );

                const newBookmarks = new Map(validBookmarksArray);
                const currentBookmarks = await getStoredBookmarks();
                let addedCount = 0,
                    skippedCount = 0;

                newBookmarks.forEach((value, key) => {
                    if (!currentBookmarks.has(key)) {
                        if (currentBookmarks.size < bookmarkMaxEntries) {
                            currentBookmarks.set(key, value);
                            addedCount++;
                        } else skippedCount++;
                    } else skippedCount++;
                });

                await storeBookmarks(currentBookmarks);
                populateBookmarksPage(currentBookmarks);

                let message = `${addedCount} Lesezeichen importiert.`;
                if (skippedCount > 0)
                    message += ` ${skippedCount} Duplikate oder Lesezeichen über dem Limit wurden übersprungen.`;
                if (invalidCount > 0) message += ` ${invalidCount} ungültige Einträge wurden ignoriert.`;
                // KORRIGIERT V5: Verwende nativen Alert
                alert(message);
            } catch (error) {
                console.error('[Bookmark Import] Fehler:', error);
                // KORRIGIERT V5: Verwende nativen Alert
                alert(`Fehler beim Importieren: ${error.message}`);
            } finally {
                event.target.value = '';
            }
        };
        reader.onerror = () => {
            // KORRIGIERT V5: Verwende nativen Alert
            alert('Fehler beim Lesen der Datei.');
            event.target.value = '';
        };
        reader.readAsText(file);
    }

    // ===========================================
    // === Custom Alert & Confirm Fallbacks (NUR NOCH NATIVE) ===
    // ===========================================
    // KORRIGIERT V5: showCustomAlert und showCustomConfirm sind jetzt nur noch Wrapper für alert/confirm
    function showCustomAlert(message) {
        if (debugModeJsComic) console.log(`[Alert Fallback]: ${message}`);
        alert(message);
    }
    function showCustomConfirm(message, onConfirm, onCancel = () => {}) {
        if (debugModeJsComic) console.log(`[Confirm Fallback]: ${message}`);
        if (confirm(message)) {
            onConfirm();
        } else {
            onCancel();
        }
    }
})(); // Ende IIFE
