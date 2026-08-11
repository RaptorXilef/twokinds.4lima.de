export class AuthManager {
    /** @param {import('../core/FrontendApi.js').FrontendApi} api */
    constructor(api) {
        this.api = api;
        this.bindEvents();
    }

    bindEvents() {
        const btnLogout = document.getElementById('btn-frontend-logout');
        if (btnLogout) {
            btnLogout.addEventListener('click', async (e) => {
                e.preventDefault();
                try {
                    // Wir feuern den Logout an die API...
                    await this.api.post('frontend_logout');
                } catch (err) {
                    // Falls der Server meckert (z.B. Session schon tot), ignorieren wir das einfach.
                    console.warn('[AuthManager] API-Fehler beim Logout ignoriert:', err);
                } finally {
                    // EGAL OB ERFOLG ODER FEHLER: Wir zwingen die Seite zum Neuladen!
                    window.location.reload();
                }
            });
        }
    }
}
