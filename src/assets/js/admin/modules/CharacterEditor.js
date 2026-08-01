import { createReactiveState } from '../core/ReactiveState.js';
import { DragDropService } from '../ui/DragDropService.js';

/**
 * @typedef {import('../core/Api.js').Api} Api
 * @typedef {import('../ui/ModalManager.js').ModalManager} ModalManager
 * @typedef {import('../core/NotificationService.js').NotificationService} NotificationService
 * @typedef {import('../core/FormService.js').FormService} FormService
 * @typedef {import('../core/UnsavedTracker.js').UnsavedTracker} UnsavedTracker
 */

export class CharacterEditor {
    /**
     * @param {Api} api
     * @param {ModalManager} modalManager
     * @param {NotificationService} notifications
     * @param {FormService} formService
     * @param {UnsavedTracker} tracker
     */
    constructor(api, modalManager, notifications, formService, tracker) {
        this.api = api;
        this.modalManager = modalManager;
        this.notifications = notifications;
        this.formService = formService;
        this.tracker = tracker;

        /** @type {HTMLElement|null} */
        this.section = document.getElementById('section-characters');
        /** @type {HTMLFormElement|null} */
        this.form = document.getElementById('char-form');
        /** @type {DataTransfer} */
        this.accumulatedRefFiles = new DataTransfer();

        this.currentDraftKey = null;

        if (this.form) {
            this.formService.enableAutoSave(this.form, () => this.currentDraftKey);
        }

        // REAKTIVER STATE FÜR LIVE-PREVIEWS INKL. AUTO-SAVE
        // Wir nutzen hier keinen cacheKey mehr für den ReactiveState, das übernimmt jetzt der FormService komplett!
        this.state = createReactiveState(
            {
                picUrl: '',
                mainPicUrl: '',
                swatchPicUrl: '',
                refSheets: '',
            },
            (property, value) => this.renderPreviews(property, value),
            null,
            this.tracker
        );

        if (this.section || this.form) {
            this.bindEvents();
            this.bindImageSelection();
            this.bindDropZones();
            this.bindLiveStateInputs();
        }
    }

    // Verbindet die HTML-Eingabefelder mit dem State
    bindLiveStateInputs() {
        const bindToState = (inputName, stateProp) => {
            const input = this.form?.querySelector(`[name="${inputName}"]`);
            if (input) {
                input.addEventListener('input', (e) => {
                    this.state[stateProp] = e.target.value.trim();
                });
            }
        };

        bindToState('pic_url', 'picUrl');
        bindToState('main_pic_url', 'mainPicUrl');
        bindToState('swatch_pic_url', 'swatchPicUrl');
        bindToState('ref_sheets_urls', 'refSheets');
    }

    // Wird automatisch gefeuert, wenn sich this.state ändert!
    renderPreviews(property, value) {
        if (property === 'picUrl') {
            const charPreviewImg = document.getElementById('char-preview-img');
            if (charPreviewImg) {
                charPreviewImg.src = value
                    ? `${this.api.baseUrl}/assets/images/characters/profiles/${value}`
                    : 'https://placehold.co/120x120?text=Kein+Bild';
            }
        }
        if (property === 'mainPicUrl') {
            const prevMain = document.getElementById('preview-img-main');
            if (prevMain) {
                prevMain.style.display = value ? 'block' : 'none';
                prevMain.src = value
                    ? `${this.api.baseUrl}/assets/images/characters/main/${value}`
                    : '';
            }
        }
        if (property === 'swatchPicUrl') {
            const prevSwatch = document.getElementById('preview-img-swatch');
            if (prevSwatch) {
                prevSwatch.style.display = value ? 'block' : 'none';
                prevSwatch.src = value
                    ? `${this.api.baseUrl}/assets/images/characters/swatches/${value}`
                    : '';
            }
        }
        if (property === 'refSheets') {
            const containerRefs = document.getElementById('preview-container-refs');
            if (containerRefs) {
                containerRefs.innerHTML = '';
                const vals = value
                    .split(',')
                    .map((s) => s.trim())
                    .filter(Boolean);
                vals.forEach((sheet) => {
                    const img = document.createElement('img');
                    img.src = `${this.api.baseUrl}/assets/images/characters/refsheets/${sheet}`;
                    img.style.maxWidth = '80px';
                    img.style.maxHeight = '80px';
                    img.style.objectFit = 'cover';
                    img.style.borderRadius = '4px';
                    img.style.border = '1px solid var(--border-medium)';
                    containerRefs.appendChild(img);
                });
            }
        }
    }

    bindEvents() {
        // Event Delegation für die Tabelle (Bearbeiten, Löschen)
        if (this.section) {
            this.section.addEventListener('click', (e) => {
                const btnAdd = e.target.closest('#btn-add-char');
                const btnEdit = e.target.closest('.btn-edit-char');
                const btnDelete = e.target.closest('.btn-delete-char');

                if (btnAdd) {
                    e.preventDefault();
                    this.openAddModal();
                }
                if (btnEdit) {
                    e.preventDefault();
                    try {
                        const payload = JSON.parse(btnEdit.dataset.payload);
                        this.openEditModal(payload);
                    } catch (err) {
                        console.error(
                            '[CharacterEditor] Fehler beim Parsen der Charakter-Daten:',
                            err
                        );
                        this.notifications.show('Fehler beim Öffnen des Charakters.', 'error');
                    }
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
        const handleGridClick = (gridId, inputName, stateProp) => {
            const grid = document.getElementById(gridId);
            if (grid) {
                grid.addEventListener('click', (e) => {
                    if (e.target.tagName === 'IMG') {
                        grid.querySelectorAll('img').forEach((img) => {
                            img.classList.remove('selected');
                        });
                        e.target.classList.add('selected');

                        const input = this.form.querySelector(`[name="${inputName}"]`);
                        if (input) input.value = e.target.dataset.filename;

                        // Setze den State -> Vorschau aktualisiert sich magisch
                        this.state[stateProp] = e.target.dataset.filename;
                    }
                });
            }
        };

        handleGridClick('profile-pic-grid', 'pic_url', 'picUrl');
        handleGridClick('main-pic-grid', 'main_pic_url', 'mainPicUrl');
    }

    // Drag & Drop
    bindDropZones() {
        DragDropService.bind('char-drop-zone', 'profile_image', {
            tracker: this.tracker,
            previewTextId: 'upload-preview-name',
            onChange: (files) => {
                const preview = document.getElementById('char-preview-img');
                if (preview && files[0]) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        preview.src = e.target.result;
                    };
                    reader.readAsDataURL(files[0]);
                }
                const picUrlInput = document.getElementById('pic_url');
                if (picUrlInput) picUrlInput.value = '';
                this.state.picUrl = '';
            },
        });

        DragDropService.bind('char-drop-zone-main', 'main_pic', {
            tracker: this.tracker,
            onChange: (files) => {
                const preview = document.getElementById('preview-img-main');
                if (preview && files[0]) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        preview.src = e.target.result;
                        if (preview.style.display === 'none') preview.style.display = 'block';
                    };
                    reader.readAsDataURL(files[0]);
                }
            },
        });

        DragDropService.bind('char-drop-zone-swatch', 'swatch_pic', {
            tracker: this.tracker,
            onChange: (files) => {
                const preview = document.getElementById('preview-img-swatch');
                if (preview && files[0]) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        preview.src = e.target.result;
                        if (preview.style.display === 'none') preview.style.display = 'block';
                    };
                    reader.readAsDataURL(files[0]);
                }
            },
        });

        // Akkumulierung für Multi-Uploads (Ref-Sheets)
        DragDropService.bind('char-drop-zone-refs', 'ref_sheets', {
            tracker: this.tracker,
            onChange: (files) => {
                Array.from(files).forEach((newFile) => {
                    let exists = false;
                    for (let i = 0; i < this.accumulatedRefFiles.files.length; i++) {
                        if (
                            this.accumulatedRefFiles.files[i].name === newFile.name &&
                            this.accumulatedRefFiles.files[i].size === newFile.size
                        ) {
                            exists = true;
                            break;
                        }
                    }
                    if (!exists) this.accumulatedRefFiles.items.add(newFile);
                });
                document.getElementById('ref_sheets').files = this.accumulatedRefFiles.files;
                this.updateRefDropPreview();
            },
        });
    }

    updateRefDropPreview() {
        const containerRefs = document.getElementById('preview-container-refs');
        if (!containerRefs) return;

        Array.from(containerRefs.querySelectorAll('img.is-new')).forEach((img) => {
            img.remove();
        });
        Array.from(this.accumulatedRefFiles.files).forEach((file) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'is-new';
                img.style.maxWidth = '80px';
                img.style.maxHeight = '80px';
                img.style.objectFit = 'cover';
                img.style.borderRadius = '4px';
                img.style.border = '2px solid var(--status-green-text)';
                containerRefs.appendChild(img);
            };
            reader.readAsDataURL(file);
        });
    }

    resetAllDropZones() {
        DragDropService.reset('char-drop-zone', 'upload-preview-name');
        DragDropService.reset('char-drop-zone-main');
        DragDropService.reset('char-drop-zone-swatch');
        DragDropService.reset('char-drop-zone-refs');

        const containerRefs = document.getElementById('preview-container-refs');
        if (containerRefs) containerRefs.innerHTML = '';

        this.accumulatedRefFiles = new DataTransfer();
    }

    openAddModal() {
        this.currentDraftKey = 'admin_char_form_draft_new';
        if (this.form) this.form.reset();

        // Draft Recovery Check
        if (this.formService.hasDraft(this.currentDraftKey)) {
            if (
                confirm(
                    'Es existiert ein ungespeicherter Entwurf für Charaktere. Möchtest du ihn wiederherstellen?'
                )
            ) {
                // Modal ZUERST öffnen, dann erst Formular füllen!
                this.modalManager.open('char-modal');
                this.formService.restoreDraft(this.form, this.currentDraftKey);
                if (this.tracker) this.tracker.markDirty();
                return;
            } else {
                this.formService.clearDraft(this.currentDraftKey);
            }
        }

        const setValAndState = (nameAttr, stateProp, val) => {
            const el = this.form.querySelector(`[name="${nameAttr}"]`);
            if (el) el.value = val;
            if (stateProp) this.state[stateProp] = val;
        };

        setValAndState('id', null, 'new');
        setValAndState('pic_url', 'picUrl', '');
        setValAndState('main_pic_url', 'mainPicUrl', '');
        setValAndState('swatch_pic_url', 'swatchPicUrl', '');
        setValAndState('ref_sheets_urls', 'refSheets', '');

        if (typeof window.$ !== 'undefined' && window.$('#char_description').length) {
            window.$('#char_description').trumbowyg('empty');
        }

        document.querySelectorAll('#profile-pic-grid img, #main-pic-grid img').forEach((img) => {
            img.classList.remove('selected');
        });

        const displayId = document.getElementById('char-display-id');
        if (displayId) displayId.textContent = 'ID: NEW';

        const titleEl = document.getElementById('modal-title-char');
        if (titleEl) titleEl.textContent = 'Neuen Charakter erstellen';

        this.resetAllDropZones();
        sessionStorage.setItem('highlightEntityIdCancel', 'new');

        this.modalManager.open('char-modal');
        if (this.tracker) this.tracker.markClean();
    }

    openEditModal(payload) {
        // Template Literal verwenden
        this.currentDraftKey = `admin_char_form_draft_${payload.id}`;
        if (this.form) this.form.reset();

        // Draft Recovery Check
        if (this.formService.hasDraft(this.currentDraftKey)) {
            if (
                confirm(
                    'Es gibt noch ungespeicherte Änderungen! Möchtest du den abgebrochenen Entwurf laden?'
                )
            ) {
                // Modal ZUERST öffnen, dann erst Formular füllen!
                this.modalManager.open('char-modal');
                this.formService.restoreDraft(this.form, this.currentDraftKey);
                if (this.tracker) this.tracker.markDirty();
                return;
            } else {
                this.formService.clearDraft(this.currentDraftKey);
            }
        }

        const setValAndState = (nameAttr, stateProp, val) => {
            const el = this.form.querySelector(`[name="${nameAttr}"]`);
            if (el) el.value = val || '';
            if (stateProp) this.state[stateProp] = val || ''; // Triggert Vorschau!
        };

        setValAndState('id', null, payload.id);
        setValAndState('name', null, payload.name);
        setValAndState('full_name', null, payload.fullName);
        setValAndState('alt_names', null, payload.altNames);
        setValAndState('gender', null, payload.gender);
        setValAndState('age', null, payload.age);
        setValAndState('rank', null, payload.rank);
        setValAndState('species', null, payload.species);
        setValAndState('subspecies', null, payload.subspecies);
        setValAndState('languages', null, payload.languages);

        // Richtige HTML ID und payload.description
        if (typeof window.$ !== 'undefined' && window.$('#char_description').length) {
            window.$('#char_description').trumbowyg('html', payload.description || '');
        }

        // Bilder im Raster markieren
        document.querySelectorAll('#profile-pic-grid img, #main-pic-grid img').forEach((img) => {
            img.classList.remove('selected');
        });

        // Felder füllen UND input Event feuern, damit die Bilder geladen werden
        setValAndState('pic_url', 'picUrl', payload.picUrl);
        if (payload.picUrl) {
            const match = document.querySelector(
                `#profile-pic-grid img[data-filename="${payload.picUrl}"]`
            );
            if (match) match.classList.add('selected');
        }

        setValAndState('main_pic_url', 'mainPicUrl', payload.mainPic);
        if (payload.mainPic) {
            const match = document.querySelector(
                `#main-pic-grid img[data-filename="${payload.mainPic}"]`
            );
            if (match) match.classList.add('selected');
        }

        setValAndState('swatch_pic_url', 'swatchPicUrl', payload.swatchPic);
        setValAndState(
            'ref_sheets_urls',
            'refSheets',
            payload.refSheets && payload.refSheets.length > 0 ? payload.refSheets.join(', ') : ''
        );

        const displayId = document.getElementById('char-display-id');
        if (displayId) displayId.textContent = `ID: ${payload.id}`;

        const titleEl = document.getElementById('modal-title-char');
        if (titleEl) titleEl.textContent = 'Charakter bearbeiten';

        this.resetAllDropZones();
        sessionStorage.setItem('highlightEntityIdCancel', payload.id);

        this.modalManager.open('char-modal');
        if (this.tracker) this.tracker.markClean();
    }

    // ACHTUNG: Die Form-Service Magie
    async saveCharacter(btnElement) {
        if (!this.form) return;

        const customData = {};

        // Richtige HTML ID für den Fallback-Speicher
        if (typeof window.$ !== 'undefined' && window.$('#char_description').length) {
            customData.description = window.$('#char_description').trumbowyg('html');
        }

        const idInput = this.form.querySelector('[name="id"]');
        if (idInput) sessionStorage.setItem('highlightEntityId', idInput.value.trim());

        this.formService.clearDraft(this.currentDraftKey);

        // PERF: true übergeben für SOFORTIGEN Reload (ohne setTimeout!)
        await this.formService.submit(
            this.form,
            btnElement,
            'save_single_character',
            customData,
            true
        );
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
            this.notifications.show(result.message, 'success');
            // Sofort DOM Element entfernen
            const row = btnElement.closest('tr');
            if (row) row.remove();
        } else {
            this.notifications.show(result.error, 'error');
        }
    }
}
