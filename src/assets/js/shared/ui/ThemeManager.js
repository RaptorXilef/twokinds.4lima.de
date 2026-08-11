export class ThemeManager {
    constructor() {
        this.body = document.body;
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
                    document.cookie = `themePref=${isDark ? '2' : '1'}; max-age=31536000; path=/; SameSite=Lax`;
                } catch (err) {
                    console.error('[ThemeManager] Konnte Theme-Einstellung nicht speichern:', err);
                }
                this.applyTheme(isDark, true);
            });
        }

        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                let currentPref = null;
                try {
                    currentPref = localStorage.getItem('themePref');
                } catch (_err) {}
                if (!currentPref) {
                    this.applyTheme(e.matches, true);
                }
            });
        }

        // 2. Mobile/Desktop Toggle Logik
        let forceDesktop = false;
        try {
            forceDesktop = localStorage.getItem('forceDesktop') === 'true';
        } catch (_err) {}

        if (forceDesktop) this.body.classList.add('force-desktop');

        if (this.desktopToggleBtn) {
            this.desktopToggleBtn.style.flexFlow = 'row nowrap';
            this.desktopToggleBtn.style.alignItems = 'center';
            this.desktopToggleBtn.style.justifyContent = 'center';
            this.desktopToggleBtn.style.gap = '8px';
            this.desktopToggleBtn.style.padding = '10px 0';

            this.desktopToggleBtn.addEventListener('click', (e) => {
                e.preventDefault();
                forceDesktop = !this.body.classList.contains('force-desktop');
                if (forceDesktop) {
                    this.body.classList.add('force-desktop');
                    localStorage.setItem('forceDesktop', 'true');
                } else {
                    this.body.classList.remove('force-desktop');
                    localStorage.setItem('forceDesktop', 'false');
                }
                this.updateDesktopButtonUI(forceDesktop);
            });
            this.updateDesktopButtonUI(forceDesktop);
        }
    }

    applyTheme(isDark, doTransition) {
        if (doTransition) this.body.classList.add('transitioning');
        if (isDark) {
            this.body.classList.add('theme-night');
            this.updateButtonUI(true);
        } else {
            this.body.classList.remove('theme-night');
            this.updateButtonUI(false);
        }
        if (doTransition) {
            window.setTimeout(() => {
                this.body.classList.remove('transitioning');
                this.body.classList.remove('preload');
            }, 300);
        } else {
            this.body.classList.remove('preload');
        }
    }

    updateButtonUI(isDark) {
        if (!this.toggleBtn) return;
        if (isDark) {
            this.toggleBtn.innerHTML =
                '<i class="fa-solid fa-sun" style="font-size: 1.2em;"></i> <span class="themename" style="padding:0;">Light Mode</span>';
        } else {
            this.toggleBtn.innerHTML =
                '<i class="fa-solid fa-moon" style="font-size: 1.2em;"></i> <span class="themename" style="padding:0;">Dark Mode</span>';
        }
    }

    updateDesktopButtonUI(forceDesktop) {
        if (!this.desktopToggleBtn) return;
        if (forceDesktop) {
            this.desktopToggleBtn.innerHTML =
                '<i class="fa-solid fa-mobile-screen" style="font-size: 1.2em;"></i> <span class="themename" style="padding:0;">Mobile-Ansicht</span>';
        } else {
            this.desktopToggleBtn.innerHTML =
                '<i class="fa-solid fa-desktop" style="font-size: 1.2em;"></i> <span class="themename" style="padding:0;">PC-Ansicht erzwingen</span>';
        }
    }
}
