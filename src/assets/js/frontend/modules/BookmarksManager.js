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
            this.syncApi('check');
        } else {
            this.renderBookmarks();
        }
    }

    getLocalIds() {
        const map = JSON.parse(localStorage.getItem('comicBookmarksMap') || '{}');
        return Object.keys(map);
    }

    applyCloudToLocal(ids) {
        const newMap = {};
        const now = Date.now();
        ids.forEach((id) => {
            newMap[id] = { id: id, added: now };
        });
        localStorage.setItem('comicBookmarksMap', JSON.stringify(newMap));
        this.renderBookmarks();
    }

    async syncApi(resolution) {
        const fd = new window.FormData();
        fd.append('resolution', resolution);
        fd.append('local_ids', JSON.stringify(this.getLocalIds()));

        const json = await this.api.post('sync_bookmarks', fd);
        if (json.success) {
            if (json.status === 'conflict') {
                this.conflictModal.style.display = 'block';
                document.getElementById('sync-cloud-count').textContent = json.db_ids.length;
                document.getElementById('sync-local-count').textContent = json.local_ids.length;
            } else if (json.status === 'resolved' || json.status === 'synced') {
                this.applyCloudToLocal(json.final_ids);
            }
        }
    }

    async resolveSync(resolution) {
        this.conflictModal.style.display = 'none';
        await this.syncApi(resolution);
    }

    async removeBookmark(id) {
        if (this.canUseCloudSync) {
            const fd = new window.FormData();
            fd.append('comic_id', id);
            fd.append('bookmark_action', 'remove');
            await this.api.post('toggle_bookmark', fd);
        }
        const map = JSON.parse(localStorage.getItem('comicBookmarksMap') || '{}');
        delete map[id];
        localStorage.setItem('comicBookmarksMap', JSON.stringify(map));
        this.renderBookmarks();
    }

    renderBookmarks() {
        const map = JSON.parse(localStorage.getItem('comicBookmarksMap') || '{}');
        const bookmarks = Object.values(map).filter((bm) => this.serverComics[bm.id]);

        this.countBadge.textContent = bookmarks.length;

        if (bookmarks.length === 0) {
            this.contentWrapper.style.display = 'none';
            this.noMsg.style.display = 'block';
            return;
        }

        this.contentWrapper.style.display = 'block';
        this.noMsg.style.display = 'none';

        const sortMode = this.sortSelect ? this.sortSelect.value : 'added_desc';

        bookmarks.sort((a, b) => {
            if (sortMode === 'added_desc') return (b.added || 0) - (a.added || 0);
            if (sortMode === 'page_desc') return b.id.localeCompare(a.id);
            if (sortMode === 'page_asc') return a.id.localeCompare(b.id);
            return 0;
        });

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
                <a href="${db.permalink}" style="display: block; width: 100%; aspect-ratio: 1 / 1.35; overflow: hidden; background: var(--page-bg);">
                    <img src="${db.thumb}" alt="${db.title}" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">
                </a>
                <div style="padding: 12px 10px; text-align: center; flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        ${typeBadge}
                        <div style="font-weight: bold; font-size: 0.95em; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--text-color);">${db.title}</div>
                        <div style="font-size: 0.85em; color: var(--text-color-light); margin-bottom: 8px;">Kapitel ${db.chapter} <span style="opacity: 0.5;">|</span> ${db.date}</div>
                    </div>
                </div>
                <button class="delete-btn" style="position: absolute; top: 5px; right: 5px; width: 28px; height: 28px; border-radius: 50%; border: 1px solid var(--border-medium); background: rgba(255,255,255,0.9); color: var(--status-red-text); cursor: pointer;" title="Lesezeichen löschen">
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
            this.conflictModal.style.display = 'none';
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
