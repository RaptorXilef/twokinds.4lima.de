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
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
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
