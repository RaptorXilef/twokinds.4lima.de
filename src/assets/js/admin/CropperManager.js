export class CropperManager {
    constructor(api) {
        this.api = api;
        this.cropperInstance = null;

        const baseUrlMatch = window.location.pathname.match(/^(.*)\/admin/);
        this.baseUrl = baseUrlMatch ? baseUrlMatch[1] : '';

        this.bindEvents();
    }

    bindEvents() {
        document.addEventListener('click', (e) => {
            const btnOpen = e.target.closest('#btn-open-cropper');
            const btnClose = e.target.closest('.btn-close-cropper-modal');
            const btnSave = e.target.closest('#btn-save-crop');

            if (btnOpen) {
                e.preventDefault();
                this.openCropper();
            }

            if (btnClose) {
                e.preventDefault();
                this.closeCropper();
            }

            if (btnSave) {
                e.preventDefault();
                this.saveCrop(btnSave);
            }
        });
    }

    openCropper() {
        const comicIdInput = document.getElementById('comic_id');
        const comicId = comicIdInput?.value.trim();

        if (!comicId || comicId.length !== 8) {
            alert(
                'Bitte zuerst eine gültige 8-stellige Comic-ID eingeben oder den Comic speichern!'
            );
            return;
        }

        const imgUrl = `${this.baseUrl}/assets/images/comic/hires/${comicId}.webp?t=${Date.now()}`;
        const testImg = new Image();

        testImg.onload = () => {
            const cropperModal = document.getElementById('cropper-modal');
            const cropperImage = document.getElementById('cropper-image');
            const cropComicIdInput = document.getElementById('crop_comic_id');

            if (cropComicIdInput) cropComicIdInput.value = comicId;
            if (cropperImage) {
                cropperImage.src = imgUrl;
                cropperImage.style.display = 'block';
            }
            if (cropperModal) cropperModal.style.display = 'flex';

            setTimeout(() => {
                if (this.cropperInstance) this.cropperInstance.destroy();
                this.cropperInstance = new window.Cropper(cropperImage, {
                    aspectRatio: 1200 / 630, // Open-Graph Standard
                    viewMode: 1,
                    autoCropArea: 0.8,
                    background: false,
                    zoomable: false,
                    guides: true,
                });
            }, 100);
        };

        testImg.onerror = () => {
            alert(
                'Es existiert noch kein Hires-Bild für diesen Comic auf dem Server. Bitte lade die Bilder zuerst hoch.'
            );
        };
        testImg.src = imgUrl;
    }

    closeCropper() {
        const cropperModal = document.getElementById('cropper-modal');
        if (cropperModal) cropperModal.style.display = 'none';
        if (this.cropperInstance) {
            this.cropperInstance.destroy();
            this.cropperInstance = null;
        }
    }

    async saveCrop(btnElement) {
        if (!this.cropperInstance) return;

        const cropData = this.cropperInstance.getData(true);
        const comicId = document.getElementById('crop_comic_id')?.value;

        if (!comicId) return;

        const originalText = btnElement.innerHTML;
        btnElement.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Schneide zu...';
        btnElement.style.pointerEvents = 'none';

        const fd = new window.FormData();
        fd.append('comic_id', comicId);
        fd.append('x', cropData.x);
        fd.append('y', cropData.y);
        fd.append('width', cropData.width);
        fd.append('height', cropData.height);

        try {
            const result = await this.api.post('crop_social_media', fd);

            if (result.success) {
                this.api.showStatus(result.message, 'success');
                this.closeCropper();

                // Live-Vorschau updaten (Cache Buster)
                const timestamp = Date.now();
                const prevSocial = document.getElementById('prev-comic-social');
                if (prevSocial) {
                    prevSocial.src = `${this.baseUrl}/assets/images/comic/socialmedia/${comicId}.jpg?t=${timestamp}`;
                }

                // Miniaturansicht in der Tabelle updaten
                const tableRow = document
                    .querySelector(`.btn-delete-comic[data-id="${comicId}"]`)
                    ?.closest('tr');
                if (tableRow) {
                    const tableThumb = tableRow.querySelectorAll('img')[1];
                    if (tableThumb) {
                        tableThumb.src = `${this.baseUrl}/assets/images/comic/socialmedia/${comicId}.jpg?t=${timestamp}`;
                        tableThumb.style.display = 'inline-block';
                    }
                }
            } else {
                this.api.showStatus(result.error, 'error');
            }
        } finally {
            btnElement.innerHTML = originalText;
            btnElement.style.pointerEvents = 'auto';
        }
    }
}
