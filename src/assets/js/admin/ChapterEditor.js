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
                    this.deleteChapter(btnDelete.dataset.id, btnDelete);
                }
            });
        }

        // Globale Delegation für Modal-Buttons
        document.addEventListener('click', (e) => {
            const btnSave = e.target.closest('#btn-save-chapter');
            const btnCancel = e.target.closest('.btn-close-chapter-modal');

            if (btnSave) {
                e.preventDefault();
                this.saveChapter(btnSave);
            }

            if (btnCancel) {
                e.preventDefault();
                this.modalManager.close('chapter-modal');
            }
        });
    }

    openAddModal() {
        if (this.form) this.form.reset();

        const setVal = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.value = val;
        };
        setVal('old_chapter_id', '');
        setVal('chapter-form-action', 'save');

        const titleEl = document.getElementById('modal-title-chapter');
        if (titleEl) titleEl.textContent = 'Neues Kapitel anlegen';

        if (typeof window.$ !== 'undefined' && window.$('#chap_description').length) {
            window.$('#chap_description').trumbowyg('empty');
        }

        this.modalManager.open('chapter-modal');
    }

    openEditModal(payload) {
        if (this.form) this.form.reset();

        // Crash-sichere Zuweisung, egal wie die HTML ID heißt!
        const setVal = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.value = val || '';
        };

        setVal('old_chapter_id', payload.id);
        setVal('chapter-form-action', 'save');
        setVal('chapter_id', payload.id);
        setVal('chap_id', payload.id);
        setVal('title', payload.title);
        setVal('chap_title', payload.title);
        setVal('number', payload.number);

        if (typeof window.$ !== 'undefined' && window.$('#chap_description').length) {
            window.$('#chap_description').trumbowyg('html', payload.description || '');
        }

        const titleEl = document.getElementById('modal-title-chapter');
        if (titleEl) titleEl.textContent = 'Kapitel bearbeiten';
        this.modalManager.open('chapter-modal');
    }

    async saveChapter(btnElement) {
        if (!this.form?.reportValidity()) return;

        const originalText = btnElement.innerHTML;
        btnElement.disabled = true;
        btnElement.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Speichere...';

        try {
            const formData = new window.FormData(this.form);
            const result = await this.api.post('save_chapter', formData);

            if (result.success) {
                this.api.showStatus(result.message, 'success');
                this.modalManager.close('chapter-modal');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                this.api.showStatus(result.error, 'error');
            }
        } finally {
            // Egal was passiert, Button wird wieder freigegeben
            btnElement.disabled = false;
            btnElement.innerHTML = originalText;
        }
    }

    async deleteChapter(id, btnElement) {
        const check = prompt(
            `Willst du das Kapitel "${id}" löschen?\nTippe "${id}" zur Bestätigung:`
        );
        if (check !== id) return;

        const formData = new window.FormData();
        formData.append('chapter_id', id);

        const result = await this.api.post('delete_chapter', formData);
        if (result.success) {
            this.api.showStatus(result.message, 'success');
            // Sofort aus dem DOM löschen!
            const row = btnElement.closest('tr');
            if (row) row.remove();
        } else {
            this.api.showStatus(result.error, 'error');
        }
    }
}
