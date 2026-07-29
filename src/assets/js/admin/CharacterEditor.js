/**
 * @typedef {import('./Api.js').Api} Api
 * @typedef {import('./ModalManager.js').ModalManager} ModalManager
 * @typedef {import('./NotificationService.js').NotificationService} NotificationService
 */

export class CharacterEditor {
    /**
     * @param {Api} api
     * @param {ModalManager} modalManager
     * @param {NotificationService} notifications
     */
    constructor(api, modalManager, notifications) {
        this.api = api;
        this.modalManager = modalManager;
        this.notifications = notifications;

        /** @type {HTMLElement|null} */
        this.section = document.getElementById('section-characters');
        /** @type {HTMLFormElement|null} */
        this.form = document.getElementById('char-form');
        /** @type {DataTransfer} */
        this.accumulatedRefFiles = new DataTransfer();

        if (this.section || this.form) {
            this.bindEvents();
            this.bindImageSelection();
            this.bindDropZones();
            this.bindLivePreviews(); // FIX: Live-Previews reaktivieren!
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
                    // LINTER FIX: Block Statement {} um impliziten Return zu verhindern
                    profileGrid.querySelectorAll('img').forEach((img) => {
                        img.classList.remove('selected');
                    });
                    e.target.classList.add('selected');
                    const picInput = this.form.querySelector('[name="pic_url"]');
                    if (picInput) {
                        picInput.value = e.target.dataset.filename;
                        picInput.dispatchEvent(new Event('input')); // Live Preview triggern
                    }
                }
            });
        }

        // Hauptbild Auswahl
        const mainGrid = document.getElementById('main-pic-grid');
        if (mainGrid) {
            mainGrid.addEventListener('click', (e) => {
                if (e.target.tagName === 'IMG') {
                    // LINTER FIX: Block Statement {} um impliziten Return zu verhindern
                    mainGrid.querySelectorAll('img').forEach((img) => {
                        img.classList.remove('selected');
                    });
                    e.target.classList.add('selected');
                    const mainInput = this.form.querySelector('[name="main_pic_url"]');
                    if (mainInput) {
                        mainInput.value = e.target.dataset.filename;
                        mainInput.dispatchEvent(new Event('input')); // Live Preview triggern
                    }
                }
            });
        }
    }

    // Live Previews durch Text-Eingaben
    bindLivePreviews() {
        const picUrlInput = this.form?.querySelector('[name="pic_url"]');
        const charPreviewImg = document.getElementById('char-preview-img');
        picUrlInput?.addEventListener('input', (e) => {
            const val = e.target.value.trim();
            if (charPreviewImg) {
                charPreviewImg.src = val
                    ? `${this.api.baseUrl}/assets/images/characters/profiles/${val}`
                    : 'https://placehold.co/120x120?text=Kein+Bild';
            }
        });

        const mainPicInput = this.form?.querySelector('[name="main_pic_url"]');
        const prevMain = document.getElementById('preview-img-main');
        mainPicInput?.addEventListener('input', (e) => {
            const val = e.target.value.trim();
            if (prevMain) {
                if (val) {
                    prevMain.src = `${this.api.baseUrl}/assets/images/characters/main/${val}`;
                    prevMain.style.display = 'block';
                } else {
                    prevMain.style.display = 'none';
                    prevMain.src = '';
                }
            }
        });

        const swatchPicInput = this.form?.querySelector('[name="swatch_pic_url"]');
        const prevSwatch = document.getElementById('preview-img-swatch');
        swatchPicInput?.addEventListener('input', (e) => {
            const val = e.target.value.trim();
            if (prevSwatch) {
                if (val) {
                    prevSwatch.src = `${this.api.baseUrl}/assets/images/characters/swatches/${val}`;
                    prevSwatch.style.display = 'block';
                } else {
                    prevSwatch.style.display = 'none';
                    prevSwatch.src = '';
                }
            }
        });

        const refSheetsInput = this.form?.querySelector('[name="ref_sheets_urls"]');
        const containerRefs = document.getElementById('preview-container-refs');
        refSheetsInput?.addEventListener('input', (e) => {
            if (containerRefs) {
                containerRefs.innerHTML = '';
                const vals = e.target.value
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
        });
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

                if (previewText) {
                    previewText.textContent = `Bereit: ${input.files[0].name}`;
                }

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
                this.updateRefPreviews(containerRefs, zoneRefs);
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
                this.updateRefPreviews(containerRefs, zoneRefs);
            }
        });
    }

    updateRefPreviews(containerRefs, zoneRefs) {
        window.isDirty = true;
        if (zoneRefs) {
            zoneRefs.style.borderColor = 'var(--status-green-text)';
            zoneRefs.style.backgroundColor = 'var(--status-green-bg)';
        }

        // LINTER FIX: Block Statement {} um impliziten Return zu verhindern
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

        const setVal = (nameAttr, val) => {
            const el = this.form.querySelector(`[name="${nameAttr}"]`);
            if (el) el.value = val;
        };

        setVal('id', 'new');

        // Formular leeren und Live Previews resetten
        setVal('pic_url', '');
        this.form.querySelector('[name="pic_url"]')?.dispatchEvent(new Event('input'));

        setVal('main_pic_url', '');
        this.form.querySelector('[name="main_pic_url"]')?.dispatchEvent(new Event('input'));

        setVal('swatch_pic_url', '');
        this.form.querySelector('[name="swatch_pic_url"]')?.dispatchEvent(new Event('input'));

        setVal('ref_sheets_urls', '');
        this.form.querySelector('[name="ref_sheets_urls"]')?.dispatchEvent(new Event('input'));

        if (typeof window.$ !== 'undefined' && window.$('#char_description').length) {
            window.$('#char_description').trumbowyg('empty');
        }

        // LINTER FIX: Block Statement {}
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

        // 100% ID-Kollisionssicher durch Nutzung von name="..."
        const setVal = (nameAttr, val) => {
            const el = this.form.querySelector(`[name="${nameAttr}"]`);
            if (el) el.value = val || '';
        };

        setVal('id', payload.id);
        setVal('name', payload.name);
        setVal('full_name', payload.fullName);
        setVal('alt_names', payload.altNames);
        setVal('gender', payload.gender);
        setVal('age', payload.age);
        setVal('rank', payload.rank);
        setVal('species', payload.species);
        setVal('subspecies', payload.subspecies);
        setVal('languages', payload.languages);

        // Richtige HTML ID und payload.description
        if (typeof window.$ !== 'undefined' && window.$('#char_description').length) {
            window.$('#char_description').trumbowyg('html', payload.description || '');
        }

        // Bilder im Raster markieren
        document.querySelectorAll('#profile-pic-grid img, #main-pic-grid img').forEach((img) => {
            img.classList.remove('selected');
        });

        // Felder füllen UND input Event feuern, damit die Bilder geladen werden
        setVal('pic_url', payload.picUrl);
        this.form.querySelector('[name="pic_url"]')?.dispatchEvent(new Event('input'));
        if (payload.picUrl) {
            const match = document.querySelector(
                `#profile-pic-grid img[data-filename="${payload.picUrl}"]`
            );
            if (match) match.classList.add('selected');
        }

        setVal('main_pic_url', payload.mainPic);
        this.form.querySelector('[name="main_pic_url"]')?.dispatchEvent(new Event('input'));
        if (payload.mainPic) {
            const match = document.querySelector(
                `#main-pic-grid img[data-filename="${payload.mainPic}"]`
            );
            if (match) match.classList.add('selected');
        }

        setVal('swatch_pic_url', payload.swatchPic);
        this.form.querySelector('[name="swatch_pic_url"]')?.dispatchEvent(new Event('input'));

        setVal(
            'ref_sheets_urls',
            payload.refSheets && payload.refSheets.length > 0 ? payload.refSheets.join(', ') : ''
        );
        this.form.querySelector('[name="ref_sheets_urls"]')?.dispatchEvent(new Event('input'));

        const displayId = document.getElementById('char-display-id');
        if (displayId) displayId.textContent = `ID: ${payload.id}`;

        const titleEl = document.getElementById('modal-title-char');
        if (titleEl) titleEl.textContent = 'Charakter bearbeiten';

        this.resetAllDropZones();
        sessionStorage.setItem('highlightEntityIdCancel', payload.id);

        this.modalManager.open('char-modal');
    }

    async saveCharacter(btnElement) {
        if (!this.form?.reportValidity()) return;

        const originalText = btnElement.innerHTML;
        btnElement.disabled = true;
        btnElement.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Speichere...';

        try {
            const formData = new window.FormData(this.form);

            // Richtige HTML ID für den Fallback-Speicher
            if (typeof window.$ !== 'undefined' && window.$('#char_description').length) {
                formData.set('description', window.$('#char_description').trumbowyg('html'));
            }

            const idInput = this.form.querySelector('[name="id"]');
            if (idInput) sessionStorage.setItem('highlightEntityId', idInput.value.trim());

            const result = await this.api.post('save_single_character', formData);

            if (result.success) {
                this.notifications.show(result.message, 'success');
                this.modalManager.close('char-modal');
                window.isDirty = false;
                setTimeout(() => window.location.reload(), 1000);
            } else {
                this.notifications.show(result.error, 'error');
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
            this.notifications.show(result.message, 'success');
            // Sofort DOM Element entfernen
            const row = btnElement.closest('tr');
            if (row) row.remove();
        } else {
            this.notifications.show(result.error, 'error');
        }
    }
}
