export class CharacterEditor {
    constructor(api, modalManager) {
        this.api = api;
        this.modalManager = modalManager;

        this.section = document.getElementById('section-characters');
        this.form = document.getElementById('char-form');

        if (this.section || this.form) {
            this.bindEvents();
            this.bindImageSelection();
        }
    }

    bindEvents() {
        // 1. "Neuen Charakter" Button
        const btnAdd = document.getElementById('btn-add-char');
        if (btnAdd) {
            btnAdd.addEventListener('click', () => this.openAddModal());
        }

        // 2. Event Delegation für die Tabelle (Bearbeiten, Löschen)
        if (this.section) {
            this.section.addEventListener('click', (e) => {
                const btnEdit = e.target.closest('.btn-edit-char');
                const btnDelete = e.target.closest('.btn-delete-char');

                if (btnEdit) {
                    e.preventDefault();
                    this.openEditModal(JSON.parse(btnEdit.dataset.payload));
                }

                if (btnDelete) {
                    e.preventDefault();
                    this.deleteCharacter(btnDelete.dataset.id, btnDelete.dataset.name, btnDelete);
                }
            });
        }

        // Globale Delegation für Modal-Buttons
        document.addEventListener('click', (e) => {
            const btnSave = e.target.closest('#btn-save-char');
            const btnCancel = e.target.closest('.btn-close-char-modal');

            if (btnSave) {
                e.preventDefault();
                this.saveCharacter(btnSave);
            }

            if (btnCancel) {
                e.preventDefault();
                this.modalManager.close('char-modal');
            }
        });
    }

    bindImageSelection() {
        // Profilbild Auswahl
        const profileGrid = document.getElementById('profile-pic-grid');
        if (profileGrid) {
            profileGrid.addEventListener('click', (e) => {
                if (e.target.tagName === 'IMG') {
                    profileGrid.querySelectorAll('img').forEach((img) => {
                        img.classList.remove('selected');
                    });
                    e.target.classList.add('selected');
                    const picInput = document.getElementById('pic_url');
                    if (picInput) picInput.value = e.target.dataset.filename;
                }
            });
        }

        // Hauptbild Auswahl
        const mainGrid = document.getElementById('main-pic-grid');
        if (mainGrid) {
            mainGrid.addEventListener('click', (e) => {
                if (e.target.tagName === 'IMG') {
                    mainGrid.querySelectorAll('img').forEach((img) => {
                        img.classList.remove('selected');
                    });
                    e.target.classList.add('selected');
                    const mainInput = document.getElementById('main_pic_url');
                    if (mainInput) mainInput.value = e.target.dataset.filename;
                }
            });
        }
    }

    openAddModal() {
        if (this.form) this.form.reset();

        const setVal = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.value = val;
        };
        setVal('old_char_id', '');
        setVal('char-form-action', 'save');
        setVal('pic_url', '');
        setVal('main_pic_url', '');

        if (typeof window.$ !== 'undefined' && window.$('#biography').length) {
            window.$('#biography').trumbowyg('empty');
        }

        // Bild-Selektionen zurücksetzen
        document.querySelectorAll('#profile-pic-grid img, #main-pic-grid img').forEach((img) => {
            img.classList.remove('selected');
        });

        const titleEl = document.getElementById('modal-title-char');
        if (titleEl) titleEl.textContent = 'Neuen Charakter erstellen';
        this.modalManager.open('char-modal');
    }

    openEditModal(payload) {
        if (this.form) this.form.reset();

        // Crash-Sicherer Wrapper
        const setVal = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.value = val || '';
        };

        setVal('old_char_id', payload.id);
        setVal('char-form-action', 'save');

        setVal('character_id', payload.id);
        setVal('char_id', payload.id);
        setVal('name', payload.name);
        setVal('char_name', payload.name);
        setVal('fullName', payload.fullName);
        setVal('full_name', payload.fullName);
        setVal('altNames', payload.altNames);
        setVal('alt_names', payload.altNames);
        setVal('gender', payload.gender);
        setVal('age', payload.age);
        setVal('rank', payload.rank);
        setVal('char_rank', payload.rank);
        setVal('species', payload.species);
        setVal('subspecies', payload.subspecies);
        setVal('languages', payload.languages);

        if (typeof window.$ !== 'undefined' && window.$('#biography').length) {
            window.$('#biography').trumbowyg('html', payload.biography || '');
        }

        // Bilder im Raster markieren
        document.querySelectorAll('#profile-pic-grid img, #main-pic-grid img').forEach((img) => {
            img.classList.remove('selected');
        });

        setVal('pic_url', payload.picUrl);
        if (payload.picUrl) {
            const match = document.querySelector(
                `#profile-pic-grid img[data-filename="${payload.picUrl}"]`
            );
            if (match) match.classList.add('selected');
        }

        setVal('main_pic_url', payload.mainPic);
        if (payload.mainPic) {
            const match = document.querySelector(
                `#main-pic-grid img[data-filename="${payload.mainPic}"]`
            );
            if (match) match.classList.add('selected');
        }

        const titleEl = document.getElementById('modal-title-char');
        if (titleEl) titleEl.textContent = 'Charakter bearbeiten';
        this.modalManager.open('char-modal');
    }

    async saveCharacter(btnElement) {
        if (!this.form?.reportValidity()) return;

        const originalText = btnElement.innerHTML;
        btnElement.disabled = true;
        btnElement.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Speichere...';

        try {
            const formData = new window.FormData(this.form);

            // Trumbowyg Inhalt explizit abgreifen
            if (typeof window.$ !== 'undefined' && window.$('#biography').length) {
                formData.set('biography', window.$('#biography').trumbowyg('html'));
            }

            const result = await this.api.post('save_character', formData);

            if (result.success) {
                this.api.showStatus(result.message, 'success');
                this.modalManager.close('char-modal');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                this.api.showStatus(result.error, 'error');
            }
        } finally {
            btnElement.disabled = false;
            btnElement.innerHTML = originalText;
        }
    }

    async deleteCharacter(id, name, btnElement) {
        const check = prompt(
            `ACHTUNG: Möchtest du den Charakter "${name}" (${id}) wirklich löschen?\n\nUm den Löschvorgang zu bestätigen, tippe bitte den Namen "${name}" in das Feld ein:`
        );
        if (check !== name) return;

        const formData = new window.FormData();
        formData.append('char_id', id);

        const result = await this.api.post('delete_character', formData);
        if (result.success) {
            this.api.showStatus(result.message, 'success');
            // Sofort DOM Element entfernen
            const row = btnElement.closest('tr');
            if (row) row.remove();
        } else {
            this.api.showStatus(result.error, 'error');
        }
    }
}
