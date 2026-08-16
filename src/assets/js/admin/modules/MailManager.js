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
                paginationContainerSelector: '.mail-queue-pagination',
            });
        }

        // Tabelle: Verlauf
        if (document.getElementById('mail-log-table')) {
            new DataTable({
                tableBodySelector: '#mail-log-table tbody',
                searchInputId: 'mail-log-search',
                perPageSelectId: 'mail-log-per-page',
                paginationContainerSelector: '.mail-log-pagination',
            });
        }
    }

    bindEvents() {
        this.section.addEventListener('click', (e) => {
            const tabBtn = e.target.closest('.media-tab-btn');

            if (tabBtn) {
                e.preventDefault();

                // 1. Alle Tab-Buttons zurücksetzen
                this.section.querySelectorAll('.media-tab-btn').forEach((b) => {
                    b.classList.remove('active');
                    b.classList.add('edit');
                });

                // 2. Geklickten Button aktivieren
                tabBtn.classList.remove('edit');
                tabBtn.classList.add('active');

                // 3. Alle Ansichten (Tabellen) ausblenden
                this.section.querySelectorAll('.media-view').forEach((v) => {
                    v.classList.add('hidden');
                });

                // 4. Ziel-Ansicht einblenden
                const targetView = document.getElementById(`media-view-${tabBtn.dataset.type}`);
                if (targetView) {
                    targetView.classList.remove('hidden');
                }
                return; // Wichtig, damit die restliche Klick-Logik übersprungen wird
            }

            // E-Mail Aktionen (Vorschau, Senden, Erneut Senden)
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

        // Globaler Klick-Listener zum Schließen des E-Mail Vorschau Modals
        document.addEventListener('click', (e) => {
            const btnClose = e.target.closest('.btn-close-mail-modal');
            if (btnClose) {
                e.preventDefault();
                this.modalManager.close('mail-preview-modal');
            }
        });
    }

    async openPreview(id) {
        const iframe = document.getElementById('mail-preview-frame');

        // Hilfsfunktion zum sicheren Befüllen des Iframes ohne srcdoc
        const setIframeContent = (html) => {
            if (!iframe) return;
            const doc = iframe.contentWindow.document;
            doc.open();
            doc.write(html);
            doc.close();
        };

        // Fix: Quirks-Modus verhindern durch sauberes HTML-Grundgerüst
        setIframeContent(
            '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body style="font-family:sans-serif; padding: 20px; text-align:center;"><i class="fa-solid fa-spinner fa-spin fa-2x"></i><br><br>Lade Vorschau...</body></html>'
        );

        this.modalManager.open('mail-preview-modal');

        const res = await this.api.get('preview_mail', `id=${id}`);
        if (res.success) {
            setIframeContent(res.html);
        } else {
            setIframeContent(
                `<!DOCTYPE html><html><head><meta charset="utf-8"></head><body style="font-family:sans-serif; color:red; padding: 20px;">Fehler: ${res.error}</body></html>`
            );
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
