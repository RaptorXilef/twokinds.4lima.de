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
            const data = Object.fromEntries(formData.entries());

            // Trumbowyg manuell abgreifen
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
                if (input) {
                    input.value = data[key];
                    if (
                        input.tagName === 'TEXTAREA' &&
                        input.classList.contains('wysiwyg-editor') &&
                        typeof window.$ !== 'undefined'
                    ) {
                        window.$(input).trumbowyg('html', data[key]);
                    }
                    // Trigger events for reactive preview bindings
                    input.dispatchEvent(new Event('input'));
                }
            }
        } catch (e) {
            console.error('Konnte Draft nicht wiederherstellen', e);
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
