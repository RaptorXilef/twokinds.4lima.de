export class LightboxManager {
    constructor() {
        this.init();
    }

    init() {
        const hoverOverlay = document.getElementById('image-hover-overlay');
        const hoverOverlayImg = document.getElementById('hover-overlay-img');

        if (!hoverOverlay || !hoverOverlayImg) return;

        document.addEventListener('click', (e) => {
            const img = e.target.closest('.hover-zoom-trigger');
            if (img?.src && !img.src.includes('placehold.co')) {
                e.preventDefault(); // Verhindert, dass <a> Tags eine neue Seite öffnen
                hoverOverlayImg.src = img.src;
                hoverOverlay.style.display = 'flex';
            }
        });

        hoverOverlay.addEventListener('click', () => {
            hoverOverlay.style.display = 'none';
            hoverOverlayImg.src = '';
        });
    }
}
