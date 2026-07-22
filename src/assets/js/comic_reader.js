document.addEventListener('DOMContentLoaded', () => {
    const comicImg = document.getElementById('comic-image');
    const comicLink = document.getElementById('comic-image-link');
    const btnToggleLang = document.getElementById('btn-toggle-lang');

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
        const swipeThreshold = 50; // Mindestdistanz für einen Swipe in Pixeln

        comicImg.addEventListener('touchstart', e => {
            touchstartX = e.changedTouches[0].screenX;
        }, { passive: true });

        comicImg.addEventListener('touchend', e => {
            touchendX = e.changedTouches[0].screenX;
            handleSwipe();
        }, { passive: true });

        function handleSwipe() {
            // Nach Links wischen = Nächste Seite
            if (touchendX < touchstartX - swipeThreshold && navNext) {
                window.location.href = navNext.href;
            }
            // Nach Rechts wischen = Vorherige Seite
            if (touchendX > touchstartX + swipeThreshold && navPrev) {
                window.location.href = navPrev.href;
            }
        }
    }

    // --- 3. SPRACHE UMSCHALTEN (DE / EN) ---
    if (btnToggleLang && comicImg) {
        const urlDeLowres = comicImg.src;
        const urlDeHires = comicLink.href;
        const originalFilename = comicImg.dataset.enOriginal;

        btnToggleLang.addEventListener('click', () => {
            const currentState = btnToggleLang.dataset.state;

            if (currentState === 'de') {
                // Wechsle zu Englisch
                btnToggleLang.innerHTML = '<i class="fa-solid fa-language"></i> Zurück zu Deutsch';
                btnToggleLang.dataset.state = 'en';

                // Nutze den CDN Link von Keenspot
                const enUrl = `https://cdn.twokinds.keenspot.com/comics/${originalFilename}`;
                comicImg.src = enUrl;
                comicLink.href = enUrl;
            } else {
                // Wechsle zu Deutsch
                btnToggleLang.innerHTML = '<i class="fa-solid fa-language"></i> Auf Englisch (Original) lesen';
                btnToggleLang.dataset.state = 'de';

                comicImg.src = urlDeLowres;
                comicLink.href = urlDeHires;
            }
        });
    }

    // --- 4. LESEZEICHEN (LOCAL STORAGE) ---
    const btnBookmark = document.getElementById('btn-toggle-bookmark');
    if (btnBookmark) {
        const comicId = btnBookmark.dataset.id;
        const comicTitle = btnBookmark.dataset.title;

        // Prüfen, ob schon gemerkt
        let bookmarks = JSON.parse(localStorage.getItem('comicBookmarks') || '{}');
        if (bookmarks[comicId]) {
            btnBookmark.classList.add('active', 'button-green');
            btnBookmark.innerHTML = '<i class="fa-solid fa-check"></i> Gemerkt';
        }

        btnBookmark.addEventListener('click', () => {
            bookmarks = JSON.parse(localStorage.getItem('comicBookmarks') || '{}');

            if (bookmarks[comicId]) {
                delete bookmarks[comicId];
                btnBookmark.classList.remove('active', 'button-green');
                btnBookmark.innerHTML = '<i class="fa-solid fa-bookmark"></i> Lesezeichen';
            } else {
                bookmarks[comicId] = {
                    title: comicTitle,
                    url: window.location.pathname,
                    date: Date.now()
                };
                btnBookmark.classList.add('active', 'button-green');
                btnBookmark.innerHTML = '<i class="fa-solid fa-check"></i> Gemerkt';
            }
            localStorage.setItem('comicBookmarks', JSON.stringify(bookmarks));
        });
    }
});
