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
        this.handleScrollHighlight();
    }

    /**
     * Schließt alle offenen Modals
     */
    closeAll() {
        document.querySelectorAll('.modal').forEach((modal) => {
            modal.style.display = 'none';
        });
        this.handleScrollHighlight();
    }

    // FIX: Row-Highlight & Scroll-To Logik nach Klick auf "Abbrechen"
    handleScrollHighlight() {
        const cancelId = sessionStorage.getItem('highlightEntityIdCancel');
        if (cancelId) {
            const targetBtn =
                document.querySelector(`.btn-delete-comic[data-id="${cancelId}"]`) ??
                document.querySelector(`.btn-delete-char[data-id="${cancelId}"]`);
            const tr = targetBtn?.closest('tr');
            if (tr) {
                tr.scrollIntoView({ behavior: 'smooth', block: 'center' });
                tr.classList.add('row-highlight');
                setTimeout(() => {
                    tr.classList.remove('row-highlight');
                }, 3000);
            }
            sessionStorage.removeItem('highlightEntityIdCancel');
        }
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
                    this.close(modal.id);
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
