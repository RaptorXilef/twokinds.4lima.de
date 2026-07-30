import { ReactiveState } from './ReactiveState.js';

/**
 * @typedef {import('./Api.js').Api} Api
 * @typedef {import('./ModalManager.js').ModalManager} ModalManager
 * @typedef {import('./NotificationService.js').NotificationService} NotificationService
 * @typedef {import('./FormService.js').FormService} FormService
 */

export class ComicEditor {
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
        this.formService = formService;

        /** @type {HTMLElement|null} */
        this.section = document.getElementById('section-comics');
        /** @type {HTMLFormElement|null} */
        this.form = document.getElementById('comic-form');

        // REAKTIVER STATE INKL. AUTO-SAVE
        this.state = new ReactiveState(
            {
                comicId: '',
                origUrl: '',
                sketchUrl: '',
            },
            () => this.updateComicPreviews(),
            'admin_comic_draft'
        );

        if (this.section) {
            this.bindEvents();
            this.bindDropZones();
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
        // Felder an den State binden
        if (this.form) {
            const bindToState = (inputName, stateProp) => {
                const input = this.form.querySelector(`[name="${inputName}"]`);
                if (input) {
                    input.addEventListener('input', (e) => {
                        this.state[stateProp] = e.target.value.trim();
                    });
                }
                return input;
            };

            const comicIdInput = bindToState('comic_id', 'comicId');
            const origUrlInput = bindToState('url_originalbild', 'origUrl');
            const origSketchInput = bindToState('url_originalsketch', 'sketchUrl');

            if (comicIdInput) {
                comicIdInput.addEventListener('blur', () => {
                    const val = comicIdInput.value.trim();
                    if (val.length === 8 && !comicIdInput.readOnly) {
                        if (origUrlInput && origUrlInput.value === '') {
                            origUrlInput.value = val;
                            this.state.origUrl = val;
                        }
                        if (origSketchInput && origSketchInput.value === '') {
                            origSketchInput.value = val;
                            this.state.sketchUrl = val;
                        }
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
        // Liest direkt vom State!
        const idVal = this.state.comicId;
        const oldIdVal = this.form?.querySelector('[name="old_comic_id"]')?.value.trim() ?? '';
        const localPreviewId = oldIdVal !== '' ? oldIdVal : idVal;

        const origVal = this.state.origUrl;
        const sketchVal = this.state.sketchUrl;

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

        const setValAndState = (nameAttr, stateProp, val) => {
            const el = this.form.querySelector(`[name="${nameAttr}"]`);
            if (el) el.value = val;
            if (stateProp) this.state[stateProp] = val; // Triggert Previews
        };

        setValAndState('old_comic_id', null, '');
        setValAndState('action', null, 'save');
        setValAndState('comic_id', 'comicId', '');
        setValAndState('url_originalbild', 'origUrl', '');
        setValAndState('url_originalsketch', 'sketchUrl', '');

        const idInput = this.form.querySelector('[name="comic_id"]');
        if (idInput) {
            idInput.readOnly = false;
            sessionStorage.setItem('highlightEntityIdCancel', '');
        }

        if (typeof window.$ !== 'undefined' && window.$('#transcript').length) {
            window.$('#transcript').trumbowyg('empty');
        }

        this.resetCharacterSelection();
        this.resetDropZones();

        const titleEl = document.getElementById('modal-title-comic');
        if (titleEl) titleEl.textContent = 'Neuen Comic hinzufügen';

        this.modalManager.open('comic-modal');
    }

    openEditModal(payload) {
        if (this.form) this.form.reset();

        const setValAndState = (nameAttr, stateProp, val) => {
            const el = this.form.querySelector(`[name="${nameAttr}"]`);
            if (el) el.value = val || '';
            if (stateProp) this.state[stateProp] = val || ''; // Triggert Previews
        };

        setValAndState('old_comic_id', null, payload.id);
        setValAndState('comic_id', 'comicId', payload.id);

        const idInput = this.form.querySelector('[name="comic_id"]');
        if (idInput) {
            idInput.readOnly = true;
            sessionStorage.setItem('highlightEntityIdCancel', payload.id);
        }

        setValAndState('type', null, payload.type || 'Comicseite');
        setValAndState('name', null, payload.name);
        setValAndState('chapter_id', null, payload.chapterId);

        setValAndState('url_originalbild', 'origUrl', payload.originalUrl);
        setValAndState('url_originalsketch', 'sketchUrl', payload.sketchUrl);

        // WYSIWYG füllen
        if (typeof window.$ !== 'undefined' && window.$('#transcript').length) {
            window.$('#transcript').trumbowyg('html', payload.transcript || '');
        }

        this.applyCharacterSelection(payload.characters || []);
        this.resetDropZones();

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

    // ACHTUNG: Die Form-Service Magie
    async saveComic(btnElement) {
        if (!this.form) return;

        const customData = {};
        if (typeof window.$ !== 'undefined' && window.$('#transcript').length) {
            customData.transcript = window.$('#transcript').trumbowyg('html');
        }

        const idInput = this.form.querySelector('[name="comic_id"]');
        if (idInput) sessionStorage.setItem('highlightEntityId', idInput.value.trim());

        const success = await this.formService.submit(
            this.form,
            btnElement,
            'save_single_comic',
            customData
        );
        if (success) {
            this.state.clearCache(); // CACHE LÖSCHEN NACH ERFOLG!
            this.modalManager.close('comic-modal');
            setTimeout(() => window.location.reload(), 1000);
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
            this.notifications.show(result.message, 'success');
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
            this.notifications.show(result.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            this.notifications.show(result.error, 'error');
        }
    }
}
