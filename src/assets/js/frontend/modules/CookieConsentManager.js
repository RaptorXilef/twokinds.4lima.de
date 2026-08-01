export class CookieConsentManager {
    constructor() {
        this.CATEGORIES = {
            NECESSARY: 'necessary',
            ANALYTICS: 'analytics',
        };
        this.STORAGE_KEY = 'cookie_consent';
        this.gaConfigured = false;

        this.banner = document.getElementById('cookie-consent-banner');

        // Wir initialisieren nur, wenn Google Analytics in den PHP-Settings aktiv ist
        // (dann wird auch das Banner-HTML vom Server gerendert)
        if (this.banner || window.GA_MEASUREMENT_ID) {
            this.init();
        }
    }

    init() {
        this.bindEvents();
        this.checkConsent();
    }

    bindEvents() {
        const acceptAllBtn = document.getElementById('accept-all-cookies');
        const rejectAllBtn = document.getElementById('reject-all-cookies');
        const savePreferencesBtn = document.getElementById('save-cookie-preferences');

        // Event-Delegation für Buttons, die eventuell nachträglich gerendert werden
        // (z.B. der Button auf der Datenschutzseite)
        document.addEventListener('click', (e) => {
            if (e.target.closest('#btn-open-cookie-banner')) {
                e.preventDefault();
                this.showBanner();
            }
        });

        if (acceptAllBtn) {
            acceptAllBtn.addEventListener('click', () => {
                this.setConsent({
                    [this.CATEGORIES.NECESSARY]: true,
                    [this.CATEGORIES.ANALYTICS]: true,
                });
                this.hideBanner();
            });
        }

        if (rejectAllBtn) {
            rejectAllBtn.addEventListener('click', () => {
                this.setConsent({
                    [this.CATEGORIES.NECESSARY]: true,
                    [this.CATEGORIES.ANALYTICS]: false,
                });
                this.hideBanner();
            });
        }

        if (savePreferencesBtn) {
            savePreferencesBtn.addEventListener('click', () => {
                const analyticsCheckbox = document.getElementById('cookieAnalytics');
                this.setConsent({
                    [this.CATEGORIES.NECESSARY]: true,
                    [this.CATEGORIES.ANALYTICS]: analyticsCheckbox
                        ? analyticsCheckbox.checked
                        : false,
                });
                this.hideBanner();
            });
        }

        document.querySelectorAll('.toggle-details').forEach((toggle) => {
            toggle.addEventListener('click', () => {
                const targetId = toggle.dataset.target;
                const content = document.getElementById(targetId);
                const icon = toggle.querySelector('.toggle-icon');

                if (content) {
                    if (content.style.display === 'block' || content.style.display === '') {
                        content.style.display = 'none';
                        if (icon) icon.classList.replace('fa-chevron-down', 'fa-chevron-right');
                    } else {
                        content.style.display = 'block';
                        if (icon) icon.classList.replace('fa-chevron-right', 'fa-chevron-down');
                    }
                }
            });
        });
    }

    checkConsent() {
        const consent = this.getConsent();
        if (consent === null) {
            this.showBanner();
        } else {
            if (consent[this.CATEGORIES.ANALYTICS]) {
                this.loadGoogleAnalytics();
            } else {
                this.disableGoogleAnalytics();
            }
        }
    }

    showBanner() {
        if (!this.banner) return;

        this.banner.style.display = 'block';
        const consent = this.getConsent();
        const analyticsCheckbox = document.getElementById('cookieAnalytics');

        if (analyticsCheckbox) {
            const necessaryCheckbox = document.getElementById('cookieNecessary');
            if (necessaryCheckbox) {
                necessaryCheckbox.checked = true;
                necessaryCheckbox.disabled = true;
            }
            analyticsCheckbox.checked = consent ? consent[this.CATEGORIES.ANALYTICS] : true;
        }
    }

    hideBanner() {
        if (this.banner) this.banner.style.display = 'none';
    }

    setConsent(preferences) {
        try {
            localStorage.setItem(this.STORAGE_KEY, JSON.stringify(preferences));
            document.cookie = `twokinds_cookie_consent=${JSON.stringify(preferences)}; max-age=31536000; path=/; SameSite=Lax`;

            if (preferences[this.CATEGORIES.ANALYTICS]) {
                this.loadGoogleAnalytics();
            } else {
                this.disableGoogleAnalytics();
            }
        } catch (err) {
            console.error('[CookieConsentManager] Fehler beim Speichern des Consents:', err);
        }
    }

    getConsent() {
        try {
            const stored = localStorage.getItem(this.STORAGE_KEY);
            return stored ? JSON.parse(stored) : null;
        } catch (err) {
            console.warn('[CookieConsentManager] Konnte LocalStorage nicht auslesen:', err);
            return null;
        }
    }

    loadGoogleAnalytics() {
        if (!window.GA_MEASUREMENT_ID) return;

        if (!window.dataLayer) window.dataLayer = [];
        if (typeof window.gtag !== 'function') {
            window.gtag = function () {
                window.dataLayer.push(arguments);
            };
        }

        let gaScript = document.querySelector(
            `script[src*="gtag/js?id=${window.GA_MEASUREMENT_ID}"]`
        );

        if (!gaScript) {
            gaScript = document.createElement('script');
            gaScript.async = true;
            gaScript.src = `https://www.googletagmanager.com/gtag/js?id=${window.GA_MEASUREMENT_ID}`;
            document.head.appendChild(gaScript);

            gaScript.onload = () => {
                if (!this.gaConfigured) {
                    window.gtag('js', new Date());
                    window.gtag('config', window.GA_MEASUREMENT_ID);
                    this.gaConfigured = true;
                }
            };
        } else if (!this.gaConfigured) {
            window.gtag('js', new Date());
            window.gtag('config', window.GA_MEASUREMENT_ID);
            this.gaConfigured = true;
        }
    }

    disableGoogleAnalytics() {
        if (!window.GA_MEASUREMENT_ID) return;

        if (this.gaConfigured) {
            if (typeof window.gtag === 'function') {
                window.gtag('config', window.GA_MEASUREMENT_ID, { send_page_view: false });
                window.gtag('set', 'anonymize_ip', true);
            }
            this.gaConfigured = false;
        }

        const gaScript = document.querySelector(
            `script[src*="gtag/js?id=${window.GA_MEASUREMENT_ID}"]`
        );
        if (gaScript) gaScript.remove();
    }
}
