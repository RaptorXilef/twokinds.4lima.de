/**
 * @typedef {import('../core/Api.js').Api} Api
 * @typedef {import('../core/NotificationService.js').NotificationService} NotificationService
 */

export class CropperManager {
    /**
     * @param {Api} api
     * @param {NotificationService} notifications
     */
    constructor(api, notifications) {
        this.api = api;
        this.notifications = notifications;
        this.cropperInstance = null;

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

        const imgUrl = `${this.api.baseUrl}/assets/images/comics/hires/${comicId}.webp?t=${Date.now()}`;
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
                this.notifications.show(result.message, 'success');
                this.closeCropper();

                // Live-Vorschau updaten (Cache Buster)
                const timestamp = Date.now();

                // Live-Update für das Bild im Comic-Modal (alte Cache-Buster entfernen, neuen anhängen)
                const prevSocial = document.getElementById('prev-comic-social');
                if (prevSocial) {
                    const cleanSrc = prevSocial.src.split('?')[0];
                    prevSocial.src = `${cleanSrc}?t=${timestamp}`;
                }

                // Live-Update für das Bild in der Tabellen-Übersicht im Hintergrund
                const tableRow = document
                    .querySelector(`.btn-delete-comic[data-id="${comicId}"]`)
                    ?.closest('tr');
                if (tableRow) {
                    const tableThumb = tableRow.querySelectorAll('img')[1];
                    if (tableThumb) {
                        const cleanSrc = tableThumb.src.split('?')[0];
                        tableThumb.src = `${cleanSrc}?t=${timestamp}`;
                        tableThumb.style.display = 'inline-block';
                    }
                }

                // Event triggern, damit ComicEditor.js Bescheid weiß
                document.dispatchEvent(
                    new CustomEvent('comicMediaUpdated', { detail: { comicId, timestamp } })
                );
            } else {
                this.notifications.show(result.error, 'error');
            }
        } finally {
            btnElement.innerHTML = originalText;
            btnElement.style.pointerEvents = 'auto';
        }
    }
}
