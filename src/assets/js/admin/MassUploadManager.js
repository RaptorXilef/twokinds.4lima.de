import { DragDropService } from './DragDropService.js';

/**
 * @typedef {import('./Api.js').Api} Api
 * @typedef {import('./NotificationService.js').NotificationService} NotificationService
 */
export class MassUploadManager {
    /**
     * @param {Api} api
     * @param {NotificationService} notifications
     */
    constructor(api, notifications, tracker) {
        this.api = api;
        this.notifications = notifications;
        this.tracker = tracker;

        /** @type {HTMLElement|null} */
        this.massDropZone = document.getElementById('mass-drop-zone');
        /** @type {HTMLElement|null} */
        this.queueTableBody = document.querySelector('#upload-queue-table tbody');
        /** @type {HTMLButtonElement|null} */
        this.btnStartMassUpload = document.getElementById('btn-start-mass-upload');
        /** @type {HTMLInputElement|null} */
        this.cfgWidth = document.getElementById('cfg-hires-width');
        /** @type {HTMLInputElement|null} */
        this.cfgHeight = document.getElementById('cfg-hires-height');

        /** @type {Map<string, {hires: File|null, lowres: File|null, status: string}>} */
        this.uploadQueue = new Map(); // Speichert { id: { hires: File, lowres: File } }

        if (this.massDropZone && this.queueTableBody) {
            this.initSettings();
            this.bindEvents();
        }
    }

    initSettings() {
        if (localStorage.getItem('hires_min_width') && this.cfgWidth) {
            this.cfgWidth.value = localStorage.getItem('hires_min_width');
        }
        if (localStorage.getItem('hires_min_height') && this.cfgHeight) {
            this.cfgHeight.value = localStorage.getItem('hires_min_height');
        }

        this.cfgWidth?.addEventListener('input', () =>
            localStorage.setItem('hires_min_width', this.cfgWidth.value)
        );
        this.cfgHeight?.addEventListener('input', () =>
            localStorage.setItem('hires_min_height', this.cfgHeight.value)
        );

        const btnResetThresholds = document.getElementById('btn-reset-thresholds');
        if (btnResetThresholds) {
            btnResetThresholds.addEventListener('click', () => {
                this.cfgWidth.value = btnResetThresholds.dataset.defaultW;
                this.cfgHeight.value = btnResetThresholds.dataset.defaultH;
                localStorage.removeItem('hires_min_width');
                localStorage.removeItem('hires_min_height');
            });
        }
    }

    bindEvents() {
        // PERFEKTE SCHRUMPFKUR DURCH DRAG & DROP SERVICE
        DragDropService.bind('mass-drop-zone', 'mass-upload-input', {
            onChange: (files) => {
                DragDropService.reset('mass-drop-zone');
                document.getElementById('mass-upload-input').value = '';
                this.processDroppedFiles(files);
            },
        });

        this.queueTableBody.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-remove-queue');
            if (btn) {
                this.uploadQueue.delete(btn.dataset.id);
                this.renderQueueTable();
            }
        });

        this.btnStartMassUpload?.addEventListener('click', () => this.startMassUpload());
    }

    async processDroppedFiles(files) {
        const thresholdW = parseInt(this.cfgWidth.value, 10);
        const thresholdH = parseInt(this.cfgHeight.value, 10);

        for (const file of Array.from(files)) {
            const match = file.name.match(/^(\d{8})/);
            if (!match) {
                this.notifications.show(
                    `Datei "${file.name}" ignoriert (Keine 8-stellige ID).`,
                    'error'
                );
                continue;
            }
            const baseId = match[1];

            const isHires = await new Promise((resolve) => {
                const img = new Image();
                img.onload = () => {
                    resolve(img.width >= thresholdW || img.height >= thresholdH);
                    URL.revokeObjectURL(img.src);
                };
                img.onerror = () => resolve(false);
                img.src = URL.createObjectURL(file);
            });

            const fileTypeStr = isHires ? 'Hires' : 'Lowres';
            let targetId = baseId;

            // 1. Lokale Konflikte prüfen
            if (this.uploadQueue.has(targetId)) {
                const existingEntry = this.uploadQueue.get(targetId);
                if ((isHires && existingEntry.hires) || (!isHires && existingEntry.lowres)) {
                    if (
                        confirm(
                            `Für die ID "${baseId}" liegt lokal bereits ein ${fileTypeStr}-Bild in der Warteschlange.\nMöchtest du "${file.name}" als Variante hinzufügen?`
                        )
                    ) {
                        targetId = await this.findFreeVariantId(baseId, isHires);
                        if (!targetId) {
                            alert('Zu viele Unterseiten! Datei übersprungen.');
                            continue;
                        }
                    } else {
                        continue;
                    }
                }
            }

            // 2. Server Konflikte prüfen
            const folder = isHires ? 'hires' : 'lowres';
            const serverUrl = `${this.api.baseUrl}/assets/images/comic/${folder}/${targetId}.webp`;

            const serverExists = await new Promise((resolve) => {
                const img = new Image();
                img.onload = () => resolve(true);
                img.onerror = () => resolve(false);
                img.src = serverUrl;
            });

            if (serverExists) {
                const decision = await this.showOverwriteModal(
                    targetId,
                    file,
                    serverUrl,
                    fileTypeStr
                );
                if (decision === 'skip') continue;
                if (decision === 'variant') {
                    targetId = await this.findFreeVariantId(baseId, isHires);
                    if (!targetId) {
                        alert('Zu viele Unterseiten! Datei übersprungen.');
                        continue;
                    }
                }
            }

            // 3. Zur Queue hinzufügen
            if (!this.uploadQueue.has(targetId)) {
                this.uploadQueue.set(targetId, { hires: null, lowres: null, status: 'Wartet' });
            }
            const entry = this.uploadQueue.get(targetId);
            if (isHires) entry.hires = file;
            else entry.lowres = file;

            this.renderQueueTable();
        }
    }

    showOverwriteModal(id, file, serverSrc, typeStr) {
        return new Promise((resolve) => {
            const modal = document.getElementById('overwrite-modal');
            const idDisplay = document.getElementById('overwrite-id-display');
            if (idDisplay) idDisplay.textContent = `${id} (${typeStr})`;

            const localUrl = URL.createObjectURL(file);
            const newImg = document.getElementById('overwrite-new-img');
            if (newImg) newImg.src = localUrl;

            const serverImg = document.getElementById('overwrite-server-img');
            if (serverImg) serverImg.src = `${serverSrc}?t=${Date.now()}`;

            const cleanup = () => {
                document.getElementById('btn-overwrite-skip')?.removeEventListener('click', onSkip);
                document
                    .getElementById('btn-overwrite-variant')
                    ?.removeEventListener('click', onVariant);
                document
                    .getElementById('btn-overwrite-confirm')
                    ?.removeEventListener('click', onOverwrite);
                if (modal) modal.style.display = 'none';
                URL.revokeObjectURL(localUrl);
            };

            const onSkip = () => {
                cleanup();
                resolve('skip');
            };
            const onVariant = () => {
                cleanup();
                resolve('variant');
            };
            const onOverwrite = () => {
                cleanup();
                resolve('overwrite');
            };

            document.getElementById('btn-overwrite-skip')?.addEventListener('click', onSkip);
            document.getElementById('btn-overwrite-variant')?.addEventListener('click', onVariant);
            document
                .getElementById('btn-overwrite-confirm')
                ?.addEventListener('click', onOverwrite);

            if (modal) modal.style.display = 'flex';
        });
    }

    async findFreeVariantId(baseId, isHires) {
        const alphabet = 'abcdefghijklmnopqrstuvwxyz';
        const folder = isHires ? 'hires' : 'lowres';

        for (const letter of alphabet) {
            const testId = baseId + letter;
            if (this.uploadQueue.has(testId)) {
                const testEntry = this.uploadQueue.get(testId);
                if ((isHires && testEntry.hires) || (!isHires && testEntry.lowres)) continue;
            }

            const serverExists = await new Promise((resolve) => {
                const img = new Image();
                img.onload = () => resolve(true);
                img.onerror = () => resolve(false);
                img.src = `${this.api.baseUrl}/assets/images/comic/${folder}/${testId}.webp`;
            });

            if (!serverExists) return testId;
        }
        return null;
    }

    renderQueueTable() {
        this.queueTableBody.innerHTML = '';
        if (this.uploadQueue.size === 0) {
            this.queueTableBody.innerHTML =
                '<tr id="queue-empty-msg"><td colspan="5" class="empty-table-message">Warteschlange ist leer.</td></tr>';
            if (this.btnStartMassUpload) this.btnStartMassUpload.disabled = true;
            return;
        }

        if (this.btnStartMassUpload) this.btnStartMassUpload.disabled = false;

        const sortedIds = Array.from(this.uploadQueue.keys()).sort();

        sortedIds.forEach((id) => {
            const data = this.uploadQueue.get(id);
            const tr = document.createElement('tr');

            const hiresName = data.hires
                ? data.hires.name
                : '<span style="color:var(--text-color-faded)">Wird auto-generiert</span>';
            const lowresName = data.lowres
                ? data.lowres.name
                : '<span style="color:var(--text-color-faded)">Wird auto-skaliert</span>';

            let statusHtml = `<strong>${data.status}</strong>`;
            if (data.status === 'Lädt...')
                statusHtml = '<i class="fa-solid fa-spinner fa-spin"></i> Verarbeitung...';
            if (data.status === 'Fertig')
                statusHtml =
                    '<span style="color:var(--status-green-text)"><i class="fa-solid fa-check"></i> Fertig</span>';
            if (data.status.startsWith('Fehler'))
                statusHtml = `<span style="color:var(--status-red-text)"><i class="fa-solid fa-xmark"></i> ${data.status}</span>`;

            tr.innerHTML = `
                <td class="mono"><strong>${id}</strong></td>
                <td><small>${hiresName}</small></td>
                <td><small>${lowresName}</small></td>
                <td style="text-align: center;">${statusHtml}</td>
                <td style="text-align: center;">
                    <button type="button" class="button delete btn-remove-queue" data-id="${id}" ${data.status === 'Lädt...' ? 'disabled' : ''}><i class="fa-solid fa-trash"></i></button>
                </td>
            `;
            this.queueTableBody.appendChild(tr);
        });
    }

    async startMassUpload() {
        this.btnStartMassUpload.disabled = true;
        const ids = Array.from(this.uploadQueue.keys());

        for (const id of ids) {
            const data = this.uploadQueue.get(id);
            if (data.status === 'Fertig') continue;
            if (!data.hires && !data.lowres) continue;

            data.status = 'Lädt...';
            this.renderQueueTable();

            const processUpload = async (force = false) => {
                const fd = new window.FormData();
                fd.append('comic_id', id);
                if (force) fd.append('force', '1');
                if (data.hires) fd.append('upload_hires', data.hires);
                if (data.lowres) fd.append('upload_lowres', data.lowres);

                const result = await this.api.post('upload_comic_media', fd);

                if (!result.success && result.error === 'COMIC_NOT_FOUND') {
                    if (
                        confirm(
                            `Comicseite für die ID "${id}" existiert noch nicht.\n\nTrotzdem hochladen?`
                        )
                    ) {
                        return await processUpload(true);
                    } else {
                        return { success: false, error: 'Übersprungen' };
                    }
                }
                return result;
            };

            const json = await processUpload(false);
            if (json.success) {
                data.status = 'Fertig';
            } else {
                data.status = `Fehler: ${json.error}`;
            }
            this.renderQueueTable();
        }
        this.notifications.show('Massenverarbeitung abgeschlossen!', 'success');
        this.btnStartMassUpload.disabled = false;
    }
}
