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
            });

            const isJson = response.headers.get('content-type')?.includes('application/json');
            if (isJson) {
                return await response.json();
            }
            return { success: false, error: 'Ungültige Server-Antwort.' };
        } catch (error) {
            return { success: false, error: 'Netzwerkfehler.' };
        }
    }

    async get(endpoint, params = '') {
        const query = params ? `?${params.toString()}` : '';
        try {
            const response = await fetch(`${this.baseUrl}/api/${endpoint}${query}`);
            const isJson = response.headers.get('content-type')?.includes('application/json');
            if (isJson) return await response.json();
            return { success: false, error: 'Ungültige Server-Antwort.' };
        } catch (error) {
            return { success: false, error: 'Netzwerkfehler.' };
        }
    }
}
