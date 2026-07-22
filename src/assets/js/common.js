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
    const btnOpenReport = document.getElementById('open-global-report'); // Footer Link
    const btnOpenReportComic = document.getElementById('open-report-modal'); // Link in der Comic-Navi
    const btnCloseReport = document.getElementById('close-report-modal');
    const reportForm = document.getElementById('global-report-form');
    const reportStatusMsg = document.getElementById('report-status-msg');

    function openReportWindow() {
        if (!reportModal) return;
        reportModal.style.display = 'flex';

        // Versuchen wir, die Comic-ID aus der Seite auszulesen (falls wir auf einer Comicseite sind)
        const comicImg = document.getElementById('comic-image');
        const idInput = document.getElementById('report_comic_id');
        if (comicImg && idInput) {
            idInput.value = comicImg.dataset.id || '';
        }

        // Debug-Infos mitgeben (Wo genau war der User?)
        const debugInput = document.getElementById('report_debug_info');
        if (debugInput) {
            debugInput.value = window.location.href;
        }
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

    // Modal schließen, wenn man daneben (ins Dunkle) klickt
    if (reportModal) {
        reportModal.addEventListener('click', (e) => {
            if (e.target === reportModal) reportModal.style.display = 'none';
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
                // Den baseUrl global holen, falls nicht vorhanden Fallback auf root
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
})();
