/**
 * @typedef {import('./Api.js').Api} Api
 * @typedef {import('./ModalManager.js').ModalManager} ModalManager
 * @typedef {import('./NotificationService.js').NotificationService} NotificationService
 * @typedef {import('./FormService.js').FormService} FormService
 */

export class ChapterEditor {
    /**
     * @param {Api} api
     * @param {ModalManager} modalManager
     * @param {NotificationService} notifications
     * @param {FormService} formService
     */
    constructor(api, modalManager, notifications, formService) {
        this.api = api;
        this.modalManager = modalManager;
        this.notifications = notifications;
        this.formService = formService; // NEU

        /** @type {HTMLElement|null} */
        this.section = document.getElementById('section-archive');
        /** @type {HTMLFormElement|null} */
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

        const setVal = (nameAttr, val) => {
            const el = this.form.querySelector(`[name="${nameAttr}"]`);
            if (el) el.value = val;
        };
        setVal('old_id', '');

        const titleEl = document.getElementById('modal-title-chapter');
        if (titleEl) titleEl.textContent = 'Neues Kapitel anlegen';

        if (typeof window.$ !== 'undefined' && window.$('#chap_description').length) {
            window.$('#chap_description').trumbowyg('empty');
        }

        this.modalManager.open('chapter-modal');
    }

    openEditModal(payload) {
        if (this.form) this.form.reset();

        // 100% ID-Kollisionssicher durch Nutzung von name="..."
        const setVal = (nameAttr, val) => {
            const el = this.form.querySelector(`[name="${nameAttr}"]`);
            if (el) el.value = val || '';
        };

        setVal('old_id', payload.id);
        setVal('chapter_id', payload.id);
        setVal('title', payload.title);

        if (typeof window.$ !== 'undefined' && window.$('#chap_description').length) {
            window.$('#chap_description').trumbowyg('html', payload.description || '');
        }

        const titleEl = document.getElementById('modal-title-chapter');
        if (titleEl) titleEl.textContent = 'Kapitel bearbeiten';
        this.modalManager.open('chapter-modal');
    }

    // Save
    async saveChapter(btnElement) {
        if (!this.form) return;

        // Custom Data: Wir ziehen uns das HTML aus Trumbowyg manuell
        const customData = {};
        if (typeof window.$ !== 'undefined' && window.$('#chap_description').length) {
            customData.description = window.$('#chap_description').trumbowyg('html');
        }

        // FormService macht den Rest (Validierung, Button-Sperre, Ladespinner, API-Call, Notifications)
        const success = await this.formService.submit(
            this.form,
            btnElement,
            'save_chapter',
            customData
        );

        if (success) {
            this.modalManager.close('chapter-modal');
            setTimeout(() => window.location.reload(), 1000);
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
            this.notifications.show(result.message, 'success');
            // Sofort aus dem DOM löschen!
            const row = btnElement.closest('tr');
            if (row) row.remove();
        } else {
            this.notifications.show(result.error, 'error');
        }
    }
}
// ========== END FILE: [src\assets\js\admin\ChapterEditor.js] ==========
