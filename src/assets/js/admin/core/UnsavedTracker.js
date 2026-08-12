export class UnsavedTracker {
    constructor() {
        this.isDirty = false;

        // Warnung beim Verlassen der Seite
        window.addEventListener('beforeunload', (e) => {
            if (this.isDirty) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // Globales Tracking aller Formular-Eingaben
        document.addEventListener('input', (e) => {
            // Ignoriere Such- und Filterfelder, um falsche Warnungen zu vermeiden
            if (e.target.closest('.table-controls') || e.target.closest('.filter-form')) {
                return;
            }

            // Tracke normale Inputs, Textareas, Selects und contenteditable (z.B. Gruppen-Titel)
            if (
                e.target.tagName === 'INPUT' ||
                e.target.tagName === 'TEXTAREA' ||
                e.target.tagName === 'SELECT' ||
                e.target.isContentEditable
            ) {
                this.markDirty();
            }
        });
    }

    markDirty() {
        this.isDirty = true;
    }

    markClean() {
        this.isDirty = false;
    }
}
