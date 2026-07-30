/**
 * @typedef {import('./UnsavedTracker.js').UnsavedTracker} UnsavedTracker
 */

export class DragDropService {
    /**
     * Bindet Drag & Drop Events an eine Zone und ein Input-Feld.
     * @param {string} zoneId HTML ID der Drop-Zone
     * @param {string} inputId HTML ID des File-Inputs
     * @param {Object} [options]
     * @param {UnsavedTracker} [options.tracker] Tracker für ungespeicherte Änderungen
     * @param {string} [options.previewTextId] HTML ID für den Dateinamen-Text
     * @param {Function} [options.onChange] Custom Callback `(files) => {...}` beim Drop oder Klick
     */
    static bind(zoneId, inputId, options = {}) {
        const zone = document.getElementById(zoneId);
        const input = document.getElementById(inputId);
        if (!zone || !input) return;

        const previewText = options.previewTextId
            ? document.getElementById(options.previewTextId)
            : null;
        const originalBg = zone.style.backgroundColor;

        const processFiles = (files) => {
            if (options.tracker) options.tracker.markDirty();
            zone.style.borderColor = 'var(--status-green-text)';
            zone.style.backgroundColor = 'var(--status-green-bg)';

            if (options.onChange) {
                options.onChange(files);
            } else if (previewText && files[0]) {
                previewText.textContent = `Bereit: ${files[0].name}`;
            }
        };

        zone.addEventListener('click', () => input.click());

        zone.addEventListener('dragover', (e) => {
            e.preventDefault();
            zone.style.borderColor = 'var(--link-color)';
            zone.style.backgroundColor = 'var(--table-row-hover)';
        });

        zone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            zone.style.borderColor = 'var(--border-medium)';
            zone.style.backgroundColor = originalBg;
        });

        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            if (e.dataTransfer.files.length) {
                if (!options.onChange) input.files = e.dataTransfer.files;
                processFiles(e.dataTransfer.files);
                if (!options.onChange) input.dispatchEvent(new Event('change'));
            } else {
                DragDropService.reset(zoneId, options.previewTextId);
            }
        });

        input.addEventListener('change', () => {
            if (input.files?.length) {
                processFiles(input.files);
            }
        });
    }

    /**
     * Setzt eine Drop-Zone optisch zurück.
     */
    static reset(zoneId, previewTextId = null) {
        const zone = document.getElementById(zoneId);
        const text = previewTextId ? document.getElementById(previewTextId) : null;
        if (zone) {
            zone.style.borderColor = 'var(--border-medium)';
            zone.style.backgroundColor = 'var(--table-row-even)';
        }
        if (text) text.textContent = '';
    }
}
