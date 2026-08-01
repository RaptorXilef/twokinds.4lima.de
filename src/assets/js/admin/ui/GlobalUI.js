export class GlobalUI {
    constructor(tracker) {
        this.tracker = tracker;
        this.bindImageFallback();
        this.initWysiwyg();
        this.initLightbox();
    }

    bindImageFallback() {
        document.addEventListener(
            'error',
            (e) => {
                if (
                    e.target &&
                    e.target.tagName === 'IMG' &&
                    e.target.classList.contains('hide-on-error')
                ) {
                    e.target.style.display = 'none';
                }
            },
            true
        );
    }

    initWysiwyg() {
        try {
            if (typeof window.$ !== 'undefined' && typeof window.$.fn.trumbowyg !== 'undefined') {
                window.$.trumbowyg.svgPath =
                    'https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/ui/icons.svg';
                window
                    .$('.wysiwyg-editor')
                    .trumbowyg({
                        lang: 'de',
                        btns: [
                            ['viewHTML'],
                            ['undo', 'redo'],
                            ['formatting'],
                            ['strong', 'em', 'del'],
                            ['link'],
                            ['insertImage'],
                            ['unorderedList', 'orderedList'],
                            ['removeformat'],
                        ],
                    })
                    .on('tbwchange', () => {
                        if (this.tracker) this.tracker.markDirty();
                    });
            }
        } catch (err) {
            console.error('[GlobalUI] Trumbowyg WYSIWYG konnte nicht initialisiert werden:', err);
        }
    }

    initLightbox() {
        const hoverOverlay = document.getElementById('image-hover-overlay');
        const hoverOverlayImg = document.getElementById('hover-overlay-img');

        document.addEventListener('click', (e) => {
            const img = e.target.closest('.hover-zoom-trigger');
            if (img?.src && !img.src.includes('placehold.co')) {
                if (hoverOverlayImg && hoverOverlay) {
                    hoverOverlayImg.src = img.src;
                    hoverOverlay.style.display = 'flex';
                }
            }
        });

        hoverOverlay?.addEventListener('click', () => {
            hoverOverlay.style.display = 'none';
            if (hoverOverlayImg) hoverOverlayImg.src = '';
        });
    }

    handleRowHighlighting() {
        let highlightId = null;
        try {
            highlightId = sessionStorage.getItem('highlightEntityId');
        } catch (err) {
            console.warn('[GlobalUI] Kann SessionStorage nicht lesen:', err);
            return;
        }

        if (!highlightId) return;

        const targetBtn =
            document.querySelector(`.btn-delete-comic[data-id="${highlightId}"]`) ??
            document.querySelector(`.btn-delete-char[data-id="${highlightId}"]`);
        const tr = targetBtn?.closest('tr');

        if (tr) {
            tr.scrollIntoView({ behavior: 'smooth', block: 'center' });
            tr.classList.add('row-highlight');
            setTimeout(() => {
                tr.classList.remove('row-highlight');
            }, 3000);

            try {
                // Erst löschen, wenn es WIRKLICH auf dieser Seite zu sehen war!
                sessionStorage.removeItem('highlightEntityId');
            } catch (err) {
                console.warn('[GlobalUI] Kann SessionStorage nicht bereinigen:', err);
            }
        }
    }
}
