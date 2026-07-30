export class ImageFallback {
    constructor() {
        this.bindEvents();
    }

    bindEvents() {
        // Fallback für alle Bilder
        document.querySelectorAll('img').forEach((img) => {
            img.addEventListener('error', function () {
                if (!this.dataset.fallbackApplied) {
                    this.dataset.fallbackApplied = 'true';
                    this.src =
                        'https://placehold.co/800x600/cccccc/333333?text=Bild+nicht+gefunden';
                }
            });
        });

        // Spezieller Fallback für Comic-Seiten (klickbar)
        const mainComicImage = document.getElementById('comic-image');
        if (mainComicImage) {
            mainComicImage.addEventListener('error', function () {
                this.src = 'https://placehold.co/800x600/cccccc/333333?text=Bild+Fehler';
                if (this.parentElement.tagName === 'A') {
                    this.parentElement.href = '#';
                }
            });
        }

        // Lazy-Load Animation für Charakter-Avatare
        document.addEventListener(
            'load',
            (e) => {
                if (e.target && e.target.tagName === 'IMG' && e.target.closest('.character-item')) {
                    e.target.classList.add('loaded');
                }
            },
            true
        );
    }
}
