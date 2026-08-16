export class BackupManager {
    constructor(api, modalManager, notifications) {
        this.api = api;
        this.modalManager = modalManager;
        this.notifications = notifications;

        this.tableBody = document.getElementById('backup-table-body');
        this.allTables = [];

        this.init();
    }

    init() {
        this.bindEvents();
        this.loadBackups();
    }

    bindEvents() {
        document.addEventListener('click', (e) => {
            const btnCreateOpen = e.target.closest('#btn-open-create-backup');
            const btnCloseModal = e.target.closest('.btn-close-backup-modal');
            const btnSubmitCreate = e.target.closest('#btn-submit-create-backup');
            const btnSubmitRestore = e.target.closest('#btn-submit-restore-backup');
            const btnRestoreOpen = e.target.closest('.btn-restore-backup');
            const btnDelete = e.target.closest('.btn-delete-backup');

            if (btnCreateOpen) {
                e.preventDefault();
                this.openCreateModal();
            }

            if (btnCloseModal) {
                e.preventDefault();
                this.modalManager.close('backup-create-modal');
                this.modalManager.close('backup-restore-modal');
            }

            if (btnSubmitCreate) {
                e.preventDefault();
                this.createBackup(btnSubmitCreate);
            }

            if (btnSubmitRestore) {
                e.preventDefault();
                this.restoreBackup(btnSubmitRestore);
            }

            if (btnDelete) {
                e.preventDefault();
                this.deleteBackup(btnDelete.dataset.filename);
            }

            if (btnRestoreOpen) {
                e.preventDefault();
                try {
                    this.openRestoreModal(JSON.parse(btnRestoreOpen.dataset.payload));
                } catch (_err) {}
            }
        });
    }

    async loadBackups() {
        if (!this.tableBody) return;

        const res = await this.api.get('list_backups');
        if (res.success) {
            this.allTables = res.tables || [];
            this.renderTable(res.backups || []);
        } else {
            this.tableBody.innerHTML = `<tr><td colspan="5" class="empty-table-message text-danger">${res.error}</td></tr>`;
        }
    }

    renderTable(backups) {
        this.tableBody.innerHTML = '';

        if (backups.length === 0) {
            this.tableBody.innerHTML =
                '<tr><td colspan="5" class="empty-table-message">Keine Backups gefunden.</td></tr>';
            return;
        }

        backups.forEach((b) => {
            const dateStr = new Date(b.date * 1000).toLocaleString('de-DE');
            const sizeStr = `${(b.size / 1024 / 1024).toFixed(2)} MB`;
            const typeStr =
                b.type === 'full' ? 'Komplett' : `Tabelle: ${b.type.replace('table_', '')}`;

            // Maskiert das generierte JSON sicher als HTML Attribute String
            const payloadStr = JSON.stringify(b).replace(/"/g, '&quot;').replace(/'/g, '&#39;');

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${dateStr}</td>
                <td><strong>${b.filename}</strong></td>
                <td><span class="badge bg-even border-medium">${typeStr}</span></td>
                <td>${sizeStr}</td>
                <td class="actions-cell">
                    <form style="display:flex; gap:5px; justify-content: flex-end;">
                        <a href="${this.api.baseUrl}/api/download_backup?file=${b.filename}" target="_blank" class="button edit" title="Herunterladen"><i class="fa-solid fa-download"></i></a>
                        <button type="button" class="button add btn-restore-backup" data-payload="${payloadStr}" title="Wiederherstellen"><i class="fa-solid fa-rotate-left"></i></button>
                        <button type="button" class="button delete btn-delete-backup" data-filename="${b.filename}" title="Löschen"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </td>
            `;
            this.tableBody.appendChild(tr);
        });
    }

    openCreateModal() {
        const select = document.getElementById('backup-create-table-select');
        select.innerHTML = '<option value="all">Komplette Datenbank (Alle Tabellen)</option>';
        this.allTables.forEach((t) => {
            select.innerHTML += `<option value="${t}">Nur Tabelle: ${t}</option>`;
        });
        this.modalManager.open('backup-create-modal');
    }

    openRestoreModal(backupData) {
        document.getElementById('restore-filename').value = backupData.filename;
        document.getElementById('restore-filename-display').value = backupData.filename;

        const select = document.getElementById('backup-restore-table-select');
        select.innerHTML = '<option value="all">Alle im Backup enthaltenen Tabellen</option>';
        backupData.tables.forEach((t) => {
            select.innerHTML += `<option value="${t}">Nur Tabelle: ${t}</option>`;
        });

        this.modalManager.open('backup-restore-modal');
    }

    async createBackup(btn) {
        const fd = new FormData(document.getElementById('backup-create-form'));
        const origText = btn.innerHTML;

        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Erstelle...';
        btn.disabled = true;

        const res = await this.api.post('create_backup', fd);

        btn.innerHTML = origText;
        btn.disabled = false;

        if (res.success) {
            this.notifications.show(res.message, 'success');
            this.modalManager.close('backup-create-modal');
            this.loadBackups();
        } else this.notifications.show(res.error, 'error');
    }

    async restoreBackup(btn) {
        const form = document.getElementById('backup-restore-form');
        const fd = new FormData(form);

        const msgBox = document.getElementById('restore-modal-msg');

        // Fehler-Box vor neuem Versuch zurücksetzen
        if (msgBox) {
            msgBox.style.display = 'none';
            msgBox.innerHTML = '';
        }

        if (fd.get('mode') === '2') {
            if (
                !confirm(
                    'ACHTUNG! "Exakte Kopie": Daten in der DB, die im Backup fehlen, werden UNWIDERRUFLICH GELÖSCHT! Fortfahren?'
                )
            )
                return;
        } else {
            if (!confirm('Soll das Backup jetzt wiederhergestellt werden?')) return;
        }

        const origText = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Wiederherstellung...';
        btn.disabled = true;

        const res = await this.api.post('restore_backup', fd);

        btn.innerHTML = origText;
        btn.disabled = false;

        if (res.success) {
            // Bei Erfolg: Modal schließen, Formular leeren und Erfolgsmeldung im Dashboard zeigen
            this.notifications.show(res.message, 'success');
            this.modalManager.close('backup-restore-modal');
            form.reset();
        } else {
            // Bei Fehler: Modal offen lassen und Fehler im Modal anzeigen
            if (msgBox) {
                msgBox.className = 'status-message status-red visible mb-15';
                msgBox.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i> ${res.error}`;
                msgBox.style.display = 'block';
            } else {
                // Fallback, falls das HTML-Feld fehlen sollte
                this.notifications.show(res.error, 'error');
            }
        }
    }

    async deleteBackup(filename) {
        if (!confirm(`Soll das Backup ${filename} wirklich gelöscht werden?`)) return;

        const fd = new FormData();
        fd.append('filename', filename);

        const res = await this.api.post('delete_backup', fd);

        if (res.success) {
            this.notifications.show(res.message, 'success');
            this.loadBackups();
        } else this.notifications.show(res.error, 'error');
    }
}
