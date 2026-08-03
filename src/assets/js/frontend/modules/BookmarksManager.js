export class BookmarksManager {
    /** @param {import('../core/FrontendApi.js').FrontendApi} api */
    constructor(api) {
        this.api = api;

        // Prüfen, ob wir auf der Lesezeichen-Seite sind (indem wir nach dem Daten-Block suchen)
        const dataElement = document.getElementById('bookmarks-init-data');
        if (!dataElement) return;

        try {
            const initData = JSON.parse(dataElement.textContent);
            this.serverComics = initData.serverComics;
            this.canUseCloudSync = initData.canUseCloudSync;
            this.cloudIds = initData.cloudIds;
        } catch (e) {
            console.error('[BookmarksManager] Fehler beim Parsen der Init-Daten', e);
            return;
        }

        // DOM Elemente
        this.grid = document.getElementById('bookmarks-grid');
        this.noMsg = document.getElementById('no-bookmarks-msg');
        this.contentWrapper = document.getElementById('bookmarks-content');
        this.countBadge = document.getElementById('bookmark-count-badge');
        this.btnClearAll = document.getElementById('btn-clear-all-bookmarks');
        this.sortSelect = document.getElementById('bookmark-sort');
        this.conflictModal = document.getElementById('sync-conflict-modal');

        this.init();
    }

    init() {
        this.bindEvents();

        if (this.canUseCloudSync) {
            // Wenn der Nutzer eingeloggt ist, prüfe auf Sync-Konflikte
            this.syncApi('check');
        } else {
            // Ansonsten zeige sofort die lokalen Lesezeichen an
            this.renderBookmarks();
        }
    }

    // --- HILFSFUNKTIONEN ---
    getSafeStorage() {
        try {
            return JSON.parse(localStorage.getItem('comicBookmarksMap') || '{}');
        } catch (err) {
            console.warn('[BookmarksManager] LocalStorage blockiert oder defekt.', err);
            return {};
        }
    }

    setSafeStorage(data) {
        try {
            localStorage.setItem('comicBookmarksMap', JSON.stringify(data));
        } catch (err) {
            console.error(
                '[BookmarksManager] Konnte Lesezeichen nicht speichern (Speicher voll/blockiert).',
                err
            );
        }
    }

    // --- METHODEN ---
    getLocalIds() {
        return Object.keys(this.getSafeStorage());
    }

    applyCloudToLocal(ids) {
        const newMap = {};
        const now = Date.now();
        const oldMap = this.getSafeStorage();

        // Wir erhalten die reinen IDs vom Server. Um die Sortierung beizubehalten,
        // übernehmen wir (falls vorhanden) den alten Zeitstempel aus dem LocalStorage.
        ids.forEach((id) => {
            if (oldMap[id]) {
                newMap[id] = oldMap[id];
            } else {
                newMap[id] = { id: id, added: now };
            }
        });

        this.setSafeStorage(newMap);
        this.renderBookmarks();
    }

    async syncApi(resolution) {
        try {
            const localIds = this.getLocalIds();
            const fd = new window.FormData();
            fd.append('local_ids', JSON.stringify(localIds));
            fd.append('resolution', resolution); // 'check', 'merge', 'local_wins', 'db_wins'

            const result = await this.api.post('sync_bookmarks', fd);

            if (result.success) {
                if (result.status === 'synced' || result.status === 'resolved') {
                    // Sync war erfolgreich (oder wurde gelöst), wende Server-Daten auf Browser an
                    this.applyCloudToLocal(result.final_ids);
                    if (this.conflictModal) this.conflictModal.style.display = 'none';
                } else if (result.status === 'conflict') {
                    // Konflikt erkannt -> Zeige das Auswahlfenster
                    document.getElementById('sync-cloud-count').textContent = result.db_ids.length;
                    document.getElementById('sync-local-count').textContent =
                        result.local_ids.length;
                    if (this.conflictModal) this.conflictModal.style.display = 'flex';
                }
            } else {
                console.error('[BookmarksManager] Sync API Fehler:', result.error);
                this.renderBookmarks(); // Fallback
            }
        } catch (err) {
            console.error('[BookmarksManager] Sync fehlgeschlagen:', err);
            this.renderBookmarks(); // Fallback auf rein lokales Rendern
        }
    }

    resolveSync(resolution) {
        const btnId =
            'btn-sync-' +
            (resolution === 'db_wins' ? 'db' : resolution === 'local_wins' ? 'local' : 'merge');
        const btn = document.getElementById(btnId);

        const originalText = btn ? btn.innerHTML : '';
        if (btn) {
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verarbeite...';
            btn.disabled = true;
        }

        this.syncApi(resolution).finally(() => {
            if (btn) {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });
    }

    async removeBookmark(id) {
        if (this.canUseCloudSync) {
            const fd = new window.FormData();
            fd.append('comic_id', id);
            fd.append('bookmark_action', 'remove');
            await this.api.post('toggle_bookmark', fd);
        }
        const map = this.getSafeStorage();
        delete map[id];
        this.setSafeStorage(map);
        this.renderBookmarks();
    }

    renderBookmarks() {
        const map = this.getSafeStorage();
        const bookmarks = Object.values(map).filter((bm) => this.serverComics[bm.id]);

        if (this.countBadge) this.countBadge.textContent = bookmarks.length;

        if (bookmarks.length === 0) {
            if (this.contentWrapper) this.contentWrapper.style.display = 'none';
            if (this.noMsg) this.noMsg.style.display = 'block';
            return;
        }

        if (this.contentWrapper) this.contentWrapper.style.display = 'block';
        if (this.noMsg) this.noMsg.style.display = 'none';
        if (this.conflictModal) this.conflictModal.style.display = 'none';

        const sortMode = this.sortSelect ? this.sortSelect.value : 'added_desc';

        bookmarks.sort((a, b) => {
            if (sortMode === 'added_desc') return (b.added || 0) - (a.added || 0);
            if (sortMode === 'page_desc') return b.id.localeCompare(a.id);
            if (sortMode === 'page_asc') return a.id.localeCompare(b.id);
            return 0;
        });

        if (!this.grid) return;

        this.grid.innerHTML = '';

        bookmarks.forEach((bm) => {
            const db = this.serverComics[bm.id];
            const card = document.createElement('div');
            card.className = 'bookmark-card';

            const isComicPage = db.type.toLowerCase() !== 'comicseite';
            const typeBadge = isComicPage
                ? `<span style="font-size: 0.75em; font-weight: bold; background: var(--status-info-bg); color: var(--status-info-text); border: 1px solid var(--status-info-border); padding: 2px 8px; border-radius: 12px; margin-bottom: 5px; display: inline-block; text-transform: uppercase;">${db.type}</span><br>`
                : '';

            card.innerHTML = `
                <a href="${db.permalink}" class="bookmark-thumb-link">
                    <img src="${db.thumb}" alt="${db.title}" loading="lazy" class="bookmark-thumb-img">
                </a>
                <div class="bookmark-card-body">
                    <div>
                        ${typeBadge}
                        <div class="bookmark-title">${db.title}</div>
                        <div class="bookmark-meta">Kapitel ${db.chapter} <span style="opacity: 0.5;">|</span> ${db.date}</div>
                    </div>
                </div>
                <button class="bookmark-delete-btn delete-btn" title="Lesezeichen löschen">
                    <i class="fa-solid fa-times"></i>
                </button>
            `;

            card.querySelector('.delete-btn').addEventListener('click', (e) => {
                e.preventDefault();
                this.removeBookmark(bm.id);
            });

            this.grid.appendChild(card);
        });
    }

    bindEvents() {
        if (this.sortSelect) {
            this.sortSelect.addEventListener('change', () => this.renderBookmarks());
        }

        // Modal-Buttons zur Konfliktlösung
        document
            .getElementById('btn-sync-merge')
            ?.addEventListener('click', () => this.resolveSync('merge'));
        document
            .getElementById('btn-sync-db')
            ?.addEventListener('click', () => this.resolveSync('db_wins'));
        document
            .getElementById('btn-sync-local')
            ?.addEventListener('click', () => this.resolveSync('local_wins'));

        document.getElementById('btn-sync-ignore')?.addEventListener('click', () => {
            if (this.conflictModal) this.conflictModal.style.display = 'none';
            this.renderBookmarks(); // Bei 'Ignorieren' einfach den lokalen Status rendern
        });

        if (this.btnClearAll) {
            this.btnClearAll.addEventListener('click', async () => {
                if (confirm('Möchtest du wirklich ALLE Lesezeichen unwiderruflich löschen?')) {
                    if (this.canUseCloudSync) {
                        const fd = new window.FormData();
                        fd.append('resolution', 'local_wins');
                        fd.append('local_ids', '[]');
                        await this.api.post('sync_bookmarks', fd);
                    }
                    localStorage.removeItem('comicBookmarksMap');
                    this.renderBookmarks();
                }
            });
        }
    }
}
