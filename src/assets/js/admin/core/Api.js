export class Api {
    constructor() {
        /** @type {string} */
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        const baseUrlMatch = window.location.pathname.match(/^(.*)\/admin/);
        /** @type {string} */
        this.baseUrl = baseUrlMatch ? baseUrlMatch[1] : '';
    }

    /**
     * Zentraler privater Response-Parser (Verhindert JSON-Crashes bei 500er/404er HTML Seiten)
     * @param {Response} response
     * @returns {Promise<Object>}
     */
    async #handleResponse(response) {
        const isJson = response.headers.get('content-type')?.includes('application/json');

        if (!response.ok) {
            if (isJson) {
                const errData = await response.json();
                return { success: false, error: errData.error || `HTTP Fehler ${response.status}` };
            }
            return { success: false, error: `Server-Verbindungsfehler (HTTP ${response.status}).` };
        }

        if (isJson) {
            return await response.json();
        }

        return {
            success: false,
            error: 'Der Server hat ungültige Daten (Kein JSON) zurückgegeben.',
        };
    }

    /**
     * Sendet einen POST-Request an die API.
     * @param {string} endpoint - z.B. 'save_single_comic'
     * @param {FormData} formData
     * @returns {Promise<Object>}
     */
    async post(endpoint, formData = new window.FormData()) {
        if (!formData.has('csrf_token')) {
            formData.append('csrf_token', this.csrfToken);
        }

        try {
            const response = await fetch(`${this.baseUrl}/api/${endpoint}`, {
                method: 'POST',
                body: formData,
            });
            return await this.#handleResponse(response);
        } catch (error) {
            console.error(`[API Error] POST /api/${endpoint} failed:`, error);
            return { success: false, error: 'Netzwerkfehler. Server nicht erreichbar.' };
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
            const response = await fetch(`${this.baseUrl}/api/${endpoint}${query}`);
            return await this.#handleResponse(response);
        } catch (error) {
            console.error(`[API Error] GET /api/${endpoint} failed:`, error);
            return { success: false, error: 'Netzwerkfehler. Server nicht erreichbar.' };
        }
    }
}
