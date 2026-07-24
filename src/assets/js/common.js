/**
 * Dies is der JS Code zum umschalten zwischen den Themen Dark/Normal
 *
 * @file      ROOT/ressources/js/common.js / Minificed: ROOT/public/assets/js/common.min.js
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   1.1.0
 * @since     1.1.0 Der Button zum Umschalten des Webseiten-Themas (Light- / Dark-Mode) in der Seitenleiste wurde deaktiviert. Damit soll die Benutzererfahrung beim Umgang mit dem Fehler-Modal verbessert und ein ungewolltes umschalten verhindert werden.
 */

/**
 * Enables common features throughout the website
 */
(() => {
    var themes = [
        { id: 0, name: 'Default', class: null },
        { id: 1, name: 'Lights On', class: null },
        { id: 2, name: 'Lights Off', class: 'theme-night' },
    ];
    var systemThemeId = 0;
    var systemLightThemeId = 1;
    var systemDarkThemeId = 2;
    var currentTheme = systemThemeId;

    document.addEventListener('DOMContentLoaded', () => {
        var body = document.getElementsByTagName('body')[0];

        // Show elements that are hidden by default due to requiring JS.
        document.querySelectorAll('.jsdep').forEach((el) => el.classList.remove('jsdep'));

        document.querySelector('#toggle_lights').addEventListener('click', toggleTheme);

        if (typeof window.localStorage != 'undefined') {
            if (typeof window.localStorage.themePref == 'undefined') {
                setTheme(systemThemeId, false, false);
            } else {
                setTheme(window.localStorage.themePref, false, false);
            }
        } else {
            body.classList.remove('preload');
        }

        // Theme also toggles on 'i' keypress, but only if not an admin page.
        // window.isAdminPage is set in header.php
        /*    if (typeof window.isAdminPage === "undefined" || !window.isAdminPage) {
      body.addEventListener("keyup", (e) => {
        if (e.which == 73) {
          toggleTheme(e);
        }
      });
    }*/

        // Watch the system theme change event and automatically change with it.
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                // Ignore the event if the system theme is not currently selected.
                if (currentTheme == systemThemeId) {
                    setTheme(systemThemeId, false, true);
                }
            });
        }
    });

    /**
     * Switches between light and dark theme.
     * @param {event} e The event that triggered the change.
     * @returns {undefined}
     */
    function toggleTheme(e) {
        if (typeof e !== 'undefined') {
            e.preventDefault();
        }

        var themeToSelect = (currentTheme + 1) % themes.length;
        setTheme(themeToSelect, true, true);
    }

    /**
     * Sets the display theme.
     * @param {number} themeId The id of the theme to set.
     * @param {bool} storePref Determines whether a preference is stored in the user's localstorage.
     * @param {bool} doTransition Determines whether to animate the theme transition.
     * @returns {undefined}
     */
    function setTheme(themeId, storePref, doTransition) {
        var body = document.getElementsByTagName('body')[0];
        var theme = themes[themeId];
        var isSystemTheme = themeId == systemThemeId;

        // Remove any lingering theme.
        body.classList.forEach((cls) => {
            if (cls.startsWith('theme-')) {
                body.classList.remove(cls);
            }
        });

        // Enable transition effects if specified.
        if (doTransition) {
            body.classList.add('transitioning');
        }

        // Perform theme selection logic if system theme is chosen.
        if (isSystemTheme) {
            setSystemTheme(doTransition);
        }

        // Apply a class if the theme specifies it.
        if (theme.class != null) {
            body.classList.add(theme.class);
        }

        // Update the toggle button.
        document.querySelector('#toggle_lights .themename').innerHTML = theme.name;

        // Store the theme selection in localstorage if enabled.
        if (storePref && typeof window.localStorage != 'undefined') {
            if (!isSystemTheme) {
                window.localStorage.setItem('themePref', themeId);
            } else {
                window.localStorage.removeItem('themePref');
            }
        }

        currentTheme = themeId;
        resetTransitionState();
    }

    /**
     * Sets theme to the system default provided by the browser.
     * @param {bool} doTransition Determines whether to animate the theme transition.
     * @returns {undefined}
     */
    function setSystemTheme(doTransition) {
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            setTheme(systemDarkThemeId, false, doTransition);
        } else {
            setTheme(systemLightThemeId, false, doTransition);
        }
    }

    /**
     * Disables transition flags.
     * @returns {undefined}
     */
    function resetTransitionState() {
        var body = document.getElementsByTagName('body')[0];
        window.setTimeout(() => {
            body.classList.remove('transitioning');
            body.classList.remove('preload');
        }, 300);
    }

    document.addEventListener('DOMContentLoaded', () => {
        // 1. Zero-Trust Image Fallback Logic
        // Ersetzt defekte Bilder durch ein graues Platzhalterbild, ohne CSP zu verletzen.
        document.querySelectorAll('img').forEach((img) => {
            img.addEventListener('error', function () {
                if (!this.dataset.fallbackApplied) {
                    this.dataset.fallbackApplied = 'true';
                    this.src =
                        'https://placehold.co/800x600/cccccc/333333?text=Bild+nicht+gefunden';
                }
            });
        });

        // 2. Language Toggle Bindung (Ersatz für das gelöschte onclick="")
        const langToggleBtn = document.getElementById('lang-toggle-btn');
        const comicImage = document.getElementById('comic-image');
        if (langToggleBtn && comicImage && typeof window.runOriginalProbingLogic === 'function') {
            langToggleBtn.addEventListener('click', function () {
                window.runOriginalProbingLogic(comicImage, this);
            });
        }
    });

    // --- GLOBAL REPORT MODAL LOGIC ---
    const reportModal = document.getElementById('global-report-modal');
    const btnOpenReport = document.getElementById('open-global-report');
    const btnOpenReportComic = document.getElementById('open-report-modal');
    const btnCloseReport = document.getElementById('close-report-modal');
    const reportForm = document.getElementById('global-report-form');
    const reportStatusMsg = document.getElementById('report-status-msg');

    // Editor Initialisierung für Public (ohne Bilder-Upload, nur Formatierung!)
    if (typeof $.fn.trumbowyg !== 'undefined') {
        $.trumbowyg.svgPath =
            'https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/ui/icons.svg';
        $('.public-wysiwyg').trumbowyg({
            lang: 'de',
            btns: [
                ['viewHTML'],
                ['undo', 'redo'],
                ['strong', 'em', 'del'],
                ['link'],
                ['removeformat'],
            ],
        });
    }

    function openReportWindow() {
        if (!reportModal) return;
        reportModal.style.display = 'flex';

        const comicImg = document.getElementById('comic-image');
        const idInput = document.getElementById('report_comic_id');
        const idSection = document.getElementById('comic-id-section');
        const optTranscript = document.getElementById('report_type_transcript');
        const debugInput = document.getElementById('report_debug_info');
        const urlDisplay = document.getElementById('report_page_url_display');
        const charDescRaw = document.getElementById('raw-char-description'); // NEU

        // --- 1. Allgemeine Telemetrie sammeln ---
        const telemetry = {
            url: window.location.href,
            system: {
                userAgent: navigator.userAgent,
                viewport: `${window.innerWidth}x${window.innerHeight}`,
                pixelRatio: window.devicePixelRatio || 1,
                theme: document.body.classList.contains('theme-night') ? 'dark' : 'light',
            },
            context: {},
        };

        if (urlDisplay) urlDisplay.value = window.location.href; // Für den Nutzer sichtbar

        // --- 2. Kontext-Intelligenz (Comic-Seite) ---
        if (comicImg && idInput) {
            idInput.value = comicImg.dataset.id || '';
            idSection.style.display = 'block';
            optTranscript.style.display = 'block';
            optTranscript.textContent = 'Tippfehler / Transkript-Fehler'; // NEU

            telemetry.context.comicImages = {
                displayed: comicImg.src,
                hiresLink:
                    comicImg.parentElement.tagName === 'A' ? comicImg.parentElement.href : null,
                originalName: comicImg.dataset.enOriginal || null,
            };

            const charElements = document.querySelectorAll('.character-item .character-name');
            if (charElements.length > 0) {
                telemetry.context.visibleCharacters = Array.from(charElements).map((el) =>
                    el.textContent.trim()
                );
            }
        } else if (charDescRaw) {
            // NEU: WIR SIND AUF EINER CHARAKTER-SEITE!
            idInput.value = '';
            idSection.style.display = 'none';
            optTranscript.style.display = 'block';
            optTranscript.textContent = 'Tippfehler / Biografie anpassen'; // Text ändern!

            const charName = document.querySelector('.page-header')?.textContent.trim();
            telemetry.context.characterName = charName;
        } else {
            idInput.value = '';
            idSection.style.display = 'none';
            optTranscript.style.display = 'none';
        }

        // --- 3. Telemetrie als formatiertes JSON im versteckten Feld speichern ---
        if (debugInput) debugInput.value = JSON.stringify(telemetry, null, 4); // Versteckt für den Admin
    }

    if (btnOpenReport)
        btnOpenReport.addEventListener('click', (e) => {
            e.preventDefault();
            openReportWindow();
        });
    if (btnOpenReportComic)
        btnOpenReportComic.addEventListener('click', (e) => {
            e.preventDefault();
            openReportWindow();
        });

    if (btnCloseReport) {
        btnCloseReport.addEventListener('click', () => {
            reportModal.style.display = 'none';
        });
    }
    if (reportModal) {
        reportModal.addEventListener('click', (e) => {
            if (e.target === reportModal) reportModal.style.display = 'none';
        });
    }

    // Transkript Logik
    const typeSelect = document.getElementById('report_type');
    const transcriptSection = document.getElementById('transcript-edit-section');
    const originalInput = document.getElementById('report_transcript_original');
    const comicIdInput = document.getElementById('report_comic_id');
    const descInput = document.getElementById('report_description');

    if (typeSelect && transcriptSection) {
        typeSelect.addEventListener('change', async (e) => {
            if (e.target.value === 'transcript') {
                descInput.required = false; // Beschreibung ist jetzt optional

                const charDescRaw = document.getElementById('raw-char-description');
                const comicImg = document.getElementById('comic-image');

                // FALL 1: COMIC SEITE (API Fetch)
                if (comicImg) {
                    const comicId = comicIdInput.value.trim();
                    if (comicId.length >= 8) {
                        reportStatusMsg.style.display = 'block';
                        reportStatusMsg.style.backgroundColor = 'var(--status-info-bg)';
                        reportStatusMsg.style.color = 'var(--status-info-text)';
                        reportStatusMsg.style.border = '1px solid var(--status-info-border)';
                        reportStatusMsg.innerHTML =
                            '<i class="fa-solid fa-spinner fa-spin"></i> Lade aktuelles Transkript...';

                        try {
                            const baseUrl = window.location.origin;
                            const res = await fetch(`${baseUrl}/api/get_transcript?id=${comicId}`);
                            const json = await res.json();

                            reportStatusMsg.style.display = 'none';
                            if (json.success) {
                                originalInput.value = json.transcript;
                                $('.public-wysiwyg').trumbowyg('html', json.transcript);
                                transcriptSection.style.display = 'block';
                                if (descInput)
                                    descInput.placeholder = 'Zusätzliche Anmerkungen (Optional)...';
                            } else {
                                alert(json.error);
                                e.target.value = '';
                            }
                        } catch {
                            reportStatusMsg.style.display = 'none';
                            alert('Fehler beim Laden des Transkripts.');
                            e.target.value = '';
                        }
                    } else {
                        alert('Systemfehler: Comic-ID fehlt.');
                        e.target.value = '';
                    }
                }
                // FALL 2: CHARAKTER SEITE (Lokales DOM nutzen)
                else if (charDescRaw) {
                    originalInput.value = charDescRaw.value;
                    $('.public-wysiwyg').trumbowyg('html', charDescRaw.value);
                    transcriptSection.style.display = 'block';
                    if (descInput) descInput.placeholder = 'Zusätzliche Anmerkungen (Optional)...';
                }
                // FALL 3: FEHLER
                else {
                    alert('Systemfehler: Keine Textquelle auf dieser Seite gefunden.');
                    e.target.value = '';
                }
            } else {
                descInput.required = true; // Beschreibung ist wieder Pflicht
                transcriptSection.style.display = 'none';
                if (descInput) descInput.placeholder = 'Beschreibe kurz, was nicht stimmt...';
            }
        });
    }

    if (reportForm) {
        reportForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = document.getElementById('report-submit-btn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sende...';

            const formData = new FormData(reportForm);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            formData.append('csrf_token', csrfToken);

            try {
                const baseUrl = window.location.origin;
                const res = await fetch(`${baseUrl}/api/submit_report`, {
                    method: 'POST',
                    body: formData,
                });

                const json = await res.json();
                reportStatusMsg.style.display = 'block';

                if (json.success) {
                    reportStatusMsg.style.backgroundColor = 'var(--status-green-bg)';
                    reportStatusMsg.style.color = 'var(--status-green-text)';
                    reportStatusMsg.style.border = '1px solid var(--status-green-border)';
                    reportStatusMsg.innerHTML = `<i class="fa-solid fa-check"></i> ${json.message}`;
                    reportForm.reset();
                    $('.public-wysiwyg').trumbowyg('empty');
                    transcriptSection.style.display = 'none';

                    setTimeout(() => {
                        reportModal.style.display = 'none';
                        reportStatusMsg.style.display = 'none';
                    }, 3000);
                } else {
                    reportStatusMsg.style.backgroundColor = 'var(--status-red-bg)';
                    reportStatusMsg.style.color = 'var(--status-red-text)';
                    reportStatusMsg.style.border = '1px solid var(--status-red-border)';
                    reportStatusMsg.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i> ${json.error}`;
                }
            } catch {
                reportStatusMsg.style.display = 'block';
                reportStatusMsg.style.backgroundColor = 'var(--status-red-bg)';
                reportStatusMsg.style.color = 'var(--status-red-text)';
                reportStatusMsg.innerHTML =
                    '<i class="fa-solid fa-bomb"></i> Fehler bei der Serververbindung.';
            }

            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Fehler melden';
        });
    }
    // --- GLOBAL REPORT MODAL LOGIC ENDE ---

    // --- CSP-Konforme Ersatz-Logik für entfernte HTML-Attribute ---

    // 1. Ersatz für onload="this.classList.add('loaded')" in der Charakter-Liste
    // Da Bilder dynamisch laden, nutzen wir das Event in der Capture-Phase (true)
    document.addEventListener(
        'load',
        (e) => {
            if (e.target && e.target.tagName === 'IMG' && e.target.closest('.character-item')) {
                e.target.classList.add('loaded');
            }
        },
        true
    );

    // 2. Ersatz für onerror auf der Comic-Seite und 404-Seite
    const mainComicImage = document.getElementById('comic-image');
    if (mainComicImage) {
        mainComicImage.addEventListener('error', function () {
            this.src = 'https://placehold.co/800x600/cccccc/333333?text=Bild+Fehler';
            if (this.parentElement.tagName === 'A') {
                this.parentElement.href = '#';
            }
        });
    }

    // --- CSP-Konforme Ersatz-Logik für entfernte HTML-Attribute ENDE ---
})();
