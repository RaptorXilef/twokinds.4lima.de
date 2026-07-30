export class NotificationService {
    constructor() {
        /** @type {HTMLElement|null} */
        this.container = document.getElementById('global-status-message');
    }

    /**
     * Steuert die globale Status-Meldung oben im Dashboard.
     * @param {string} message
     * @param {'success'|'error'|'info'} type
     */
    show(message, type = 'success') {
        if (!this.container) return;

        // Reset
        this.container.className = 'status-message visible';

        let icon = '';
        if (type === 'success') {
            this.container.classList.add('status-green');
            icon = 'fa-check';
        } else if (type === 'error') {
            this.container.classList.add('status-red');
            icon = 'fa-triangle-exclamation';
        } else {
            this.container.classList.add('status-info');
            icon = 'fa-info-circle';
        }

        this.container.innerHTML = `<i class="fa-solid ${icon}"></i> ${message}`;

        if (type === 'success') {
            setTimeout(() => {
                this.container.classList.remove('visible');
            }, 5000);
        }
    }
}
