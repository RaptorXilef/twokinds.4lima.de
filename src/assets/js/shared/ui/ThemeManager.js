export class ThemeManager {
    constructor() {
        this.body = document.body;
        this.html = document.documentElement;
        this.toggleBtn = document.querySelector('#toggle_lights');
        this.desktopToggleBtn = document.querySelector('#toggle_desktop_mode');
        this.init();
    }

    init() {
        // Fallback entfernen
        document.querySelectorAll('.jsdep').forEach((el) => {
            el.classList.remove('jsdep');
        });

        // 1. Dark/Light Theme Logik
        let isDark = false;
        let pref = null;
        try {
            pref = localStorage.getItem('themePref');
        } catch (err) {
            console.warn('[ThemeManager] LocalStorage blockiert. Fallback auf System-Theme.', err);
        }

        if (pref === '2') {
            isDark = true;
        } else if (pref === '1') {
            isDark = false;
        } else {
            isDark = window.matchMedia?.('(prefers-color-scheme: dark)')?.matches ?? false;
        }
        this.applyTheme(isDark, false);

        if (this.toggleBtn) {
            this.toggleBtn.style.flexFlow = 'row nowrap';
            this.toggleBtn.style.alignItems = 'center';
            this.toggleBtn.style.justifyContent = 'center';
            this.toggleBtn.style.gap = '8px';
            this.toggleBtn.style.padding = '10px 0';
            this.toggleBtn.addEventListener('click', (e) => {
                e.preventDefault();
                isDark = !this.body.classList.contains('theme-night');
                try {
                    localStorage.setItem('themePref', isDark ? '2' : '1');
                    // biome-ignore lint/suspicious/noDocumentCookie: CookieStore API lacks full cross-browser support (Firefox/Safari)
                    document.cookie = `themePref=${isDark ? '2' : '1'}; max-age=31536000; path=/; SameSite=Lax`;
                } catch (err) {
                    console.error('[ThemeManager] Konnte Theme-Einstellung nicht speichern:', err);
                }
                this.applyTheme(isDark, true);
            });
        }

        // 2. Mobile/Desktop Toggle Logik
        let viewMode = localStorage.getItem('themeViewMode') || 'auto';
        localStorage.removeItem('forceDesktop'); // Alte Cache-Leiche aufräumen

        const applyViewMode = (mode) => {
            this.html.classList.remove('force-desktop', 'force-mobile');
            const vp = document.querySelector('meta[name="viewport"]');

            if (mode === 'desktop') {
                this.html.classList.add('force-desktop');
                if (vp) vp.setAttribute('content', 'width=1079'); // Zwingt Handys zum Herauszoomen
            } else if (mode === 'mobile') {
                this.html.classList.add('force-mobile');
                if (vp) vp.setAttribute('content', 'width=device-width, initial-scale=1.0');
            } else {
                if (vp) vp.setAttribute('content', 'width=device-width, initial-scale=1.0');
            }
        };

        // Direkt aufrufen (FOUC im Header passiert bereits parallel)
        applyViewMode(viewMode);

        if (this.desktopToggleBtn) {
            this.desktopToggleBtn.style.flexFlow = 'row nowrap';
            this.desktopToggleBtn.style.alignItems = 'center';
            this.desktopToggleBtn.style.justifyContent = 'center';
            this.desktopToggleBtn.style.gap = '8px';
            this.desktopToggleBtn.style.padding = '10px 0';

            const updateDesktopButton = () => {
                let isCurrentlyMobile = false;
                if (this.html.classList.contains('force-mobile')) isCurrentlyMobile = true;
                else if (this.html.classList.contains('force-desktop')) isCurrentlyMobile = false;
                else isCurrentlyMobile = window.matchMedia('(max-width: 860px)').matches;

                // Button bietet immer das GEGENTEIL der aktuellen Ansicht an
                if (isCurrentlyMobile) {
                    this.desktopToggleBtn.innerHTML =
                        '<i class="fa-solid fa-desktop" style="font-size: 1.2em;"></i> <span class="themename" style="padding:0;">Desktop-Ansicht</span>';
                } else {
                    this.desktopToggleBtn.innerHTML =
                        '<i class="fa-solid fa-mobile-screen" style="font-size: 1.2em;"></i> <span class="themename" style="padding:0;">Mobile-Ansicht</span>';
                }
            };

            this.desktopToggleBtn.addEventListener('click', (e) => {
                e.preventDefault();
                let isCurrentlyMobile = false;

                if (this.html.classList.contains('force-mobile')) isCurrentlyMobile = true;
                else if (this.html.classList.contains('force-desktop')) isCurrentlyMobile = false;
                else isCurrentlyMobile = window.matchMedia('(max-width: 860px)').matches;

                // Toggle logic
                viewMode = isCurrentlyMobile ? 'desktop' : 'mobile';

                localStorage.setItem('themeViewMode', viewMode);
                applyViewMode(viewMode);
                updateDesktopButton();
            });

            updateDesktopButton();

            // Re-render button if window resizes and mode is auto
            window.matchMedia('(max-width: 860px)').addEventListener('change', () => {
                if (viewMode === 'auto') updateDesktopButton();
            });
        }
    }

    applyTheme(isDark, doTransition) {
        if (doTransition) this.body.classList.add('transitioning');
        if (isDark) {
            this.body.classList.add('theme-night');
            if (this.toggleBtn)
                this.toggleBtn.innerHTML =
                    '<i class="fa-solid fa-sun" style="font-size: 1.2em;"></i> <span class="themename" style="padding:0;">Light Mode</span>';
        } else {
            this.body.classList.remove('theme-night');
            if (this.toggleBtn)
                this.toggleBtn.innerHTML =
                    '<i class="fa-solid fa-moon" style="font-size: 1.2em;"></i> <span class="themename" style="padding:0;">Dark Mode</span>';
        }
        if (doTransition) {
            window.setTimeout(() => {
                this.body.classList.remove('transitioning', 'preload');
            }, 300);
        } else {
            this.body.classList.remove('preload');
        }
    }
}
