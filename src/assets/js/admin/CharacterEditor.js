import { ReactiveState } from './ReactiveState.js';

/**
 * @typedef {import('./Api.js').Api} Api
 * @typedef {import('./ModalManager.js').ModalManager} ModalManager
 * @typedef {import('./NotificationService.js').NotificationService} NotificationService
 * @typedef {import('./FormService.js').FormService} FormService
 */

export class CharacterEditor {
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
        this.section = document.getElementById('section-characters');
        /** @type {HTMLFormElement|null} */
        this.form = document.getElementById('char-form');
        /** @type {DataTransfer} */
        this.accumulatedRefFiles = new DataTransfer();

        // REAKTIVER STATE FÜR LIVE-PREVIEWS INKL. AUTO-SAVE
        this.state = new ReactiveState(
            {
                picUrl: '',
                mainPicUrl: '',
                swatchPicUrl: '',
                refSheets: '',
            },
            (property, value) => this.renderPreviews(property, value),
            'admin_char_draft'
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
        // 1. "Neuen Charakter" Button
        const btnAdd = document.getElementById('btn-add-char');
        if (btnAdd) btnAdd.addEventListener('click', () => this.openAddModal());

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
        this.setupCharDropZone(
            'char-drop-zone',
            'profile_image',
            'char-preview-img',
            'upload-preview-name'
        );
        this.setupCharDropZone('char-drop-zone-main', 'main_pic', 'preview-img-main');
        this.setupCharDropZone('char-drop-zone-swatch', 'swatch_pic', 'preview-img-swatch');
        this.setupRefSheetsDropZone();
    }

    setupCharDropZone(zoneId, inputId, previewImgId, previewTextId = null) {
        const zone = document.getElementById(zoneId);
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewImgId);
        const previewText = document.getElementById(previewTextId);

        if (!zone || !input) return;

        zone.addEventListener('click', () => input.click());

        zone.addEventListener('dragover', (e) => {
            e.preventDefault();
            zone.style.borderColor = 'var(--link-color)';
            zone.style.backgroundColor = 'var(--table-row-hover)';
        });

        zone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            zone.style.borderColor = 'var(--border-medium)';
            zone.style.backgroundColor = 'var(--content-bg)';
        });

        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            zone.style.borderColor = 'var(--status-green-text)';
            zone.style.backgroundColor = 'var(--status-green-bg)';
            if (e.dataTransfer.files.length) {
                input.files = e.dataTransfer.files;
                input.dispatchEvent(new Event('change'));
            }
        });

        input.addEventListener('change', () => {
            if (input.files?.[0]) {
                window.isDirty = true;
                zone.style.borderColor = 'var(--status-green-text)';
                zone.style.backgroundColor = 'var(--status-green-bg)';

                if (previewText) previewText.textContent = `Bereit: ${input.files[0].name}`;

                if (preview) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        preview.src = e.target.result;
                        if (preview.style.display === 'none') preview.style.display = 'block';
                    };
                    reader.readAsDataURL(input.files[0]);
                }

                if (inputId === 'profile_image') {
                    const picUrlInput = document.getElementById('pic_url');
                    if (picUrlInput) picUrlInput.value = '';
                    this.state.picUrl = ''; // Löscht das alte Bild
                }
            }
        });
    }

    // Akkumulierung für Multi-Uploads (Ref-Sheets)
    setupRefSheetsDropZone() {
        const zoneRefs = document.getElementById('char-drop-zone-refs');
        const inputRefs = document.getElementById('ref_sheets');
        const containerRefs = document.getElementById('preview-container-refs');

        if (!zoneRefs || !inputRefs || !containerRefs) return;

        zoneRefs.addEventListener('click', () => inputRefs.click());

        zoneRefs.addEventListener('dragover', (e) => {
            e.preventDefault();
            zoneRefs.style.borderColor = 'var(--link-color)';
            zoneRefs.style.backgroundColor = 'var(--table-row-hover)';
        });

        zoneRefs.addEventListener('dragleave', (e) => {
            e.preventDefault();
            zoneRefs.style.borderColor = 'var(--border-medium)';
            zoneRefs.style.backgroundColor = 'var(--content-bg)';
        });

        zoneRefs.addEventListener('drop', (e) => {
            e.preventDefault();
            zoneRefs.style.borderColor = 'var(--status-green-text)';
            zoneRefs.style.backgroundColor = 'var(--status-green-bg)';
            if (e.dataTransfer.files.length) {
                Array.from(e.dataTransfer.files).forEach((file) => {
                    this.accumulatedRefFiles.items.add(file);
                });
                inputRefs.files = this.accumulatedRefFiles.files;
                this.updateRefDropPreview(containerRefs, zoneRefs);
            }
        });

        inputRefs.addEventListener('change', () => {
            if (inputRefs.files.length > 0) {
                Array.from(inputRefs.files).forEach((newFile) => {
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
                    if (!exists) {
                        this.accumulatedRefFiles.items.add(newFile);
                    }
                });
                inputRefs.files = this.accumulatedRefFiles.files;
                this.updateRefDropPreview(containerRefs, zoneRefs);
            }
        });
    }

    updateRefDropPreview(containerRefs, zoneRefs) {
        window.isDirty = true;
        if (zoneRefs) {
            zoneRefs.style.borderColor = 'var(--status-green-text)';
            zoneRefs.style.backgroundColor = 'var(--status-green-bg)';
        }

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
        [
            'char-drop-zone',
            'char-drop-zone-main',
            'char-drop-zone-swatch',
            'char-drop-zone-refs',
        ].forEach((id) => {
            const el = document.getElementById(id);
            if (el) {
                el.style.borderColor = 'var(--border-medium)';
                el.style.backgroundColor = 'var(--table-row-even)';
            }
        });
        const previewNameEl = document.getElementById('upload-preview-name');
        if (previewNameEl) previewNameEl.textContent = '';

        const containerRefs = document.getElementById('preview-container-refs');
        if (containerRefs) containerRefs.innerHTML = '';

        this.accumulatedRefFiles = new DataTransfer();
    }

    openAddModal() {
        if (this.form) this.form.reset();

        const setValAndState = (nameAttr, stateProp, val) => {
            const el = this.form.querySelector(`[name="${nameAttr}"]`);
            if (el) el.value = val;
            if (stateProp) this.state[stateProp] = val; // Triggert Vorschau!
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
    }

    openEditModal(payload) {
        if (this.form) this.form.reset();

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

        const success = await this.formService.submit(
            this.form,
            btnElement,
            'save_single_character',
            customData
        );
        if (success) {
            this.state.clearCache(); // CACHE LÖSCHEN NACH ERFOLG!
            this.modalManager.close('char-modal');
            setTimeout(() => window.location.reload(), 1000);
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
            this.notifications.show(result.message, 'success');
            // Sofort DOM Element entfernen
            const row = btnElement.closest('tr');
            if (row) row.remove();
        } else {
            this.notifications.show(result.error, 'error');
        }
    }
}
