import { renderPagination } from '../ui/Pagination.js';

/**
 * @typedef {import('../core/Api.js').Api} Api
 * @typedef {import('../ui/ModalManager.js').ModalManager} ModalManager
 * @typedef {import('./ComicEditor.js').ComicEditor} ComicEditor
 * @typedef {import('../core/NotificationService.js').NotificationService} NotificationService
 */

export class ReportManager {
    /**
     * @param {Api} api
     * @param {ModalManager} modalManager
     * @param {ComicEditor} comicEditor
     * @param {NotificationService} notifications
     */
    constructor(api, modalManager, comicEditor, notifications, tracker) {
        this.api = api;
        this.modalManager = modalManager;
        this.comicEditor = comicEditor;
        this.notifications = notifications;
        this.tracker = tracker;

        /** @type {HTMLElement|null} */
        this.section = document.getElementById('section-reports');
        /** @type {Object|null} */
        this.currentReportPayload = null;

        if (this.section) {
            this.initTableLogic();
            this.bindEvents();
        }
    }

    // Filter & Paginierung für Reports, da DataTable generisch Comics macht
    initTableLogic() {
        this.searchInput = document.getElementById('report-search');
        this.perPageSelect = document.getElementById('report-per-page');
        this.statusSelect = document.getElementById('report-status-filter');
        this.tableBody = document.querySelector('#reports-table tbody');
        this.paginationContainer = document.getElementById('report-pagination');

        if (!this.tableBody || !this.paginationContainer) return;

        this.allRows = Array.from(this.tableBody.querySelectorAll('tr')).filter(
            (row) => !row.classList.contains('empty-table-message')
        );

        this.stateKey = 'admin_dt_state_reports';
        this.repPage = 1;
        this.repLimit = this.perPageSelect?.value || '15';
        this.repSearch = '';
        this.repStatus = this.statusSelect?.value || 'open';

        this.restoreTableState(); // Lade alten State!

        this.searchInput?.addEventListener('input', (e) => {
            this.repSearch = e.target.value;
            this.repPage = 1;
            this.renderTable();
        });

        this.perPageSelect?.addEventListener('change', (e) => {
            this.repLimit = e.target.value;
            this.repPage = 1;
            this.renderTable();
        });

        this.statusSelect?.addEventListener('change', (e) => {
            this.repStatus = e.target.value;
            this.repPage = 1;
            this.renderTable();
        });

        this.renderTable();
    }

    saveTableState() {
        try {
            sessionStorage.setItem(
                this.stateKey,
                JSON.stringify({
                    page: this.repPage,
                    limit: this.repLimit,
                    query: this.repSearch,
                    status: this.repStatus,
                })
            );
        } catch (err) {
            console.warn('[ReportManager] Konnte Filter-Status nicht speichern:', err);
        }
    }

    restoreTableState() {
        try {
            const s = JSON.parse(sessionStorage.getItem(this.stateKey));
            if (s) {
                this.repPage = s.page || 1;
                if (s.limit && this.perPageSelect) {
                    this.repLimit = s.limit;
                    this.perPageSelect.value = s.limit;
                }
                if (s.query !== undefined && this.searchInput) {
                    this.repSearch = s.query;
                    this.searchInput.value = s.query;
                }
                if (s.status && this.statusSelect) {
                    this.repStatus = s.status;
                    this.statusSelect.value = s.status;
                }
            }
        } catch (err) {
            console.warn('[ReportManager] Konnte Filter-Status nicht wiederherstellen:', err);
        }
    }

    renderTable() {
        const filteredRows = this.allRows.filter((row) => {
            const matchesSearch = row.textContent
                .toLowerCase()
                .includes(this.repSearch.toLowerCase());
            const matchesStatus = this.repStatus === 'all' || row.dataset.status === this.repStatus;
            return matchesSearch && matchesStatus;
        });

        const totalItems = filteredRows.length;
        const limit = this.repLimit === 'all' ? totalItems : parseInt(this.repLimit, 10);
        const totalPages = limit > 0 ? Math.ceil(totalItems / limit) : 1;

        if (this.repPage > totalPages) this.repPage = totalPages || 1;

        const startIndex = limit === totalItems ? 0 : (this.repPage - 1) * limit;
        const endIndex = startIndex + limit;

        this.allRows.forEach((row) => {
            row.style.display = 'none';
        });

        filteredRows.slice(startIndex, endIndex).forEach((row) => {
            row.style.display = '';
        });

        let emptyMsg = this.tableBody.querySelector('.dyn-empty-msg');
        if (filteredRows.length === 0) {
            if (!emptyMsg) {
                emptyMsg = document.createElement('tr');
                emptyMsg.className = 'dyn-empty-msg empty-table-message';
                emptyMsg.innerHTML =
                    '<td colspan="6">Keine Reports für diese Filter gefunden.</td>';
                this.tableBody.appendChild(emptyMsg);
            }
            emptyMsg.style.display = '';
        } else if (emptyMsg) {
            emptyMsg.style.display = 'none';
        }

        renderPagination(this.paginationContainer, this.repPage, totalPages, (newPage) => {
            this.repPage = newPage;
            this.renderTable();
        });

        this.saveTableState(); // Speichere den Zustand bei jedem Rendern!
    }

    bindEvents() {
        // 1. Event Delegation für die Tabelle (Berichte ansehen)
        this.section.addEventListener('click', (e) => {
            const btnView = e.target.closest('.btn-view-report');
            if (btnView) {
                e.preventDefault();
                try {
                    const payload = JSON.parse(btnView.dataset.payload);
                    this.openReportModal(payload);
                } catch (err) {
                    console.error('[ReportManager] Fehler beim Parsen der Report-Daten:', err);
                    this.notifications.show('Fehler beim Öffnen des Reports.', 'error');
                }
            }
        });

        // 2. Buttons im Modal (Erledigt & Spam)
        document.addEventListener('click', (e) => {
            const btnResolve = e.target.closest('#btn-rep-resolve');
            const btnSpam = e.target.closest('#btn-rep-spam');
            const btnToggleDebug = e.target.closest('#btn-toggle-debug-view');
            const btnTransfer = e.target.closest('#btn-transfer-transcript');
            const btnClose = e.target.closest('.btn-close-report-modal');

            // Close
            if (btnClose) {
                e.preventDefault();
                this.modalManager.close('report-detail-modal');
            }

            if (btnResolve && this.currentReportPayload) this.resolveReport(btnResolve);
            if (btnSpam && this.currentReportPayload) this.markAsSpam(btnSpam);

            // Toggle JSON Ansicht
            if (btnToggleDebug) {
                const debugRaw = document.getElementById('rep-modal-debug');
                const debugRendered = document.getElementById('rep-modal-debug-rendered');

                if (debugRaw.style.display === 'none') {
                    debugRaw.style.display = 'block';
                    debugRendered.style.display = 'none';
                    btnToggleDebug.innerHTML =
                        '<i class="fa-solid fa-list-tree"></i> Formatiert anzeigen';
                } else {
                    debugRaw.style.display = 'none';
                    debugRendered.style.display = 'block';
                    btnToggleDebug.innerHTML = '<i class="fa-solid fa-code"></i> Rohdaten anzeigen';
                }
            }

            // Das Killer-Feature: Transkript übernehmen
            if (btnTransfer && this.currentReportPayload && this.comicEditor) {
                if (!this.currentReportPayload.comicId) {
                    alert(
                        'Automatisches Übernehmen ist aktuell nur für Comics verfügbar. Bitte kopiere den Text manuell.'
                    );
                    return;
                }

                const btnOriginalText = btnTransfer.innerHTML;
                btnTransfer.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Lade Comic...';
                btnTransfer.disabled = true;

                this.api
                    .get('get_comic', `id=${this.currentReportPayload.comicId}`)
                    .then((json) => {
                        if (json.success) {
                            const comicData = json.comic;
                            // Das vorgeschlagene Transkript einfügen
                            comicData.transcript = this.currentReportPayload.suggestion;

                            // MAGIC BUTTON FUNKTION: Helfer automatisch anhängen!
                            if (this.currentReportPayload.userId) {
                                if (!comicData.helpers) comicData.helpers = [];
                                if (!comicData.helpers.includes(this.currentReportPayload.userId)) {
                                    comicData.helpers.push(this.currentReportPayload.userId);
                                }
                            }

                            this.modalManager.close('report-detail-modal');
                            this.comicEditor.openEditModal(comicData);
                            this.notifications.show(
                                'Transkript-Vorschlag geladen. Melder wurde als Helfer ausgewählt!',
                                'success'
                            );
                        } else {
                            alert(`Fehler beim Laden des Comics: ${json.error}`);
                        }
                    })
                    .catch((err) => {
                        console.error('[ReportManager] Fehler beim API-Transfer:', err);
                        alert('Der Comic konnte nicht geladen werden (Netzwerkfehler).');
                    })
                    .finally(() => {
                        btnTransfer.innerHTML = btnOriginalText;
                        btnTransfer.disabled = false;
                    });
            }
        });
    }

    // Helper-Funktion für saubere Diff-Ansichten
    convertHtmlToText(html) {
        if (!html) return '';
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        tempDiv.querySelectorAll('p, br').forEach((el) => {
            el.after(document.createTextNode('\n'));
        });
        return (tempDiv.textContent || tempDiv.innerText || '').trim();
    }

    // Rekursive JSON Baumdarstellung für Telemetrie
    renderJsonToHtml(obj) {
        if (typeof obj !== 'object' || obj === null) {
            const val = obj === null ? 'null' : obj;
            if (typeof val === 'string' && val.startsWith('http')) {
                return `<a href="${val}" target="_blank" style="text-decoration: underline; color: var(--link-color);">${val}</a>`;
            }
            return `<span style="color: var(--text-color);">${val}</span>`;
        }

        let html =
            '<ul style="list-style: none; padding-left: 20px; margin: 5px 0; border-left: 2px solid var(--border-medium);">';
        for (const [key, value] of Object.entries(obj)) {
            html += `<li style="margin-bottom: 6px;"><strong style="color: var(--text-color-faded);">${key}:</strong> ${this.renderJsonToHtml(value)}</li>`;
        }
        html += '</ul>';
        return html;
    }

    openReportModal(payload) {
        this.currentReportPayload = payload;

        // Link oder Text
        const comicIdContainer = document.getElementById('rep-modal-comic-id');
        if (payload.comicId) {
            comicIdContainer.innerHTML = `<a href="${this.api.baseUrl}/comics/${payload.comicId}" target="_blank">${payload.comicId}</a>`;
        } else {
            comicIdContainer.innerHTML =
                '<em style="color: var(--text-color-faded);">Allgemeine Website</em>';
        }

        const repAvatarImg = document.getElementById('rep-modal-avatar-img');
        if (repAvatarImg) {
            repAvatarImg.src = payload.avatarUrl
                ? `${this.api.baseUrl}/assets/images/avatars/${payload.avatarUrl}`
                : 'https://placehold.co/60x60?text=N/A';
        }

        document.getElementById('rep-modal-submitter').textContent = payload.submitter;
        document.getElementById('rep-modal-date').textContent = payload.date;
        document.getElementById('rep-modal-desc').textContent =
            payload.description || 'Keine Beschreibung angegeben.';

        // Telemetrie JSON Logik
        const debugRaw = document.getElementById('rep-modal-debug');
        const debugRendered = document.getElementById('rep-modal-debug-rendered');
        const btnToggleDebug = document.getElementById('btn-toggle-debug-view');

        if (debugRaw) debugRaw.value = payload.debug || 'Keine Telemetrie vorhanden.';

        if (debugRendered && payload.debug) {
            try {
                const parsed = JSON.parse(payload.debug);
                debugRendered.innerHTML = this.renderJsonToHtml(parsed);
                debugRendered.style.display = 'block';
                if (debugRaw) debugRaw.style.display = 'none';

                if (btnToggleDebug) {
                    btnToggleDebug.style.display = 'inline-block';
                    btnToggleDebug.innerHTML = '<i class="fa-solid fa-code"></i> Rohdaten anzeigen';
                }
            } catch (err) {
                console.warn(
                    '[ReportManager] Fehler beim Parsen der Telemetrie. Fallback auf Rohtext.',
                    err
                );
                debugRendered.style.display = 'none';
                if (debugRaw) debugRaw.style.display = 'block';
                if (btnToggleDebug) btnToggleDebug.style.display = 'none';
            }
        } else {
            if (debugRendered) debugRendered.style.display = 'none';
            if (btnToggleDebug) btnToggleDebug.style.display = 'none';
            if (debugRaw) debugRaw.style.display = 'block';
        }

        // JS-Diff Logik
        const transcriptSec = document.getElementById('rep-modal-transcript-section');
        const diffBox = document.getElementById('rep-modal-diff');

        if (payload.type === 'transcript') {
            transcriptSec.style.display = 'block';

            if (typeof window.Diff !== 'undefined' && window.Diff.diffWordsWithSpace) {
                const oldTxt = this.convertHtmlToText(payload.original);
                const newTxt = this.convertHtmlToText(payload.suggestion);
                const diff = window.Diff.diffWordsWithSpace(oldTxt, newTxt);

                const fragment = document.createDocumentFragment();

                diff.forEach((part) => {
                    const node = document.createElement(
                        part.added ? 'ins' : part.removed ? 'del' : 'span'
                    );
                    node.appendChild(document.createTextNode(part.value));
                    fragment.appendChild(node);
                });

                diffBox.innerHTML = '';
                diffBox.appendChild(fragment);
            } else {
                diffBox.innerHTML = `Diff-Bibliothek nicht geladen. Vorschlag:\n\n${payload.suggestion}`;
            }
        } else {
            transcriptSec.style.display = 'none';
        }

        // Screenshot Logik
        const screenshotSec = document.getElementById('rep-modal-screenshot-section');
        const screenshotImg = document.getElementById('rep-modal-screenshot-img');
        const screenshotLink = document.getElementById('rep-modal-screenshot-link');

        if (payload.screenshotUrl) {
            const fullUrl = `${this.api.baseUrl}/assets/images/reports/${payload.screenshotUrl}`;
            screenshotImg.src = fullUrl;
            screenshotLink.href = fullUrl;
            screenshotSec.style.display = 'block';
        } else {
            screenshotSec.style.display = 'none';
            screenshotImg.src = '';
            screenshotLink.href = '#';
        }

        const btnResolve = document.getElementById('btn-rep-resolve');
        const btnSpam = document.getElementById('btn-rep-spam');

        if (btnResolve)
            btnResolve.style.display = payload.status === 'open' ? 'inline-block' : 'none';
        if (btnSpam) btnSpam.style.display = payload.status === 'open' ? 'inline-block' : 'none';

        this.modalManager.open('report-detail-modal');
    }

    async resolveReport(btnElement) {
        if (
            !this.currentReportPayload ||
            !confirm('Möchtest du diesen Bericht als "Erledigt" markieren?')
        )
            return;

        const originalText = btnElement.innerHTML;
        btnElement.disabled = true;
        btnElement.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

        const formData = new window.FormData();
        formData.append('report_id', this.currentReportPayload.id);
        formData.append('status', 'closed');

        const result = await this.api.post('update_report_status', formData);

        if (result.success) {
            this.notifications.show(result.message, 'success');
            if (this.tracker) this.tracker.markClean(); // Bugfix
            setTimeout(() => window.location.reload(), 1000);
        } else {
            this.notifications.show(result.error, 'error');
            btnElement.disabled = false;
            btnElement.innerHTML = originalText;
        }
    }

    async markAsSpam(btnElement) {
        if (
            !this.currentReportPayload ||
            !confirm('Möchtest du diesen Bericht als SPAM markieren?')
        )
            return;

        const originalText = btnElement.innerHTML;
        btnElement.disabled = true;
        btnElement.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

        const formData = new window.FormData();
        formData.append('report_id', this.currentReportPayload.id);
        formData.append('status', 'spam');

        const result = await this.api.post('update_report_status', formData);

        if (result.success) {
            this.notifications.show('Bericht markiert.', 'success');
            if (this.tracker) this.tracker.markClean(); // Bugfix
            setTimeout(() => window.location.reload(), 1000);
        } else {
            this.notifications.show(result.error, 'error');
            btnElement.disabled = false;
            btnElement.innerHTML = originalText;
        }
    }
}
