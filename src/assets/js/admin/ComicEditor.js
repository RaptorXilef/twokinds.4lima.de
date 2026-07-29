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
            this.bindDropZones(); // FIX: Dropzones binden
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

        // --- LIVE PREVIEW EVENTS ---
        const triggerUpdate = () => this.updateComicPreviews();

        // Suchen der Felder isoliert in dieser Form
        if (this.form) {
            const comicIdInput = this.form.querySelector('[name="comic_id"]');
            const origUrlInput = this.form.querySelector('[name="url_originalbild"]');
            const origSketchInput = this.form.querySelector('[name="url_originalsketch"]');

            if (comicIdInput) comicIdInput.addEventListener('input', triggerUpdate);
            if (origUrlInput) origUrlInput.addEventListener('input', triggerUpdate);
            if (origSketchInput) origSketchInput.addEventListener('input', triggerUpdate);

            if (comicIdInput) {
                comicIdInput.addEventListener('blur', () => {
                    const val = comicIdInput.value.trim();
                    if (val.length === 8 && !comicIdInput.readOnly) {
                        if (origUrlInput && origUrlInput.value === '') origUrlInput.value = val;
                        if (origSketchInput && origSketchInput.value === '')
                            origSketchInput.value = val;
                        this.updateComicPreviews();
                    }
                });
            }
        }
    }

    // Drag&Drop Zonen
    bindDropZones() {
        this.setupDropZone('comic-drop-zone-hires', 'upload_hires', 'preview-name-hires');
        this.setupDropZone('comic-drop-zone-lowres', 'upload_lowres', 'preview-name-lowres');
    }

    setupDropZone(zoneId, inputId, previewId) {
        const dropZone = document.getElementById(zoneId);
        const fileInput = document.getElementById(inputId);
        const previewName = document.getElementById(previewId);

        if (!dropZone || !fileInput) return;

        dropZone.addEventListener('click', () => fileInput.click());

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = 'var(--link-color)';
            dropZone.style.backgroundColor = 'var(--table-row-hover)';
        });

        dropZone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = 'var(--border-medium)';
            dropZone.style.backgroundColor = 'var(--table-row-even)';
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = 'var(--status-green-text)';
            dropZone.style.backgroundColor = 'var(--status-green-bg)';
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                window.isDirty = true;
                if (previewName) previewName.textContent = `Ausgewählt: ${fileInput.files[0].name}`;
            }
        });

        fileInput.addEventListener('change', () => {
            const file = fileInput.files?.[0];
            if (file) {
                window.isDirty = true;
                dropZone.style.borderColor = 'var(--status-green-text)';
                dropZone.style.backgroundColor = 'var(--status-green-bg)';
                if (previewName) previewName.textContent = `Ausgewählt: ${file.name}`;
            }
        });
    }

    resetDropZones() {
        const resetEl = (idText, idZone) => {
            const txt = document.getElementById(idText);
            const zone = document.getElementById(idZone);
            if (txt) txt.textContent = '';
            if (zone) {
                zone.style.borderColor = 'var(--border-medium)';
                zone.style.backgroundColor = 'var(--table-row-even)';
            }
        };
        resetEl('preview-name-hires', 'comic-drop-zone-hires');
        resetEl('preview-name-lowres', 'comic-drop-zone-lowres');
    }

    // --- BILD VORSCHAU HELPER ---
    loadPreviewWithProbe(imgElement, basePath, extensions, fallbackUrl) {
        if (!imgElement) return;
        let i = 0;
        imgElement.src = 'https://placehold.co/100x140?text=L%C3%A4dt...';

        const testNext = () => {
            if (i >= extensions.length) {
                imgElement.src = fallbackUrl;
                return;
            }
            const ext = extensions[i++];
            const testImg = new Image();
            testImg.onload = () => {
                imgElement.src = testImg.src;
            };
            testImg.onerror = testNext;
            testImg.src = `${basePath}.${ext}`;
        };
        testNext();
    }

    updateComicPreviews() {
        if (!this.form) return;
        const comicIdInput = this.form.querySelector('[name="comic_id"]');
        const origUrlInput = this.form.querySelector('[name="url_originalbild"]');
        const origSketchInput = this.form.querySelector('[name="url_originalsketch"]');
        const oldIdInput = this.form.querySelector('[name="old_comic_id"]');

        const idVal = comicIdInput?.value.trim() ?? '';
        const oldIdVal = oldIdInput?.value.trim() ?? '';
        const localPreviewId = oldIdVal !== '' ? oldIdVal : idVal;

        const origVal = origUrlInput?.value.trim() ?? '';
        const sketchVal = origSketchInput?.value.trim() ?? '';

        const remoteExts = ['png', 'jpg', 'gif', 'jpeg', 'webp'];
        const localExts = ['webp', 'png', 'jpg', 'jpeg', 'gif'];
        const fallback = 'https://placehold.co/100x140?text=Fehlt';

        const prevLocal = document.getElementById('prev-comic-local');
        const prevOrig = document.getElementById('prev-comic-orig');
        const prevSketch = document.getElementById('prev-comic-sketch');
        const prevSocial = document.getElementById('prev-comic-social');

        if (prevLocal) {
            if (localPreviewId.length >= 8) {
                this.loadPreviewWithProbe(
                    prevLocal,
                    `${this.api.baseUrl}/assets/images/comic/lowres/${localPreviewId}`,
                    localExts,
                    fallback
                );
            } else {
                prevLocal.src = fallback;
            }
        }

        if (prevOrig) {
            if (origVal !== '') {
                if (origVal.startsWith('http')) prevOrig.src = origVal;
                else if (origVal.includes('.'))
                    prevOrig.src = `https://cdn.twokinds.keenspot.com/comics/${origVal}`;
                else
                    this.loadPreviewWithProbe(
                        prevOrig,
                        `https://cdn.twokinds.keenspot.com/comics/${origVal}`,
                        remoteExts,
                        fallback
                    );
            } else {
                prevOrig.src = fallback;
            }
        }

        if (prevSketch) {
            if (sketchVal !== '') {
                if (sketchVal.startsWith('http')) prevSketch.src = sketchVal;
                else if (sketchVal.includes('.'))
                    prevSketch.src = `https://twokindscomic.com/images/${sketchVal}`;
                else {
                    let baseSketch = sketchVal;
                    if (!baseSketch.endsWith('_sketch')) baseSketch += '_sketch';
                    this.loadPreviewWithProbe(
                        prevSketch,
                        `https://twokindscomic.com/images/${baseSketch}`,
                        remoteExts,
                        fallback
                    );
                }
            } else {
                prevSketch.src = fallback;
            }
        }

        if (prevSocial) {
            if (localPreviewId.length >= 8) {
                this.loadPreviewWithProbe(
                    prevSocial,
                    `${this.api.baseUrl}/assets/images/comic/socialmedia/${localPreviewId}`,
                    ['jpg', 'jpeg', 'webp', 'png'],
                    'https://placehold.co/191x100?text=Fehlt'
                );
            } else {
                prevSocial.src = 'https://placehold.co/191x100?text=Fehlt';
            }
        }
    }

    openAddModal() {
        if (this.form) this.form.reset();

        const setVal = (nameAttr, val) => {
            const el = this.form.querySelector(`[name="${nameAttr}"]`);
            if (el) el.value = val;
        };

        setVal('old_comic_id', '');
        setVal('action', 'save');
        setVal('comic_id', '');

        const idInput = this.form.querySelector('[name="comic_id"]');
        if (idInput) {
            idInput.readOnly = false;
            sessionStorage.setItem('highlightEntityIdCancel', '');
        }

        // WYSIWYG leeren
        if (typeof window.$ !== 'undefined' && window.$('#transcript').length) {
            window.$('#transcript').trumbowyg('empty');
        }

        this.resetCharacterSelection();
        this.resetDropZones(); // FIX: Borders reset

        const titleEl = document.getElementById('modal-title-comic');
        if (titleEl) titleEl.textContent = 'Neuen Comic hinzufügen';

        this.updateComicPreviews();
        this.modalManager.open('comic-modal');
    }

    openEditModal(payload) {
        if (this.form) this.form.reset();

        const setVal = (nameAttr, val) => {
            const el = this.form.querySelector(`[name="${nameAttr}"]`);
            if (el) el.value = val || '';
        };

        setVal('old_comic_id', payload.id);
        setVal('comic_id', payload.id);
        const idInput = this.form.querySelector('[name="comic_id"]');
        if (idInput) {
            idInput.readOnly = true; // Sperren beim Bearbeiten!
            sessionStorage.setItem('highlightEntityIdCancel', payload.id); // FIX: Cancel Fallback
        }

        setVal('type', payload.type || 'Comicseite');
        setVal('name', payload.name);
        setVal('chapter_id', payload.chapterId);
        setVal('url_originalbild', payload.originalUrl);
        setVal('url_originalsketch', payload.sketchUrl);

        // WYSIWYG füllen
        if (typeof window.$ !== 'undefined' && window.$('#transcript').length) {
            window.$('#transcript').trumbowyg('html', payload.transcript || '');
        }

        this.applyCharacterSelection(payload.characters || []);
        this.resetDropZones(); // FIX: Borders reset

        const titleEl = document.getElementById('modal-title-comic');
        if (titleEl) titleEl.textContent = 'Comic bearbeiten';

        this.updateComicPreviews();
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

            const idInput = this.form.querySelector('[name="comic_id"]');
            if (idInput) sessionStorage.setItem('highlightEntityId', idInput.value.trim());

            const result = await this.api.post('save_single_comic', formData);

            if (result.success) {
                window.isDirty = false;
                this.notifications.show(result.message, 'success');
                this.modalManager.close('comic-modal');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                this.notifications.show(result.error, 'error');
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
            window.isDirty = false;
            this.notifications.show(result.message, 'success');
            // Sofort ausblenden
            const row = btnElement.closest('tr');
            if (row) row.remove();
        } else {
            this.notifications.show(result.error, 'error');
        }
    }

    async undoComic(id) {
        if (!confirm(`Soll der Comic ${id} auf die VORHERIGE Version zurückgesetzt werden?`))
            return;
        const formData = new window.FormData();
        formData.append('comic_id', id);
        sessionStorage.setItem('highlightEntityId', id);

        const result = await this.api.post('undo_comic', formData);
        if (result.success) {
            window.isDirty = false;
            this.notifications.show(result.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            this.notifications.show(result.error, 'error');
        }
    }

    async restoreDeleted() {
        if (!confirm('Möchtest du den zuletzt gelöschten Comic wiederherstellen?')) return;
        const result = await this.api.post('restore_deleted_comic');
        if (result.success) {
            window.isDirty = false;
            this.notifications.show(result.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            this.notifications.show(result.error, 'error');
        }
    }
}
