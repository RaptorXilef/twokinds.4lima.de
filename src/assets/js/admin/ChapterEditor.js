export class ChapterEditor {
    constructor(api, modalManager) {
        this.api = api;
        this.modalManager = modalManager;

        this.section = document.getElementById('section-archive');
        this.form = document.getElementById('chapter-form');

        if (this.section || this.form) {
            this.bindEvents();
        }
    }

    bindEvents() {
        // 1. "Neues Kapitel" Button
        const btnAdd = document.getElementById('btn-add-chapter');
        if (btnAdd) {
            btnAdd.addEventListener('click', () => this.openAddModal());
        }

        // 2. Event Delegation für die Tabelle
        if (this.section) {
            this.section.addEventListener('click', (e) => {
                const btnEdit = e.target.closest('.btn-edit-chapter');
                const btnDelete = e.target.closest('.btn-delete-chapter');

                if (btnEdit) {
                    e.preventDefault();
                    this.openEditModal(JSON.parse(btnEdit.dataset.payload));
                }

                if (btnDelete) {
                    e.preventDefault();
                    this.deleteChapter(btnDelete.dataset.id);
                }
            });
        }

        // 3. Speichern-Button im Modal
        const btnSave = document.getElementById('btn-save-chapter');
        if (btnSave) {
            btnSave.addEventListener('click', () => this.saveChapter(btnSave));
        }
    }

    openAddModal() {
        if (this.form) this.form.reset();

        document.getElementById('old_chapter_id').value = '';
        document.getElementById('chapter-form-action').value = 'save';
        document.getElementById('modal-title-chapter').textContent = 'Neues Kapitel anlegen';

        this.modalManager.open('chapter-modal');
    }

    openEditModal(payload) {
        if (this.form) this.form.reset();

        document.getElementById('old_chapter_id').value = payload.id;
        document.getElementById('chapter-form-action').value = 'save';

        // Felder füllen
        const fields = ['chapter_id', 'name', 'number'];
        fields.forEach((field) => {
            const el = document.getElementById(field);
            if (el) el.value = payload[field] || '';
        });

        document.getElementById('modal-title-chapter').textContent = 'Kapitel bearbeiten';
        this.modalManager.open('chapter-modal');
    }

    async saveChapter(btnElement) {
        if (!this.form.reportValidity()) return;

        const originalText = btnElement.innerHTML;
        btnElement.disabled = true;
        btnElement.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Speichere...';

        const formData = new window.FormData(this.form);
        const result = await this.api.post('save_chapter', formData);

        if (result.success) {
            this.api.showStatus(result.message, 'success');
            this.modalManager.close('chapter-modal');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            this.api.showStatus(result.error, 'error');
            btnElement.disabled = false;
            btnElement.innerHTML = originalText;
        }
    }

    async deleteChapter(id) {
        if (
            !confirm(
                `Möchtest du das Kapitel '${id}' wirklich löschen? Die verknüpften Comics bleiben erhalten, verlieren aber ihre Kapitel-Zuordnung.`
            )
        )
            return;

        const formData = new window.FormData();
        formData.append('chapter_id', id);

        const result = await this.api.post('delete_chapter', formData);
        if (result.success) {
            this.api.showStatus(result.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            this.api.showStatus(result.error, 'error');
        }
    }
}
