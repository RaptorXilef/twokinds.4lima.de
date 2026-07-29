export class MediaGallery {
    constructor(api, modalManager) {
        this.api = api;
        this.modalManager = modalManager;
        this.section = document.getElementById('section-media');

        this.galChars = document.getElementById('media-gallery-characters');
        this.galComics = document.getElementById('media-gallery-comics');
        this.mediaSearchInput = document.getElementById('media-search');

        this.currentMediaTab = 'characters';
        this.currentGalleryTargetInput = null;

        const baseUrlMatch = window.location.pathname.match(/^(.*)\/admin/);
        this.baseUrl = baseUrlMatch ? baseUrlMatch[1] : '';

        if (this.section) {
            this.bindSectionEvents();
            this.loadMedia();
        }

        // Immer laden, da Modals global aufrufbar sind
        this.bindDynamicGalleryEvents();
    }

    bindSectionEvents() {
        // Tab-Steuerung
        this.section.querySelectorAll('.media-tab-btn').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                this.section.querySelectorAll('.media-tab-btn').forEach((b) => {
                    b.classList.remove('active');
                    b.classList.add('edit');
                });
                e.target.classList.remove('edit');
                e.target.classList.add('active');

                this.currentMediaTab = e.target.dataset.type;
                const viewChars = document.getElementById('media-view-characters');
                const viewComics = document.getElementById('media-view-comics');

                if (viewChars)
                    viewChars.style.display =
                        this.currentMediaTab === 'characters' ? 'block' : 'none';
                if (viewComics)
                    viewComics.style.display = this.currentMediaTab === 'comics' ? 'block' : 'none';

                this.loadMedia();
            });
        });

        // Live Filter
        this.mediaSearchInput?.addEventListener('input', () => this.applyFilter());

        // Löschen per Event Delegation
        this.section.addEventListener('click', async (e) => {
            const btn = e.target.closest('.btn-delete-gallery-item');
            if (!btn) return;

            const type = btn.dataset.type;
            const id = btn.dataset.id;

            if (type === 'character') {
                if (!confirm(`Datei ${id} wirklich löschen?`)) return;
                const fd = new window.FormData();
                fd.append('filename', id);
                await this.api.post('delete_media', fd);
            } else if (type === 'comic') {
                const check = prompt(
                    `ACHTUNG: Dies löscht physisch ALLE Varianten der Comicseite ${id}.\nTippe "${id}" zum Bestätigen:`
                );
                if (check === id) {
                    const fd = new window.FormData();
                    fd.append('comic_id', id);
                    await this.api.post('delete_comic_media', fd);
                }
            }
            this.loadMedia();
        });

        // Drag & Drop Upload
        const mediaDropZone = document.getElementById('media-drop-zone');
        const mediaUploadInput = document.getElementById('media-upload-input');

        if (mediaDropZone && mediaUploadInput) {
            mediaDropZone.addEventListener('click', () => mediaUploadInput.click());

            mediaDropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                mediaDropZone.style.backgroundColor = 'var(--table-row-hover)';
            });

            mediaDropZone.addEventListener('dragleave', (e) => {
                e.preventDefault();
                mediaDropZone.style.backgroundColor = 'var(--table-row-even)';
            });

            mediaDropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                mediaDropZone.style.backgroundColor = 'var(--table-row-even)';
                if (e.dataTransfer.files.length) {
                    mediaUploadInput.files = e.dataTransfer.files;
                    mediaUploadInput.dispatchEvent(new Event('change'));
                }
            });

            mediaUploadInput.addEventListener('change', async () => {
                if (mediaUploadInput.files.length === 0) return;
                this.api.showStatus('Lade Bilder hoch...', 'info');

                const fd = new window.FormData();
                for (const file of mediaUploadInput.files) {
                    fd.append('files[]', file);
                }

                const json = await this.api.post('upload_media', fd);
                if (json.success) {
                    this.api.showStatus(json.message, 'success');
                    this.loadMedia();
                } else {
                    this.api.showStatus(json.error, 'error');
                }
                mediaUploadInput.value = '';
            });
        }
    }

    applyFilter() {
        if (!this.mediaSearchInput) return;
        const term = this.mediaSearchInput.value.toLowerCase().trim();
        const activeGallery =
            this.currentMediaTab === 'characters' ? this.galChars : this.galComics;

        if (activeGallery) {
            activeGallery.querySelectorAll('.preview-box').forEach((box) => {
                const searchable = box.dataset.search || '';
                box.style.display = searchable.includes(term) ? 'block' : 'none';
            });
        }
    }

    async loadMedia() {
        try {
            if (this.currentMediaTab === 'characters' && this.galChars) {
                const json = await this.api.get('list_media');
                if (json.files) {
                    this.galChars.innerHTML = json.files
                        .map(
                            (f) => `
                        <div class="preview-box" data-search="${f.filename.toLowerCase()}" style="position: relative;">
                            <img src="${f.url}" loading="lazy" style="width: 100%; height: 140px; object-fit: cover; border-radius: 4px;">
                            <p style="font-size: 0.7em; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin: 8px 0;" title="${f.filename}">${f.filename}</p>
                            <button type="button" class="button delete btn-delete-gallery-item" data-type="character" data-id="${f.filename}" style="width: 100%; padding: 5px;"><i class="fa-solid fa-trash"></i> Löschen</button>
                        </div>
                    `
                        )
                        .join('');
                }
            } else if (this.currentMediaTab === 'comics' && this.galComics) {
                const json = await this.api.get('list_comic_media');
                if (json.files) {
                    this.galComics.innerHTML = json.files
                        .map(
                            (f) => `
                        <div class="preview-box" data-search="${f.id.toLowerCase()}" style="position: relative; border: 2px solid var(--border-medium);">
                            <img src="${f.url}" loading="lazy" style="width: 100%; height: 225px; object-fit: contain; border-radius: 4px; background: #fff;">
                            <h4 style="margin: 8px 0 4px 0;" class="mono">${f.id}</h4>
                            <div style="display: flex; gap: 4px; justify-content: center; margin-bottom: 8px; font-size: 0.75em; color: var(--text-color-faded);">
                                <span style="color: ${f.has_hires ? 'var(--status-green-text)' : 'inherit'}">HR</span> |
                                <span style="color: ${f.has_lowres ? 'var(--status-green-text)' : 'inherit'}">LR</span> |
                                <span style="color: ${f.has_social ? 'var(--status-green-text)' : 'inherit'}">SM</span> |
                                <span style="color: ${f.has_thumb ? 'var(--status-green-text)' : 'inherit'}">TN</span>
                            </div>
                            <button type="button" class="button delete btn-delete-gallery-item" data-type="comic" data-id="${f.id}" style="width: 100%; padding: 5px;"><i class="fa-solid fa-trash"></i> Alle 4 löschen</button>
                        </div>
                    `
                        )
                        .join('');
                }
            }
            this.applyFilter();
        } catch (_err) {
            console.error('[Media Gallery] Failed to load media list.');
        }
    }

    bindDynamicGalleryEvents() {
        document.addEventListener('click', async (e) => {
            const btnOpenGallery = e.target.closest('.btn-open-gallery-dynamic');
            const btnCloseGallery = e.target.closest('.btn-close-gallery-modal');

            if (btnCloseGallery) {
                e.preventDefault();
                this.modalManager.close('gallery-modal');
                return;
            }

            if (btnOpenGallery) {
                e.preventDefault();
                const targetId = btnOpenGallery.dataset.target;
                const folder = btnOpenGallery.dataset.folder;
                this.currentGalleryTargetInput = document.getElementById(targetId);

                const modalTitle = document.getElementById('gallery-modal-title');
                if (modalTitle) modalTitle.textContent = `Galerie: ${folder}`;

                const galGrid = document.getElementById('gallery-grid-dynamic');
                if (galGrid) galGrid.innerHTML = '<p>Lade Bilder...</p>';
                this.modalManager.open('gallery-modal');

                try {
                    const json = await this.api.get('list_media', `folder=${folder}`);

                    if (json.files && json.files.length === 0) {
                        if (galGrid)
                            galGrid.innerHTML =
                                '<p style="font-style:italic;">Ordner ist leer.</p>';
                        return;
                    }

                    if (galGrid) {
                        galGrid.innerHTML = json.files
                            .map(
                                (f) => `
                            <div class="char-selection-item gallery-item" data-filename="${f.filename}" style="position: relative;">
                                <img src="${f.url}" loading="lazy" style="width:60px;height:60px;object-fit:cover;border-radius:5px;">
                                <span style="font-size: 0.75em; word-break: break-all;">${f.filename}</span>
                            </div>
                        `
                            )
                            .join('');

                        galGrid.querySelectorAll('.gallery-item').forEach((item) => {
                            item.addEventListener('click', () => {
                                window.isDirty = true;
                                if (this.currentGalleryTargetInput) {
                                    if (this.currentGalleryTargetInput.id === 'ref_sheets_urls') {
                                        const vals = this.currentGalleryTargetInput.value
                                            .split(',')
                                            .map((s) => s.trim())
                                            .filter(Boolean);
                                        if (!vals.includes(item.dataset.filename))
                                            vals.push(item.dataset.filename);
                                        this.currentGalleryTargetInput.value = vals.join(', ');
                                    } else {
                                        this.currentGalleryTargetInput.value =
                                            item.dataset.filename;
                                    }
                                    this.currentGalleryTargetInput.dispatchEvent(
                                        new Event('input')
                                    );
                                }
                                this.modalManager.close('gallery-modal');
                            });
                        });
                    }
                } catch (_err) {
                    if (galGrid)
                        galGrid.innerHTML =
                            '<p style="color:red;">Fehler beim Laden der Galerie.</p>';
                }
            }
        });
    }
}
