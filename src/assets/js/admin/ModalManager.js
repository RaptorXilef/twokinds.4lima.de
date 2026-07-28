export class ModalManager {
    constructor() {
        this.bindGlobalCloseEvents();
    }

    /**
     * Öffnet ein Modal anhand seiner ID
     * @param {string} modalId
     */
    open(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'flex';
        } else {
            console.error(`[ModalManager] Modal mit ID '${modalId}' wurde nicht gefunden.`);
        }
    }

    /**
     * Schließt ein Modal anhand seiner ID
     * @param {string} modalId
     */
    close(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
        }
    }

    /**
     * Schließt alle offenen Modals
     */
    closeAll() {
        document.querySelectorAll('.modal').forEach((modal) => {
            modal.style.display = 'none';
        });
    }

    /**
     * Bindet automatisch alle Elemente mit .modal-close oder .modal-overlay
     * an die Schließen-Logik.
     */
    bindGlobalCloseEvents() {
        document.querySelectorAll('.modal-close, .modal-overlay').forEach((element) => {
            element.addEventListener('click', (e) => {
                e.preventDefault();
                const modal = e.target.closest('.modal');
                if (modal) {
                    modal.style.display = 'none';
                }
            });
        });

        // Schließen mit der ESC-Taste
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeAll();
            }
        });
    }
}
