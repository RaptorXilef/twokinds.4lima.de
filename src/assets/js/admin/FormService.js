/**
 * @typedef {import('./Api.js').Api} Api
 * @typedef {import('./NotificationService.js').NotificationService} NotificationService
 * @typedef {import('./UnsavedTracker.js').UnsavedTracker} UnsavedTracker
 */

export class FormService {
    /**
     * @param {Api} api
     * @param {NotificationService} notifications
     * @param {UnsavedTracker} [tracker]
     */
    constructor(api, notifications, tracker = null) {
        this.api = api;
        this.notifications = notifications;
        this.tracker = tracker;
    }

    /**
     * Übernimmt die komplette Logik eines Form-Submits.
     * @param {HTMLFormElement} form Das abzusendende Formular
     * @param {HTMLElement} btnElement Der Submit-Button (für den Lade-Zustand)
     * @param {string} endpoint Der API Endpunkt (z.B. 'save_chapter')
     * @param {Object} [customData] Zusätzliche Key-Value Paare, die ins FormData sollen
     * @returns {Promise<boolean>} True bei Erfolg, False bei Validierungs- oder Server-Fehler
     */
    async submit(form, btnElement, endpoint, customData = {}) {
        // 1. HTML5 Validierung (required, pattern, etc.)
        if (!form.reportValidity()) return false;

        // 2. Button State: Laden
        const originalText = btnElement.innerHTML;
        btnElement.disabled = true;
        btnElement.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Speichere...';

        try {
            // 3. Daten sammeln
            const formData = new window.FormData(form);

            // Extra-Daten anhängen (z.B. Trumbowyg HTML)
            for (const [key, value] of Object.entries(customData)) {
                formData.set(key, value);
            }

            // 4. Absenden
            const result = await this.api.post(endpoint, formData);

            // 5. Auswerten
            if (result.success) {
                if (this.tracker) this.tracker.markClean();
                this.notifications.show(result.message || 'Erfolgreich gespeichert!', 'success');
                return true;
            } else {
                this.notifications.show(result.error || 'Ein Fehler ist aufgetreten.', 'error');
                return false;
            }
        } finally {
            // 6. Button State: Zurücksetzen (Egal ob Erfolg oder Fehler)
            btnElement.disabled = false;
            btnElement.innerHTML = originalText;
        }
    }
}
