export class ThemeManager {
    constructor() {
        this.body = document.body;
        this.toggleBtn = document.querySelector('#toggle_lights');
        this.init();
    }

    init() {
        document.querySelectorAll('.jsdep').forEach((el) => el.classList.remove('jsdep'));

        // Initialen Status ermitteln ('2' = Dark, alles andere = Light)
        let isDark = false;
        let pref = null;

        // Storage-Zugriff absichern!
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
            // Fallback: System-Einstellung des Betriebssystems
            isDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        }

        // Initiale UI setzen (ohne weichen Übergang)
        this.applyTheme(isDark, false);

        // Klick-Event für den Button
        if (this.toggleBtn) {
            // Wir überschreiben das alte CSS (flex-column) für eine schöne horizontale Icon-Darstellung
            this.toggleBtn.style.flexFlow = 'row nowrap';
            this.toggleBtn.style.alignItems = 'center';
            this.toggleBtn.style.justifyContent = 'center';
            this.toggleBtn.style.gap = '8px';
            this.toggleBtn.style.padding = '10px 0';

            this.toggleBtn.addEventListener('click', (e) => {
                e.preventDefault();
                // Zustand umkehren
                isDark = !this.body.classList.contains('theme-night');

                try {
                    // Speichern (1 = Light, 2 = Dark)
                    localStorage.setItem('themePref', isDark ? '2' : '1');

                    // MAGIE: Wir setzen zusätzlich das Cookie, damit PHP das ab sofort direkt lesen kann!
                    document.cookie = `themePref=${isDark ? '2' : '1'}; max-age=31536000; path=/; SameSite=Lax`;
                } catch (err) {
                    console.error('[ThemeManager] Konnte Theme-Einstellung nicht speichern:', err);
                }

                // Theme mit Animation anwenden
                this.applyTheme(isDark, true);
            });
        }

        // Auf System-Änderungen reagieren (nur wenn der Nutzer noch keine harte Wahl getroffen hat)
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                let currentPref = null;
                try {
                    currentPref = localStorage.getItem('themePref');
                } catch (err) {
                    // Ignorieren, da wir oben schon warnen
                }

                if (!currentPref) {
                    this.applyTheme(e.matches, true);
                }
            });
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

        // Wenn dunkel -> Zeige Sonne (Option für Hell). Wenn hell -> Zeige Mond (Option für Dunkel).
        if (isDark) {
            this.toggleBtn.innerHTML =
                '<i class="fa-solid fa-sun" style="font-size: 1.2em;"></i> <span class="themename" style="padding:0;">Light Mode</span>';
        } else {
            this.toggleBtn.innerHTML =
                '<i class="fa-solid fa-moon" style="font-size: 1.2em;"></i> <span class="themename" style="padding:0;">Dark Mode</span>';
        }
    }
}
