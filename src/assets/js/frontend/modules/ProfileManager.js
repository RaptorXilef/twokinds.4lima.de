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
        this.bindForm('form-email', 'msg-email', false, true); // <--- NEU
    }

    bindForm(formId, msgId, reloadOnSuccess = false, resetOnSuccess = false) {
        const form = document.getElementById(formId);
        const msg = document.getElementById(msgId);
        if (!form || !msg) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = form.querySelector('button[type="submit"]');
            const origText = btn ? btn.innerHTML : '';

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Lade...';
            }

            const formData = new window.FormData(form);

            try {
                const json = await this.api.post('frontend_update_profile', formData);
                msg.style.display = 'block';

                if (json.success) {
                    msg.style.backgroundColor = 'var(--status-green-bg)';
                    msg.style.color = 'var(--status-green-text)';
                    msg.style.border = '1px solid var(--status-green-border)';
                    msg.innerHTML = `<i class="fa-solid fa-check"></i> ${json.message}`;

                    if (resetOnSuccess) form.reset();
                    if (reloadOnSuccess) {
                        setTimeout(() => window.location.reload(), 1500);
                    }
                } else {
                    msg.style.backgroundColor = 'var(--status-red-bg)';
                    msg.style.color = 'var(--status-red-text)';
                    msg.style.border = '1px solid var(--status-red-border)';
                    msg.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i> ${json.error}`;
                }
            } catch (err) {
                console.error('[ProfileManager] Unerwarteter Fehler beim Profil-Update:', err);
                msg.style.display = 'block';
                msg.style.backgroundColor = 'var(--status-red-bg)';
                msg.style.color = 'var(--status-red-text)';
                msg.innerHTML = 'Verbindungsfehler. Server nicht erreichbar.';
            }

            if (btn) {
                btn.disabled = false;
                btn.innerHTML = origText;
            }
        });
    }
}
