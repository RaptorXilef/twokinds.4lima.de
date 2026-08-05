export class FrontendApi {
    constructor() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        this.baseUrl = window.location.origin;
    }

    async post(endpoint, formData = new window.FormData()) {
        if (!formData.has('csrf_token')) {
            formData.append('csrf_token', this.csrfToken);
        }

        try {
            const response = await fetch(`${this.baseUrl}/api/${endpoint}`, {
                method: 'POST',
                body: formData,
                headers: {
                    Accept: 'application/json', // Zwingt den ExceptionHandler, JSON zu werfen!
                },
            });

            const isJson = response.headers.get('content-type')?.includes('application/json');
            if (isJson) {
                return await response.json();
            }

            // Fallback für PHP Fatal Errors
            const text = await response.text();
            console.error(
                `[FrontendApi] Server lieferte kein JSON. Antwort:`,
                text.substring(0, 250)
            );
            return {
                success: false,
                error: 'Ein interner Serverfehler ist aufgetreten. Bitte Entwickler-Konsole prüfen.',
            };
        } catch (error) {
            console.warn(`[FrontendApi] Netzwerkfehler bei POST /api/${endpoint}:`, error.message);
            return { success: false, error: 'Netzwerkfehler.' };
        }
    }

    async get(endpoint, params = '') {
        const query = params ? `?${params.toString()}` : '';
        try {
            const response = await fetch(`${this.baseUrl}/api/${endpoint}${query}`, {
                headers: {
                    Accept: 'application/json',
                },
            });

            const isJson = response.headers.get('content-type')?.includes('application/json');
            if (isJson) return await response.json();

            console.error(`[FrontendApi] GET /api/${endpoint} lieferte kein gültiges JSON.`);
            return { success: false, error: 'Ungültige Server-Antwort.' };
        } catch (error) {
            // "NetworkError" ignorieren, passiert oft bei Tab-Reloads während eines fetch
            if (
                error.name !== 'TypeError' &&
                error.message !== 'NetworkError when attempting to fetch resource.'
            ) {
                console.warn(
                    `[FrontendApi] Netzwerkfehler bei GET /api/${endpoint}:`,
                    error.message
                );
            }
            return { success: false, error: 'Netzwerkfehler.' };
        }
    }
}
