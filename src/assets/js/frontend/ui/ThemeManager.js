export class ThemeManager {
    constructor() {
        this.themes = [
            { id: 0, name: 'Default', class: null },
            { id: 1, name: 'Lights On', class: null },
            { id: 2, name: 'Lights Off', class: 'theme-night' },
        ];
        this.systemThemeId = 0;
        this.systemLightThemeId = 1;
        this.systemDarkThemeId = 2;
        this.currentTheme = this.systemThemeId;

        this.body = document.body;
        this.init();
    }

    init() {
        document.querySelectorAll('.jsdep').forEach((el) => el.classList.remove('jsdep'));

        const toggleBtn = document.querySelector('#toggle_lights');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', (e) => {
                e.preventDefault();
                const themeToSelect = (this.currentTheme + 1) % this.themes.length;
                this.setTheme(themeToSelect, true, true);
            });
        }

        if (typeof window.localStorage !== 'undefined') {
            if (typeof window.localStorage.themePref === 'undefined') {
                this.setTheme(this.systemThemeId, false, false);
            } else {
                this.setTheme(parseInt(window.localStorage.themePref, 10), false, false);
            }
        } else {
            this.body.classList.remove('preload');
        }

        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                if (this.currentTheme === this.systemThemeId) {
                    this.setTheme(this.systemThemeId, false, true);
                }
            });
        }
    }

    setTheme(themeId, storePref, doTransition) {
        const theme = this.themes[themeId];
        const isSystemTheme = themeId === this.systemThemeId;

        this.body.classList.forEach((cls) => {
            if (cls.startsWith('theme-')) this.body.classList.remove(cls);
        });

        if (doTransition) this.body.classList.add('transitioning');

        if (isSystemTheme) {
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                this.applyThemeClass(this.themes[this.systemDarkThemeId]);
            } else {
                this.applyThemeClass(this.themes[this.systemLightThemeId]);
            }
        }

        if (theme.class !== null) {
            this.body.classList.add(theme.class);
        }

        const nameLabel = document.querySelector('#toggle_lights .themename');
        if (nameLabel) nameLabel.innerHTML = theme.name;

        if (storePref && typeof window.localStorage !== 'undefined') {
            if (!isSystemTheme) {
                window.localStorage.setItem('themePref', themeId);
            } else {
                window.localStorage.removeItem('themePref');
            }
        }

        this.currentTheme = themeId;

        if (doTransition) {
            window.setTimeout(() => {
                this.body.classList.remove('transitioning');
                this.body.classList.remove('preload');
            }, 300);
        }
    }

    applyThemeClass(theme) {
        if (theme.class !== null) this.body.classList.add(theme.class);
    }
}
