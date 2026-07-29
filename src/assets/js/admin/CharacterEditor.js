export class CharacterEditor {
    constructor(api, modalManager) {
        this.api = api;
        this.modalManager = modalManager;

        this.section = document.getElementById('section-characters');
        this.form = document.getElementById('char-form');
        this.accumulatedRefFiles = new DataTransfer(); // FIX: Speicher für Ref-Sheets

        if (this.section || this.form) {
            this.bindEvents();
            this.bindImageSelection();
            this.bindDropZones(); // FIX: Drag & Drop aktivieren
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
                    const picInput = this.form.querySelector('[name="pic_url"]');
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
                    const mainInput = this.form.querySelector('[name="main_pic_url"]');
                    if (mainInput) mainInput.value = e.target.dataset.filename;
                }
            });
        }
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
        setVal('pic_url', '');
        setVal('main_pic_url', '');

        // Richtige HTML ID für das Textfeld
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

        this.resetAllDropZones(); // Reset
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

        const displayId = document.getElementById('char-display-id');
        if (displayId) displayId.textContent = `ID: ${payload.id}`;

        const titleEl = document.getElementById('modal-title-char');
        if (titleEl) titleEl.textContent = 'Charakter bearbeiten';

        this.resetAllDropZones(); // FIX: Reset
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

            const result = await this.api.post('save_character', formData);

            if (result.success) {
                this.api.showStatus(result.message, 'success');
                this.modalManager.close('char-modal');
                window.isDirty = false;
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
