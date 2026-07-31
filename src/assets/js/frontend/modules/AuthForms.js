export class AuthForms {
    /** @param {import('../core/FrontendApi.js').FrontendApi} api */
    constructor(api) {
        this.api = api;
        this.init();
    }

    init() {
        // Wir prüfen einfach für jedes bekannte Formular, ob es auf der aktuellen Seite existiert
        this.bindForm(
            'frontend-login-form',
            'login-status-msg',
            'admin_login',
            '<i class="fa-solid fa-spinner fa-spin"></i> Prüfe...',
            'Einloggen',
            500
        );
        this.bindForm(
            'frontend-reg-form',
            'reg-status-msg',
            'frontend_register',
            '<i class="fa-solid fa-spinner fa-spin"></i> Erstelle Konto...',
            'Registrieren',
            30000
        );
        this.bindForm(
            'frontend-forgot-form',
            'forgot-status-msg',
            'frontend_forgot_password',
            '<i class="fa-solid fa-spinner fa-spin"></i> Sende...',
            'Link senden'
        );
        this.bindForm(
            'frontend-reset-form',
            'reset-status-msg',
            'frontend_reset_password',
            '<i class="fa-solid fa-spinner fa-spin"></i> Speichere...',
            'Passwort speichern',
            1000
        );
        this.bindForm(
            'frontend-resend-form',
            'resend-status-msg',
            'frontend_resend_verification',
            '<i class="fa-solid fa-spinner fa-spin"></i> Sende...',
            '<i class="fa-solid fa-paper-plane"></i> Mail senden'
        );
    }

    bindForm(formId, msgId, endpoint, loadingHtml, defaultHtml, redirectDelay = 0) {
        const form = document.getElementById(formId);
        const msg = document.getElementById(msgId);
        if (!form || !msg) return;

        const btn = form.querySelector('button[type="submit"]');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = loadingHtml;
            }

            const formData = new window.FormData(form);

            try {
                const json = await this.api.post(endpoint, formData);
                msg.style.display = 'block';

                if (json.success) {
                    msg.style.backgroundColor = 'var(--status-green-bg)';
                    msg.style.color = 'var(--status-green-text)';
                    msg.style.border = '1px solid var(--status-green-border)';

                    // Extra Nachricht für den 30-Sekunden-Wartebildschirm bei der Registrierung
                    let extraMsg = '';
                    if (formId === 'frontend-reg-form') {
                        extraMsg =
                            '<br><br><small><i>Du wirst in 30 Sekunden automatisch weitergeleitet...</i> Bitte schließe die Seite noch nicht!</small>';
                    }

                    msg.innerHTML = `<i class="fa-solid fa-check"></i> ${json.message}${extraMsg}`;

                    if (json.redirect) {
                        setTimeout(() => {
                            window.location.href = `${this.api.baseUrl}/${json.redirect}`;
                        }, redirectDelay);
                    } else {
                        // Wenn es keinen Redirect gibt (z.B. Passwort vergessen Mail gesendet), Formular leeren
                        form.reset();
                        if (btn) {
                            btn.disabled = false;
                            btn.innerHTML = defaultHtml;
                        }
                    }
                } else {
                    msg.style.backgroundColor = 'var(--status-red-bg)';
                    msg.style.color = 'var(--status-red-text)';
                    msg.style.border = '1px solid var(--status-red-border)';
                    msg.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i> ${json.error}`;
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = defaultHtml;
                    }
                }
            } catch (_err) {
                msg.style.display = 'block';
                msg.style.backgroundColor = 'var(--status-red-bg)';
                msg.style.color = 'var(--status-red-text)';
                msg.style.border = '1px solid var(--status-red-border)';
                msg.innerHTML = 'Verbindungsfehler. Server nicht erreichbar.';
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = defaultHtml;
                }
            }
        });
    }
}
