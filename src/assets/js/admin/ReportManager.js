export class ReportManager {
    constructor(api, modalManager) {
        this.api = api;
        this.modalManager = modalManager;
        this.section = document.getElementById('section-reports');
        this.currentReportId = null;

        if (this.section) {
            this.bindEvents();
        }
    }

    bindEvents() {
        // 1. Event Delegation für die Tabelle (Berichte ansehen)
        this.section.addEventListener('click', (e) => {
            const btnView = e.target.closest('.btn-view-report');
            if (btnView) {
                e.preventDefault();
                this.openReportModal(JSON.parse(btnView.dataset.payload));
            }
        });

        // 2. Buttons im Modal (Erledigt & Spam)
        const btnResolve = document.getElementById('btn-rep-resolve');
        if (btnResolve) {
            btnResolve.addEventListener('click', () => this.resolveReport(btnResolve));
        }

        const btnSpam = document.getElementById('btn-rep-spam');
        if (btnSpam) {
            btnSpam.addEventListener('click', () => this.markAsSpam(btnSpam));
        }
    }

    openReportModal(payload) {
        this.currentReportId = payload.id;

        // Metadaten ins Modal schreiben
        const typeLabels = {
            typo: 'Tippfehler / Text',
            image: 'Bildfehler',
            technical: 'Technischer Fehler',
            other: 'Sonstiges',
        };

        const typeSpan = document.getElementById('rep-type');
        if (typeSpan) typeSpan.textContent = typeLabels[payload.type] || payload.type;

        const dateSpan = document.getElementById('rep-date');
        if (dateSpan) dateSpan.textContent = payload.createdAt || 'Unbekannt';

        const pageLink = document.getElementById('rep-page');
        if (pageLink) {
            pageLink.href = `/comic/${payload.comicId}`;
            pageLink.textContent = `Comic ${payload.comicId} ansehen`;
        }

        const desc = document.getElementById('rep-desc');
        if (desc) desc.textContent = payload.description || 'Keine Beschreibung angegeben.';

        this.modalManager.open('report-modal');
    }

    async resolveReport(btnElement) {
        if (
            !this.currentReportId ||
            !confirm(
                'Möchtest du diesen Bericht als "Erledigt" markieren? Er verschwindet dann aus dieser Liste.'
            )
        )
            return;

        const originalText = btnElement.innerHTML;
        btnElement.disabled = true;
        btnElement.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

        const formData = new window.FormData();
        formData.append('report_id', this.currentReportId);

        const result = await this.api.post('resolve_report', formData);

        if (result.success) {
            this.api.showStatus(result.message, 'success');
            window.isDirty = false;
            setTimeout(() => window.location.reload(), 1000);
        } else {
            this.api.showStatus(result.error, 'error');
            btnElement.disabled = false;
            btnElement.innerHTML = originalText;
        }
    }

    async markAsSpam(btnElement) {
        if (!this.currentReportId || !confirm('Möchtest du diesen Bericht als SPAM löschen?'))
            return;

        const originalText = btnElement.innerHTML;
        btnElement.disabled = true;
        btnElement.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

        const formData = new window.FormData();
        formData.append('report_id', this.currentReportId);

        const result = await this.api.post('delete_report', formData);

        if (result.success) {
            this.api.showStatus('Bericht gelöscht.', 'success');
            window.isDirty = false;
            setTimeout(() => window.location.reload(), 1000);
        } else {
            this.api.showStatus(result.error, 'error');
            btnElement.disabled = false;
            btnElement.innerHTML = originalText;
        }
    }
}
