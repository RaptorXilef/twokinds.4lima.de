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
                    this.deleteCharacter(btnDelete.dataset.id, btnDelete.dataset.name);
                }
            });
        }

        // 3. Speichern-Button im Modal
        const btnSave = document.getElementById('btn-save-char');
        if (btnSave) {
            btnSave.addEventListener('click', () => this.saveCharacter(btnSave));
        }
    }

    bindImageSelection() {
        // Profilbild Auswahl
        const profileGrid = document.getElementById('profile-pic-grid');
        if (profileGrid) {
            profileGrid.addEventListener('click', (e) => {
                if (e.target.tagName === 'IMG') {
                    profileGrid
                        .querySelectorAll('img')
                        .forEach((img) => img.classList.remove('selected'));
                    e.target.classList.add('selected');
                    document.getElementById('pic_url').value = e.target.dataset.filename;
                }
            });
        }

        // Hauptbild Auswahl
        const mainGrid = document.getElementById('main-pic-grid');
        if (mainGrid) {
            mainGrid.addEventListener('click', (e) => {
                if (e.target.tagName === 'IMG') {
                    mainGrid
                        .querySelectorAll('img')
                        .forEach((img) => img.classList.remove('selected'));
                    e.target.classList.add('selected');
                    document.getElementById('main_pic_url').value = e.target.dataset.filename;
                }
            });
        }
    }

    openAddModal() {
        if (this.form) this.form.reset();

        document.getElementById('old_char_id').value = '';
        document.getElementById('char-form-action').value = 'save';

        // WYSIWYG leeren
        if (typeof window.$ !== 'undefined' && window.$('#biography').length) {
            window.$('#biography').trumbowyg('empty');
        }

        // Bild-Selektionen zurücksetzen
        document.querySelectorAll('#profile-pic-grid img, #main-pic-grid img').forEach((img) => {
            img.classList.remove('selected');
        });
        document.getElementById('pic_url').value = '';
        document.getElementById('main_pic_url').value = '';

        document.getElementById('modal-title-char').textContent = 'Neuen Charakter erstellen';
        this.modalManager.open('char-modal');
    }

    openEditModal(payload) {
        if (this.form) this.form.reset();

        document.getElementById('old_char_id').value = payload.id;
        document.getElementById('char-form-action').value = 'save';

        // Formular-Felder befüllen
        const fields = [
            'char_id',
            'name',
            'full_name',
            'alias',
            'gender',
            'species',
            'subspecies',
            'age',
            'height',
            'weight',
            'blood_type',
            'rank',
            'languages',
        ];
        fields.forEach((field) => {
            const el = document.getElementById(field);
            if (el) el.value = payload[field] || '';
        });

        // WYSIWYG befüllen
        if (typeof window.$ !== 'undefined' && window.$('#biography').length) {
            window.$('#biography').trumbowyg('html', payload.biography || '');
        }

        // Bilder im Raster markieren
        document
            .querySelectorAll('#profile-pic-grid img, #main-pic-grid img')
            .forEach((img) => img.classList.remove('selected'));

        document.getElementById('pic_url').value = payload.picUrl || '';
        if (payload.picUrl) {
            const match = document.querySelector(
                `#profile-pic-grid img[data-filename="${payload.picUrl}"]`
            );
            if (match) match.classList.add('selected');
        }

        document.getElementById('main_pic_url').value = payload.mainPic || '';
        if (payload.mainPic) {
            const match = document.querySelector(
                `#main-pic-grid img[data-filename="${payload.mainPic}"]`
            );
            if (match) match.classList.add('selected');
        }

        document.getElementById('modal-title-char').textContent = 'Charakter bearbeiten';
        this.modalManager.open('char-modal');
    }

    async saveCharacter(btnElement) {
        if (!this.form.reportValidity()) return;

        const originalText = btnElement.innerHTML;
        btnElement.disabled = true;
        btnElement.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Speichere...';

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
            btnElement.disabled = false;
            btnElement.innerHTML = originalText;
        }
    }

    async deleteCharacter(id, name) {
        if (
            !confirm(
                `Achtung: Möchtest du den Charakter '${name}' wirklich löschen?\nAlle Verknüpfungen in Comics und Gruppen werden entfernt!`
            )
        )
            return;

        const formData = new window.FormData();
        formData.append('char_id', id);

        const result = await this.api.post('delete_character', formData);
        if (result.success) {
            this.api.showStatus(result.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            this.api.showStatus(result.error, 'error');
        }
    }
}
