export class ProfileManager {
    /** @param {import('../core/FrontendApi.js').FrontendApi} api */
    constructor(api) {
        this.api = api;

        // Wenn das Profil-Formular nicht existiert, direkt abbrechen
        if (!document.getElementById('form-username')) return;

        this.init();
    }

    init() {
        this.bindForm('form-username', 'msg-username', true, false);
        this.bindForm('form-password', 'msg-password', false, true);
        this.bindForm('form-newsletter', 'msg-newsletter', false, false);
        this.bindForm('form-email', 'msg-email', false, true);
        this.bindForm('form-delete-account', 'msg-delete-account', false, false, true); // True = ist Lösch-Formular
    }

    bindForm(formId, msgId, reloadOnSuccess = false, resetOnSuccess = false, isDelete = false) {
        const form = document.getElementById(formId);
        const msg = document.getElementById(msgId);
        if (!form || !msg) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (isDelete) {
                if (
                    !confirm(
                        'ACHTUNG: Möchtest du dein Konto und alle deine Lesezeichen WIRKLICH unwiderruflich löschen?'
                    )
                ) {
                    return;
                }
            }

            const btn = form.querySelector('button[type="submit"]');
            const origText = btn ? btn.innerHTML : '';

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verarbeite...';
            }

            const formData = new window.FormData(form);

            // FIX: Die Variable muss VOR dem try-Block existieren,
            // damit sie unten beim Button-Reset noch bekannt ist!
            let responseJson = null;

            try {
                // Bei Delete greifen wir auf die neue API-Route zu
                const endpoint = isDelete ? 'frontend_delete_account' : 'frontend_update_profile';
                responseJson = await this.api.post(endpoint, formData);

                msg.style.display = 'block';

                if (responseJson.success) {
                    msg.style.backgroundColor = 'var(--status-green-bg)';
                    msg.style.color = 'var(--status-green-text)';
                    msg.style.border = '1px solid var(--status-green-border)';
                    msg.innerHTML = `<i class="fa-solid fa-check"></i> ${responseJson.message}`;

                    if (resetOnSuccess) form.reset();

                    if (isDelete && responseJson.redirect !== undefined) {
                        // Bei Kontolöschung sofort auf die Startseite zurückwerfen
                        setTimeout(
                            () =>
                                (window.location.href =
                                    this.api.baseUrl + '/' + responseJson.redirect),
                            1500
                        );
                    } else if (reloadOnSuccess) {
                        setTimeout(() => window.location.reload(), 1500);
                    }
                } else {
                    msg.style.backgroundColor = 'var(--status-red-bg)';
                    msg.style.color = 'var(--status-red-text)';
                    msg.style.border = '1px solid var(--status-red-border)';
                    msg.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i> ${responseJson.error}`;
                }
            } catch (err) {
                console.error('[ProfileManager] Unerwarteter Fehler beim API-Call:', err);
                msg.style.display = 'block';
                msg.style.backgroundColor = 'var(--status-red-bg)';
                msg.style.color = 'var(--status-red-text)';
                msg.innerHTML = 'Verbindungsfehler. Server nicht erreichbar.';
            }

            // Button nur bei Fehlschlag wieder aktivieren
            if (btn && (!responseJson || !responseJson.success)) {
                btn.disabled = false;
                btn.innerHTML = origText;
            }
        });
    }
}
