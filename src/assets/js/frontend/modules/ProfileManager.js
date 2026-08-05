export class ProfileManager {
    /** @param {import('../core/FrontendApi.js').FrontendApi} api */
    constructor(api) {
        this.api = api;
        this.cropperInstance = null;

        // Wenn das Profil-Formular nicht existiert, direkt abbrechen
        if (!document.getElementById('form-username')) return;

        this.init();
    }

    init() {
        this.bindForm('form-profile-details', 'msg-details', true, false);
        this.bindForm('form-username', 'msg-username', true, false);
        this.bindForm('form-password', 'msg-password', false, true);
        this.bindForm('form-newsletter', 'msg-newsletter', false, false);
        this.bindForm('form-email', 'msg-email', false, true);
        this.bindForm('form-delete-account', 'msg-delete-account', false, false, true); // True = ist Lösch-Formular

        this.bindAvatarUpload();
    }

    bindAvatarUpload() {
        const fileInput = document.getElementById('avatar-upload-input');
        const modal = document.getElementById('avatar-cropper-modal');
        const cropperImg = document.getElementById('cropper-image');
        const btnSave = document.getElementById('btn-crop-save');
        const msgAvatar = document.getElementById('msg-avatar');
        const dropZone = document.getElementById('avatar-drop-zone');

        if (!fileInput || !modal) return;

        // Drag & Drop Area Events
        if (dropZone) {
            dropZone.addEventListener('click', () => fileInput.click());

            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.style.borderColor = 'var(--link-color)';
                dropZone.style.backgroundColor = 'var(--table-row-hover)';
            });

            dropZone.addEventListener('dragleave', (e) => {
                e.preventDefault();
                dropZone.style.borderColor = 'var(--border-medium)';
                dropZone.style.backgroundColor = 'var(--table-row-even)';
            });

            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropZone.style.borderColor = 'var(--border-medium)';
                dropZone.style.backgroundColor = 'var(--table-row-even)';

                if (e.dataTransfer.files?.[0]) {
                    fileInput.files = e.dataTransfer.files;
                    fileInput.dispatchEvent(new Event('change'));
                }
            });
        }

        // Bild-Auswahl (Datei ausgewählt oder gedroppt)
        fileInput.addEventListener('change', (e) => {
            if (e.target.files?.[0]) {
                // FIX: Optional chaining
                const url = URL.createObjectURL(e.target.files[0]);
                cropperImg.src = url;
                cropperImg.style.display = 'block';
                modal.style.display = 'flex';

                if (this.cropperInstance) this.cropperInstance.destroy();

                setTimeout(() => {
                    this.cropperInstance = new window.Cropper(cropperImg, {
                        aspectRatio: 1, // 1:1 Quadrat/Kreis
                        viewMode: 1,
                        background: false,
                        autoCropArea: 0.9,
                        dragMode: 'move',
                    });
                }, 100);
            }
        });

        document.querySelectorAll('.btn-close-cropper-modal').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                modal.style.display = 'none';
                if (this.cropperInstance) this.cropperInstance.destroy();
                fileInput.value = '';
            });
        });

        btnSave.addEventListener('click', () => {
            if (!this.cropperInstance) return;

            const origText = btnSave.innerHTML;
            btnSave.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verarbeite...';
            btnSave.disabled = true;

            this.cropperInstance
                .getCroppedCanvas({
                    width: 400,
                    height: 400,
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high',
                })
                .toBlob(async (blob) => {
                    const formData = new FormData();
                    formData.append('avatar_file', blob, 'avatar.png');

                    try {
                        const res = await this.api.post('upload_avatar', formData);
                        modal.style.display = 'none';
                        msgAvatar.style.display = 'block';

                        if (res.success) {
                            msgAvatar.className = 'msg-box status-message status-green visible';
                            msgAvatar.innerHTML = `<i class="fa-solid fa-check"></i> ${res.message}`;
                            document.getElementById('current-avatar').src = res.new_avatar_url;
                        } else {
                            msgAvatar.className = 'msg-box status-message status-red visible';
                            msgAvatar.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i> ${res.error}`;
                        }
                    } catch {
                        msgAvatar.className = 'msg-box status-message status-red visible';
                        msgAvatar.innerHTML = 'Verbindungsfehler.';
                    } finally {
                        btnSave.innerHTML = origText;
                        btnSave.disabled = false;
                        if (this.cropperInstance) this.cropperInstance.destroy();
                        fileInput.value = '';
                    }
                }, 'image/png');
        });
    }

    bindForm(formId, msgId, reloadOnSuccess = false, resetOnSuccess = false, isDelete = false) {
        const form = document.getElementById(formId);
        const msg = document.getElementById(msgId);
        if (!form || !msg) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (isDelete && !confirm('ACHTUNG: Möchtest du dein Konto WIRKLICH löschen?')) return;

            const btn = form.querySelector('button[type="submit"]');
            const origText = btn ? btn.innerHTML : '';

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verarbeite...';
            }

            const formData = new window.FormData(form);

            // FIX: Die Variable muss VOR dem try-Block existieren,
            // damit sie unten beim Button-Reset noch bekannt ist!
            let responseJson = null;

            try {
                // Bei Delete greifen wir auf die neue API-Route zu
                const endpoint = isDelete ? 'frontend_delete_account' : 'frontend_update_profile';
                responseJson = await this.api.post(endpoint, formData);

                msg.style.display = 'block';

                if (responseJson.success) {
                    msg.className = 'msg-box status-message status-green visible';
                    msg.innerHTML = `<i class="fa-solid fa-check"></i> ${responseJson.message}`;

                    if (resetOnSuccess) form.reset();

                    if (isDelete && responseJson.redirect !== undefined) {
                        // Bei Kontolöschung sofort auf die Startseite zurückwerfen
                        setTimeout(
                            () =>
                                (window.location.href = `${this.api.baseUrl}/${responseJson.redirect}`),
                            1500
                        );
                    } else if (reloadOnSuccess) {
                        setTimeout(() => window.location.reload(), 1000);
                    }
                } else {
                    msg.className = 'msg-box status-message status-red visible';
                    msg.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i> ${responseJson.error}`;
                }
            } catch (err) {
                console.error('[ProfileManager] Unerwarteter Fehler beim API-Call:', err);
                msg.className = 'msg-box status-message status-red visible';
                msg.innerHTML = 'Verbindungsfehler.';
            }

            // Button nur bei Fehlschlag wieder aktivieren
            if (btn && (!responseJson?.success || (!reloadOnSuccess && !isDelete))) {
                btn.disabled = false;
                btn.innerHTML = origText;
            }
        });
    }
}
