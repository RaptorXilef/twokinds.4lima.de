export class Api {
    constructor() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        this.statusContainer = document.getElementById('global-status-message');
    }

    /**
     * Sendet einen POST-Request an die API. Hängt den CSRF-Token automatisch an.
     * @param {string} endpoint - z.B. 'save_single_comic'
     * @param {FormData} formData
     * @returns {Promise<Object>}
     */
    async post(endpoint, formData = new window.FormData()) {
        if (!formData.has('csrf_token')) {
            formData.append('csrf_token', this.csrfToken);
        }

        try {
            const response = await fetch(`/api/${endpoint}`, {
                method: 'POST',
                body: formData,
            });

            const text = await response.text();
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error(`[API Error] POST /api/${endpoint} lieferte ungültiges JSON:`, text);
                return {
                    success: false,
                    error: 'Serverfehler: Die Antwort konnte nicht verarbeitet werden.',
                };
            }
        } catch (error) {
            console.error(`[API Error] POST /api/${endpoint} ist fehlgeschlagen:`, error);
            return { success: false, error: 'Verbindungsfehler zum Server.' };
        }
    }

    /**
     * Sendet einen GET-Request an die API.
     * @param {string} endpoint
     * @param {URLSearchParams|string} params
     * @returns {Promise<Object>}
     */
    async get(endpoint, params = '') {
        const query = params ? `?${params.toString()}` : '';
        try {
            const response = await fetch(`/api/${endpoint}${query}`);
            const text = await response.text();
            try {
                return JSON.parse(text);
            } catch (e) {
                return { success: false, error: 'Serverfehler bei GET-Anfrage.' };
            }
        } catch (error) {
            console.error(`[API Error] GET /api/${endpoint} ist fehlgeschlagen:`, error);
            return { success: false, error: 'Verbindungsfehler zum Server.' };
        }
    }

    /**
     * Steuert die globale Status-Meldung oben im Dashboard.
     * @param {string} message
     * @param {string} type - 'success', 'error' oder 'info'
     */
    showStatus(message, type = 'success') {
        if (!this.statusContainer) return;

        // CSS-Klassen zurücksetzen
        this.statusContainer.className = 'status-message visible';

        let icon = '';
        if (type === 'success') {
            this.statusContainer.classList.add('status-green');
            icon = 'fa-check';
        } else if (type === 'error') {
            this.statusContainer.classList.add('status-red');
            icon = 'fa-triangle-exclamation';
        } else {
            this.statusContainer.classList.add('status-info');
            icon = 'fa-info-circle';
        }

        this.statusContainer.innerHTML = `<i class="fa-solid ${icon}"></i> ${message}`;

        // Erfolgsmeldungen nach 5 Sekunden automatisch ausblenden
        if (type === 'success') {
            setTimeout(() => {
                this.statusContainer.classList.remove('visible');
            }, 5000);
        }
    }
}
