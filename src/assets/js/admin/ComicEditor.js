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
        if (btnAdd) btnAdd.addEventListener('click', () => this.openAddModal());

        // 2. Papierkorb Button
        const btnRestoreDeleted = document.getElementById('btn-restore-deleted-comic');
        if (btnRestoreDeleted)
            btnRestoreDeleted.addEventListener('click', () => this.restoreDeleted());

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
                this.deleteComic(btnDelete.dataset.id, btnDelete);
            }
            if (btnUndo) {
                e.preventDefault();
                this.undoComic(btnUndo.dataset.id);
            }
        });

        // Globale Delegation für Modal-Buttons
        document.addEventListener('click', (e) => {
            const btnSave = e.target.closest('#btn-save-comic');
            const btnCancel = e.target.closest('.btn-close-comic-modal');
            const charItem = e.target.closest('.char-selection-item:not(.gallery-item)');

            if (btnSave) {
                e.preventDefault();
                this.saveComic(btnSave);
            }
            if (btnCancel) {
                e.preventDefault();
                this.modalManager.close('comic-modal');
            }

            if (charItem && this.form) {
                charItem.classList.toggle('selected');
                const charId = charItem.dataset.charId;
                const hiddenSelect = document.getElementById('hidden-comic-chars');
                if (hiddenSelect) {
                    const opt = hiddenSelect.querySelector(`option[value="${charId}"]`);
                    if (opt) opt.selected = charItem.classList.contains('selected');
                }
            }
        });
    }

    openAddModal() {
        if (this.form) this.form.reset();
        const setVal = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.value = val;
        };

        setVal('old_comic_id', '');
        setVal('form-action', 'save');

        // WYSIWYG leeren
        if (typeof window.$ !== 'undefined' && window.$('#transcript').length) {
            window.$('#transcript').trumbowyg('empty');
        }

        const setSrc = (id, src) => {
            const el = document.getElementById(id);
            if (el) el.src = src;
        };
        setSrc('prev-comic-local', 'https://placehold.co/100x160?text=Kein+Bild');
        setSrc('prev-comic-orig', 'https://placehold.co/100x160?text=Kein+Bild');
        setSrc('prev-comic-sketch', 'https://placehold.co/100x160?text=Kein+Bild');
        setSrc('prev-comic-social', 'https://placehold.co/191x100?text=Kein+Bild');

        this.resetCharacterSelection();

        const titleEl = document.getElementById('modal-title-comic');
        if (titleEl) titleEl.textContent = 'Neuen Comic hinzufügen';
        this.modalManager.open('comic-modal');
    }

    openEditModal(payload) {
        if (this.form) this.form.reset();
        const setVal = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.value = val || '';
        };

        setVal('old_comic_id', payload.id);
        setVal('comic_id', payload.id);
        setVal('type', payload.type || 'Comicseite');
        setVal('name', payload.name);
        setVal('chapter_id', payload.chapterId);
        setVal('url_originalbild', payload.originalUrl);
        setVal('url_originalsketch', payload.sketchUrl);

        // WYSIWYG füllen
        if (typeof window.$ !== 'undefined' && window.$('#transcript').length) {
            window.$('#transcript').trumbowyg('html', payload.transcript || '');
        }

        // Bilder Previews setzen
        const cb = payload.imageUpdatedAt ? `?c=${payload.imageUpdatedAt}` : '';
        const setSrc = (id, src) => {
            const el = document.getElementById(id);
            if (el) el.src = src;
        };

        setSrc('prev-comic-local', `/assets/images/comic/thumbnails/${payload.id}.webp${cb}`);
        setSrc('prev-comic-social', `/assets/images/comic/socialmedia/${payload.id}.jpg${cb}`);

        if (payload.originalUrl)
            setSrc(
                'prev-comic-orig',
                `https://cdn.twokinds.keenspot.com/comics/${payload.originalUrl}`
            );
        if (payload.sketchUrl) setSrc('prev-comic-sketch', payload.sketchUrl);

        this.applyCharacterSelection(payload.characters || []);

        const titleEl = document.getElementById('modal-title-comic');
        if (titleEl) titleEl.textContent = 'Comic bearbeiten';
        this.modalManager.open('comic-modal');
    }

    resetCharacterSelection() {
        document.querySelectorAll('.char-selection-item').forEach((item) => {
            item.classList.remove('selected');
        });
        const hiddenSelect = document.getElementById('hidden-comic-chars');
        if (hiddenSelect) {
            Array.from(hiddenSelect.options).forEach((opt) => {
                opt.selected = false;
            });
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
                if (opt) {
                    opt.selected = true;
                }
            }
        });
    }

    async saveComic(btnElement) {
        if (!this.form?.reportValidity()) return;

        const originalText = btnElement.innerHTML;
        btnElement.disabled = true;
        btnElement.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Speichere...';

        try {
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
            }
        } finally {
            btnElement.disabled = false;
            btnElement.innerHTML = originalText;
        }
    }

    async deleteComic(id, btnElement) {
        const check = prompt(
            `ACHTUNG: Willst du Comic ${id} unwiderruflich löschen?\nTippe "${id}" in das Feld ein:`
        );
        if (check !== id) return;

        const formData = new window.FormData();
        formData.append('comic_id', id);

        const result = await this.api.post('delete_comic', formData);
        if (result.success) {
            this.api.showStatus(result.message, 'success');
            // Sofort ausblenden
            const row = btnElement.closest('tr');
            if (row) row.remove();
        } else {
            this.api.showStatus(result.error, 'error');
        }
    }

    async undoComic(id) {
        if (!confirm(`Soll der Comic ${id} auf die VORHERIGE Version zurückgesetzt werden?`))
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
