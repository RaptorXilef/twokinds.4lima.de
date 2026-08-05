import { DataTable } from '../ui/DataTable.js';

export class MailManager {
    constructor(api, modalManager, notifications, tracker) {
        this.api = api;
        this.modalManager = modalManager;
        this.notifications = notifications;
        this.tracker = tracker;
        this.section = document.getElementById('section-mails');

        if (this.section) {
            this.initTables();
            this.bindEvents();
        }
    }

    initTables() {
        // Tabelle: Warteschlange
        if (document.getElementById('mail-queue-table')) {
            new DataTable({
                tableBodySelector: '#mail-queue-table tbody',
                searchInputId: 'mail-queue-search',
                perPageSelectId: 'mail-queue-per-page',
                paginationContainerId: 'mail-queue-pagination',
            });
        }

        // Tabelle: Verlauf
        if (document.getElementById('mail-log-table')) {
            new DataTable({
                tableBodySelector: '#mail-log-table tbody',
                searchInputId: 'mail-log-search',
                perPageSelectId: 'mail-log-per-page',
                paginationContainerId: 'mail-log-pagination',
            });
        }
    }

    bindEvents() {
        this.section.addEventListener('click', (e) => {
            const btnPreview = e.target.closest('.btn-preview-mail');
            const btnSend = e.target.closest('.btn-send-mail');
            const btnResend = e.target.closest('.btn-resend-mail');

            if (btnPreview) {
                e.preventDefault();
                this.openPreview(btnPreview.dataset.id);
            }
            if (btnSend) {
                e.preventDefault();
                this.sendMailNow(btnSend.dataset.id, btnSend);
            }
            if (btnResend) {
                e.preventDefault();
                this.requeueMail(btnResend.dataset.id, btnResend);
            }
        });
    }

    async openPreview(id) {
        const iframe = document.getElementById('mail-preview-frame');
        if (iframe) {
            iframe.srcdoc =
                '<div style="font-family:sans-serif; padding: 20px; text-align:center;"><i class="fa-solid fa-spinner fa-spin fa-2x"></i><br><br>Lade Vorschau...</div>';
        }
        this.modalManager.open('mail-preview-modal');

        const res = await this.api.get('preview_mail', `id=${id}`);
        if (res.success && iframe) {
            iframe.srcdoc = res.html;
        } else {
            if (iframe) {
                iframe.srcdoc = `<div style="font-family:sans-serif; color:red; padding: 20px;">Fehler: ${res.error}</div>`;
            }
            this.notifications.show(res.error, 'error');
        }
    }

    async sendMailNow(id, btnElement) {
        if (!confirm('Möchtest du diese E-Mail jetzt sofort versenden?')) return;

        const origText = btnElement.innerHTML;
        btnElement.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
        btnElement.disabled = true;

        const fd = new FormData();
        fd.append('id', id);
        const res = await this.api.post('send_queued_mail', fd);

        if (res.success) {
            this.notifications.show(res.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            this.notifications.show(res.error, 'error');
            btnElement.innerHTML = origText;
            btnElement.disabled = false;
        }
    }

    async requeueMail(id, btnElement) {
        if (!confirm('Soll diese E-Mail erneut in die Warteschlange eingereiht werden?')) return;

        const origText = btnElement.innerHTML;
        btnElement.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
        btnElement.disabled = true;

        const fd = new FormData();
        fd.append('id', id);
        const res = await this.api.post('requeue_mail', fd);

        if (res.success) {
            this.notifications.show(res.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            this.notifications.show(res.error, 'error');
            btnElement.innerHTML = origText;
            btnElement.disabled = false;
        }
    }
}
