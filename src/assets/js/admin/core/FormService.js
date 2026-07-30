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

    // Die Methoden für den Auto-Save:
    enableAutoSave(form, cacheKey) {
        if (!form) return;
        const saveToLocal = () => {
            const formData = new window.FormData(form);
            const data = {};

            // FIX: File-Objekte herausfiltern! Sie können nicht als JSON gespeichert
            // werden und führen beim Laden zu Abstürzen.
            for (const [key, value] of formData.entries()) {
                if (value instanceof File) continue;
                data[key] = value;
            }

            // Trumbowyg HTML Inhalt manuell abgreifen
            if (typeof window.$ !== 'undefined') {
                window
                    .$(form)
                    .find('.wysiwyg-editor')
                    .each(function () {
                        data[this.name] = window.$(this).trumbowyg('html');
                    });
            }
            localStorage.setItem(cacheKey, JSON.stringify(data));
        };

        form.addEventListener('input', saveToLocal);
        form.addEventListener('change', saveToLocal);

        if (typeof window.$ !== 'undefined') {
            window.$(form).find('.wysiwyg-editor').on('tbwchange', saveToLocal);
        }
    }

    hasDraft(cacheKey) {
        return localStorage.getItem(cacheKey) !== null;
    }

    clearDraft(cacheKey) {
        localStorage.removeItem(cacheKey);
    }

    restoreDraft(form, cacheKey) {
        const cached = localStorage.getItem(cacheKey);
        if (!cached || !form) return;

        try {
            const data = JSON.parse(cached);

            for (const key in data) {
                const input = form.querySelector(`[name="${key}"]`);
                if (!input) continue;

                // FIX: Niemals versuchen, File-Inputs per Code zu setzen!
                if (input.type === 'file') continue;

                if (
                    input.tagName === 'TEXTAREA' &&
                    input.classList.contains('wysiwyg-editor') &&
                    typeof window.$ !== 'undefined'
                ) {
                    // WYSIWYG Editor setzen
                    window.$(input).trumbowyg('html', data[key]);
                } else {
                    // Normale Inputs setzen
                    input.value = data[key];
                }

                // Trigger events für die reaktive Live-Vorschau
                input.dispatchEvent(new Event('input', { bubbles: true }));
            }
        } catch (e) {
            console.error('[FormService] Konnte Draft nicht wiederherstellen:', e);
        }
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
