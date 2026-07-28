export class ComicEditor {
    constructor(api, modalManager) {
        // Dependency Injection
        this.api = api;
        this.modalManager = modalManager;

        // DOM Elemente
        this.section = document.getElementById('section-comics');
        this.form = document.getElementById('comic-form');

        if (this.section) {
            this.bindEvents();
        }
    }

    bindEvents() {
        // 1. "Neuen Comic" Button
        const btnAdd = document.getElementById('btn-add-comic');
        if (btnAdd) {
            btnAdd.addEventListener('click', () => this.openAddModal());
        }

        // 2. Papierkorb Button
        const btnRestoreDeleted = document.getElementById('btn-restore-deleted-comic');
        if (btnRestoreDeleted) {
            btnRestoreDeleted.addEventListener('click', () => this.restoreDeleted());
        }

        // 3. Event Delegation für die Tabelle (Bearbeiten, Löschen, Undo)
        this.section.addEventListener('click', (e) => {
            const btnEdit = e.target.closest('.btn-edit-comic');
            const btnDelete = e.target.closest('.btn-delete-comic');
            const btnUndo = e.target.closest('.btn-undo-comic');

            if (btnEdit) {
                e.preventDefault();
                this.openEditModal(JSON.parse(btnEdit.dataset.payload));
            }

            if (btnDelete) {
                e.preventDefault();
                this.deleteComic(btnDelete.dataset.id);
            }

            if (btnUndo) {
                e.preventDefault();
                this.undoComic(btnUndo.dataset.id);
            }
        });

        // 4. Speichern-Button im Modal
        const btnSave = document.getElementById('btn-save-comic');
        if (btnSave) {
            btnSave.addEventListener('click', () => this.saveComic(btnSave));
        }

        // 5. Charakter-Auswahl Klick-Logik im Modal
        document.addEventListener('click', (e) => {
            const charItem = e.target.closest('.char-selection-item');
            if (charItem && this.form) {
                charItem.classList.toggle('selected');
                const charId = charItem.dataset.charId;
                const hiddenSelect = document.getElementById('hidden-comic-chars');
                if (hiddenSelect) {
                    const opt = hiddenSelect.querySelector(`option[value="${charId}"]`);
                    if (opt) {
                        opt.selected = charItem.classList.contains('selected');
                    }
                }
            }
        });
    }

    openAddModal() {
        if (this.form) this.form.reset();

        document.getElementById('old_comic_id').value = '';
        document.getElementById('form-action').value = 'save';

        // WYSIWYG leeren
        if (typeof window.$ !== 'undefined' && window.$('#transcript').length) {
            window.$('#transcript').trumbowyg('empty');
        }

        // Previews zurücksetzen
        document.getElementById('prev-comic-local').src =
            'https://placehold.co/100x160?text=Kein+Bild';
        document.getElementById('prev-comic-orig').src =
            'https://placehold.co/100x160?text=Kein+Bild';
        document.getElementById('prev-comic-sketch').src =
            'https://placehold.co/100x160?text=Kein+Bild';
        document.getElementById('prev-comic-social').src =
            'https://placehold.co/191x100?text=Kein+Bild';

        this.resetCharacterSelection();

        document.getElementById('modal-title-comic').textContent = 'Neuen Comic hinzufügen';
        this.modalManager.open('comic-modal');
    }

    openEditModal(payload) {
        if (this.form) this.form.reset();

        document.getElementById('old_comic_id').value = payload.id;
        document.getElementById('comic_id').value = payload.id;
        document.getElementById('type').value = payload.type || 'Comicseite';
        document.getElementById('name').value = payload.name || '';
        document.getElementById('chapter_id').value = payload.chapterId || '';
        document.getElementById('url_originalbild').value = payload.originalUrl || '';
        document.getElementById('url_originalsketch').value = payload.sketchUrl || '';

        // WYSIWYG füllen
        if (typeof window.$ !== 'undefined' && window.$('#transcript').length) {
            window.$('#transcript').trumbowyg('html', payload.transcript || '');
        }

        // Bilder Previews setzen
        const cb = payload.imageUpdatedAt ? '?c=' + payload.imageUpdatedAt : '';
        document.getElementById('prev-comic-local').src =
            `/assets/images/comic/thumbnails/${payload.id}.webp${cb}`;
        document.getElementById('prev-comic-social').src =
            `/assets/images/comic/socialmedia/${payload.id}.jpg${cb}`;

        if (payload.originalUrl) {
            document.getElementById('prev-comic-orig').src =
                `https://cdn.twokinds.keenspot.com/comics/${payload.originalUrl}`;
        }
        if (payload.sketchUrl) {
            document.getElementById('prev-comic-sketch').src = payload.sketchUrl;
        }

        this.applyCharacterSelection(payload.characters || []);

        document.getElementById('modal-title-comic').textContent = 'Comic bearbeiten';
        this.modalManager.open('comic-modal');
    }

    resetCharacterSelection() {
        document
            .querySelectorAll('.char-selection-item')
            .forEach((item) => item.classList.remove('selected'));
        const hiddenSelect = document.getElementById('hidden-comic-chars');
        if (hiddenSelect) {
            Array.from(hiddenSelect.options).forEach((opt) => (opt.selected = false));
        }
    }

    applyCharacterSelection(characterIds) {
        this.resetCharacterSelection();
        const hiddenSelect = document.getElementById('hidden-comic-chars');

        characterIds.forEach((charId) => {
            document
                .querySelectorAll(`.char-selection-item[data-char-id="${charId}"]`)
                .forEach((item) => {
                    item.classList.add('selected');
                });
            if (hiddenSelect) {
                const opt = hiddenSelect.querySelector(`option[value="${charId}"]`);
                if (opt) opt.selected = true;
            }
        });
    }

    async saveComic(btnElement) {
        if (!this.form.reportValidity()) return;

        const originalText = btnElement.innerHTML;
        btnElement.disabled = true;
        btnElement.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Speichere...';

        const formData = new window.FormData(this.form);

        // Trumbowyg Inhalt explizit abgreifen (da es manchmal nicht ins <textarea> synct)
        if (typeof window.$ !== 'undefined' && window.$('#transcript').length) {
            formData.set('transcript', window.$('#transcript').trumbowyg('html'));
        }

        const result = await this.api.post('save_single_comic', formData);

        if (result.success) {
            this.api.showStatus(result.message, 'success');
            this.modalManager.close('comic-modal');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            this.api.showStatus(result.error, 'error');
            btnElement.disabled = false;
            btnElement.innerHTML = originalText;
        }
    }

    async deleteComic(id) {
        if (!confirm(`Möchtest du den Comic '${id}' wirklich löschen?`)) return;

        const formData = new window.FormData();
        formData.append('comic_id', id);

        const result = await this.api.post('delete_comic', formData);
        if (result.success) {
            this.api.showStatus(result.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            this.api.showStatus(result.error, 'error');
        }
    }

    async undoComic(id) {
        if (
            !confirm(
                `Möchtest du die letzte Änderung an Comic '${id}' rückgängig machen/wiederherstellen?`
            )
        )
            return;

        const formData = new window.FormData();
        formData.append('comic_id', id);

        const result = await this.api.post('undo_comic', formData);
        if (result.success) {
            this.api.showStatus(result.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            this.api.showStatus(result.error, 'error');
        }
    }

    async restoreDeleted() {
        if (!confirm('Möchtest du den zuletzt gelöschten Comic wiederherstellen?')) return;

        const result = await this.api.post('restore_deleted_comic');
        if (result.success) {
            this.api.showStatus(result.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            this.api.showStatus(result.error, 'error');
        }
    }
}
