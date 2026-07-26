document.addEventListener('DOMContentLoaded', () => {
    const comicImg = document.getElementById('comic-image');
    const comicLink = document.getElementById('comic-image-link');
    const btnToggleLang = document.getElementById('toggle-language-btn');
    const btnBookmark = document.getElementById('add-bookmark');

    const navPrev = document.querySelector('.navprev:not(.disabled)');
    const navNext = document.querySelector('.navnext:not(.disabled)');

    // --- 1. KEYBOARD NAVIGATION ---
    document.addEventListener('keyup', (e) => {
        if (e.key === 'ArrowLeft' && navPrev) window.location.href = navPrev.href;
        if (e.key === 'ArrowRight' && navNext) window.location.href = navNext.href;
    });

    // --- 2. SWIPE GESTEN (SMARTPHONE) ---
    if (comicImg) {
        let touchstartX = 0;
        let touchendX = 0;
        const swipeThreshold = 50;

        comicImg.addEventListener(
            'touchstart',
            (e) => {
                touchstartX = e.changedTouches[0].screenX;
            },
            { passive: true }
        );

        comicImg.addEventListener(
            'touchend',
            (e) => {
                touchendX = e.changedTouches[0].screenX;
                handleSwipe();
            },
            { passive: true }
        );

        function handleSwipe() {
            if (touchendX < touchstartX - swipeThreshold && navNext) {
                window.location.href = navNext.href;
            }
            if (touchendX > touchstartX + swipeThreshold && navPrev) {
                window.location.href = navPrev.href;
            }
        }
    }

    // --- 3. SPRACHE UMSCHALTEN (DE / EN) ---
    if (btnToggleLang && comicImg) {
        const langText = btnToggleLang.querySelector('.nav-text');

        btnToggleLang.addEventListener('click', () => {
            // Wenn aktuell DE angezeigt wird (Button zeigt "EN"), wechsle zu EN
            if (langText.textContent === 'EN') {
                const enOriginal = comicImg.dataset.enOriginal;
                const enUrl = `https://cdn.twokinds.keenspot.com/comics/${enOriginal}`;

                comicImg.src = enUrl;
                comicLink.href = enUrl;
                langText.textContent = 'DE';
            } else {
                // Zurück zu Deutsch
                comicImg.src = btnToggleLang.dataset.germanSrc;
                comicLink.href = btnToggleLang.dataset.germanHref;
                langText.textContent = 'EN';
            }
        });
    }

    // --- 4. LESEZEICHEN (LOCAL STORAGE) ---
    if (btnBookmark) {
        const comicId = btnBookmark.dataset.id;
        const isLoggedIn = btnBookmark.dataset.loggedIn === 'true';
        const csrfToken = btnBookmark.dataset.csrf || '';

        // Modernes Objekt-Format für LocalStorage (vorwärtskompatibel zu deinem alten Array-System)
        let bookmarks = JSON.parse(localStorage.getItem('comicBookmarksMap') || '{}');

        // Beim Laden prüfen
        if (bookmarks[comicId]) {
            btnBookmark.classList.add('bookmarked');
            btnBookmark.title = 'Lesezeichen entfernt';
        }

        btnBookmark.addEventListener('click', async () => {
            bookmarks = JSON.parse(localStorage.getItem('comicBookmarksMap') || '{}');
            const isAdding = !bookmarks[comicId];
            const action = isAdding ? 'add' : 'remove';

            // 1. Lokales Update (Sorgt für flüssiges UI, ohne auf den Server zu warten)
            if (!isAdding) {
                delete bookmarks[comicId];
                btnBookmark.classList.remove('bookmarked');
                btnBookmark.title = 'Diese Seite mit Lesezeichen versehen';
            } else {
                // Extrem schlankes Speicherobjekt
                bookmarks[comicId] = {
                    id: comicId,
                    added: Date.now(),
                };
                btnBookmark.classList.add('bookmarked');
                btnBookmark.title = 'Lesezeichen entfernt';
            }
            localStorage.setItem('comicBookmarksMap', JSON.stringify(bookmarks));

            // 2. Cloud Update (Async im Hintergrund)
            if (isLoggedIn) {
                const fd = new FormData();
                fd.append('comic_id', comicId);
                fd.append('bookmark_action', action);
                fd.append('csrf_token', csrfToken);

                try {
                    await fetch(window.location.origin + '/api/toggle_bookmark', {
                        method: 'POST',
                        body: fd,
                    });
                } catch (e) {
                    console.error('Bookmark sync failed', e);
                }
            }
        });
    }
});
