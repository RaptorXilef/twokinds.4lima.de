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
                    const json = await this.api.post('frontend_logout');
                    if (json.success) {
                        window.location.href = this.api.baseUrl + '/';
                    } else {
                        console.warn('[AuthManager] Logout vom Server abgelehnt:', json.error);
                    }
                } catch (err) {
                    console.error('[AuthManager] Unerwarteter Fehler beim Logout:', err);
                }
            });
        }
    }
}
