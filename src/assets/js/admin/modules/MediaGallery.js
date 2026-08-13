import { DragDropService } from '../ui/DragDropService.js';
import { debounce } from '../utils/Utils.js';

/**
 * @typedef {import('../core/Api.js').Api} Api
 * @typedef {import('../ui/ModalManager.js').ModalManager} ModalManager
 * @typedef {import('../core/NotificationService.js').NotificationService} NotificationService
 */
export class MediaGallery {
    /**
     * @param {Api} api
     * @param {ModalManager} modalManager
     * @param {NotificationService} notifications
     */
    constructor(api, modalManager, notifications, tracker) {
        this.api = api;
        this.modalManager = modalManager;
        this.notifications = notifications;
        this.tracker = tracker;

        this.currentMediaTab = 'characters';
        this.currentGalleryTargetInput = null;
        this.isTabInitialized = false;

        // Cache für die aktuelle Auswahl im Modal
        this.selectedGalleryItems = new Set();
        this.isMultiSelect = false;

        // Binde die Modal-Ereignisse IMMER sofort, egal welcher Tab offen ist!
        this.bindDynamicGalleryEvents();
    }

    /**
     * Wird nur aufgerufen, wenn der Benutzer auch wirklich den Galerie-Tab öffnet
     */
    initTab() {
        this.section = document.getElementById('section-media');
        /** @type {HTMLElement|null} */
        this.galChars = document.getElementById('media-gallery-characters');
        /** @type {HTMLElement|null} */
        this.galComics = document.getElementById('media-gallery-comics');
        /** @type {HTMLInputElement|null} */
        this.mediaSearchInput = document.getElementById('media-search');

        if (this.section && this.galChars && !this.isTabInitialized) {
            this.isTabInitialized = true;

            // PERFORMANCE BOOST: Debounced Live-Suche
            this.applyFilterDebounced = debounce(() => this.applyFilter(), 250);
            this.bindSectionEvents();
            this.loadMedia(); // Lädt die Bilder ressourcenschonend erst hier
        }
    }

    bindSectionEvents() {
        this.mediaSearchInput?.addEventListener('input', this.applyFilterDebounced);

        // Alle Klicks delegieren (Tabs & Löschen-Buttons)
        this.section.addEventListener('click', async (e) => {
            const tabBtn = e.target.closest('.media-tab-btn');
            const deleteBtn = e.target.closest('.btn-delete-gallery-item');

            if (tabBtn) {
                e.preventDefault();
                this.section.querySelectorAll('.media-tab-btn').forEach((b) => {
                    b.classList.remove('active');
                    b.classList.add('edit');
                });
                tabBtn.classList.remove('edit');
                tabBtn.classList.add('active');

                this.currentMediaTab = tabBtn.dataset.type;

                const viewChars = document.getElementById('media-view-characters');
                const viewComics = document.getElementById('media-view-comics');

                if (viewChars)
                    viewChars.style.display =
                        this.currentMediaTab === 'characters' ? 'block' : 'none';
                if (viewComics)
                    viewComics.style.display = this.currentMediaTab === 'comics' ? 'block' : 'none';

                this.loadMedia();
                return;
            }

            if (deleteBtn) {
                e.preventDefault();
                const type = deleteBtn.dataset.type;
                const id = deleteBtn.dataset.id;

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
            }
        });

        DragDropService.bind('media-drop-zone', 'media-upload-input', {
            onChange: async (files) => {
                DragDropService.reset('media-drop-zone');
                if (files.length === 0) return;

                this.notifications.show('Lade Bilder hoch...', 'info');

                const fd = new window.FormData();
                for (const file of files) {
                    fd.append('files[]', file);
                }

                const json = await this.api.post('upload_media', fd);

                if (json.success) {
                    this.notifications.show(json.message, 'success');
                    this.loadMedia();
                } else {
                    this.notifications.show(json.error, 'error');
                }

                const uploadInput = document.getElementById('media-upload-input');
                if (uploadInput) uploadInput.value = '';
            },
        });
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
            const btnConfirmGallery = e.target.closest('#btn-gallery-confirm');

            if (btnCloseGallery) {
                e.preventDefault();
                this.modalManager.close('gallery-modal');
                return;
            }

            // Neu: Bestätigen Button übernimmt die Auswahl gebündelt und schreibt sie HART ins Feld
            if (btnConfirmGallery) {
                e.preventDefault();
                if (this.currentGalleryTargetInput) {
                    if (this.tracker) this.tracker.markDirty();

                    const finalValue = Array.from(this.selectedGalleryItems).join(', ');

                    // Schreibe alle markierten Elemente als kommagetrennten String in das Input
                    this.currentGalleryTargetInput.value = finalValue;

                    // Triggere Input-Events für das Live-Preview Update im Modal (CharacterEditor.js fängt das ab)
                    this.currentGalleryTargetInput.dispatchEvent(
                        new Event('input', { bubbles: true })
                    );
                    this.currentGalleryTargetInput.dispatchEvent(
                        new Event('change', { bubbles: true })
                    );
                }
                this.modalManager.close('gallery-modal');
                return;
            }

            // Öffnen der Galerie
            if (btnOpenGallery) {
                e.preventDefault();
                const targetId = btnOpenGallery.dataset.target;
                const folder = btnOpenGallery.dataset.folder;

                // Prüfen ob Mehrfachauswahl erlaubt ist (RefSheets)
                this.isMultiSelect = btnOpenGallery.dataset.multi === 'true';
                this.currentGalleryTargetInput = document.getElementById(targetId);

                const modalTitle = document.getElementById('gallery-modal-title');
                if (modalTitle) modalTitle.textContent = `Galerie: ${folder}`;

                const galGrid = document.getElementById('gallery-grid-dynamic');
                if (galGrid) galGrid.innerHTML = '<p>Lade Bilder...</p>';

                // Aktuelle Auswahl auslesen und im Set cachen
                this.selectedGalleryItems.clear();
                if (this.currentGalleryTargetInput && this.currentGalleryTargetInput.value) {
                    const vals = this.currentGalleryTargetInput.value
                        .split(',')
                        .map((s) => s.trim())
                        .filter(Boolean);
                    vals.forEach((v) => this.selectedGalleryItems.add(v));
                }

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
                        // Markiere Bilder, die bereits im Input-Feld stehen
                        galGrid.innerHTML = json.files
                            .map((f) => {
                                const isSelected = this.selectedGalleryItems.has(f.filename);
                                return `
                                <div class="char-selection-item gallery-item ${isSelected ? 'selected' : ''}" data-filename="${f.filename}" style="position: relative;">
                                    <img src="${f.url}" loading="lazy" style="width:60px;height:60px;object-fit:cover;border-radius:5px;">
                                    <span style="font-size: 0.75em; word-break: break-all; pointer-events: none;">${f.filename}</span>
                                </div>
                            `;
                            })
                            .join('');

                        // Klick-Logik zum Markieren/Demarkieren
                        galGrid.querySelectorAll('.gallery-item').forEach((item) => {
                            item.addEventListener('click', (ev) => {
                                ev.preventDefault(); // Stoppt eventuelles Bubbling von Child-Elementen
                                ev.stopPropagation(); // SICHERHEIT: Verhindert, dass der ComicEditor dazwischenfunkt!

                                const filename = item.dataset.filename;

                                if (this.selectedGalleryItems.has(filename)) {
                                    // Abwählen wenn schon markiert
                                    this.selectedGalleryItems.delete(filename);
                                    item.classList.remove('selected');
                                } else {
                                    // Bei Single-Select vorher alle anderen abwählen
                                    if (!this.isMultiSelect) {
                                        this.selectedGalleryItems.clear();
                                        galGrid
                                            .querySelectorAll('.gallery-item')
                                            .forEach((el) => el.classList.remove('selected'));
                                    }
                                    // Neues Bild markieren
                                    this.selectedGalleryItems.add(filename);
                                    item.classList.add('selected');
                                }
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
