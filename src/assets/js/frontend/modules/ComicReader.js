export class ComicReader {
    /** @param {import('../core/FrontendApi.js').FrontendApi} api */
    constructor(api) {
        this.api = api;

        this.comicImg = document.getElementById('comic-image');
        this.comicLink = document.getElementById('comic-image-link');
        this.btnToggleLang = document.getElementById('toggle-language-btn');
        this.btnBookmark = document.getElementById('add-bookmark');

        // Navigationselemente
        this.navPrev = document.querySelector('.navprev:not(.disabled)');
        this.navNext = document.querySelector('.navnext:not(.disabled)');

        this.init();
    }

    init() {
        if (this.comicImg || this.navPrev || this.navNext) {
            this.bindNavigation();
        }
        if (this.btnToggleLang && this.comicImg) {
            this.bindLangToggle();
        }
        if (this.btnBookmark) {
            this.bindBookmark();
        }
    }

    bindNavigation() {
        // 1. Tastatur-Navigation (Pfeiltasten)
        document.addEventListener('keyup', (e) => {
            if (e.key === 'ArrowLeft' && this.navPrev) window.location.href = this.navPrev.href;
            if (e.key === 'ArrowRight' && this.navNext) window.location.href = this.navNext.href;
        });

        // 2. Swipe-Navigation für Smartphones
        if (this.comicImg) {
            let touchstartX = 0;
            let touchendX = 0;
            const swipeThreshold = 50;

            this.comicImg.addEventListener('touchstart', (e) => {
                touchstartX = e.changedTouches[0].screenX;
            }, { passive: true });

            this.comicImg.addEventListener('touchend', (e) => {
                touchendX = e.changedTouches[0].screenX;

                // Nach links wischen = Nächste Seite
                if (touchendX < touchstartX - swipeThreshold && this.navNext) {
                    window.location.href = this.navNext.href;
                }
                // Nach rechts wischen = Vorherige Seite
                if (touchendX > touchstartX + swipeThreshold && this.navPrev) {
                    window.location.href = this.navPrev.href;
                }
            }, { passive: true });
        }
    }

    bindLangToggle() {
        const langText = this.btnToggleLang.querySelector('.nav-text');

        this.btnToggleLang.addEventListener('click', () => {
            if (langText.textContent === 'EN') {
                const enOriginal = this.comicImg.dataset.enOriginal;
                const enUrl = `https://cdn.twokinds.keenspot.com/comics/${enOriginal}`;

                this.comicImg.src = enUrl;
                if (this.comicLink) this.comicLink.href = enUrl;
                langText.textContent = 'DE';
            } else {
                this.comicImg.src = this.btnToggleLang.dataset.germanSrc;
                if (this.comicLink) this.comicLink.href = this.btnToggleLang.dataset.germanHref;
                langText.textContent = 'EN';
            }
        });
    }

    bindBookmark() {
        const comicId = this.btnBookmark.dataset.id;
        const isLoggedIn = this.btnBookmark.dataset.loggedIn === 'true';

        // Initialen Status aus LocalStorage prüfen
        let bookmarks = JSON.parse(localStorage.getItem('comicBookmarksMap') || '{}');
        if (bookmarks[comicId]) {
            this.btnBookmark.classList.add('bookmarked');
            this.btnBookmark.title = 'Lesezeichen entfernt';
        }

        this.btnBookmark.addEventListener('click', async () => {
            bookmarks = JSON.parse(localStorage.getItem('comicBookmarksMap') || '{}');
            const isAdding = !bookmarks[comicId];
            const action = isAdding ? 'add' : 'remove';

            // LocalStorage sofort updaten (optimistisches UI-Update)
            if (!isAdding) {
                delete bookmarks[comicId];
                this.btnBookmark.classList.remove('bookmarked');
                this.btnBookmark.title = 'Diese Seite mit Lesezeichen versehen';
            } else {
                bookmarks[comicId] = { id: comicId, added: Date.now() };
                this.btnBookmark.classList.add('bookmarked');
                this.btnBookmark.title = 'Lesezeichen entfernt';
            }
            localStorage.setItem('comicBookmarksMap', JSON.stringify(bookmarks));

            // Wenn eingeloggt, synchronisiere mit dem Server via unserer neuen FrontendApi
            if (isLoggedIn) {
                const formData = new window.FormData();
                formData.append('comic_id', comicId);
                formData.append('bookmark_action', action);

                // Wir feuern den Request ab und müssen nicht mal aufs Result warten,
                // da die UI bereits reagiert hat.
                this.api.post('toggle_bookmark', formData).catch(e => {
                    console.error('[ComicReader] Fehler beim Sync des Lesezeichens:', e);
                });
            }
        });
    }
}
