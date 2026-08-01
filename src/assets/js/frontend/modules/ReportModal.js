export class ReportModal {
    /** @param {import('../core/FrontendApi.js').FrontendApi} api */
    constructor(api) {
        this.api = api;
        this.modal = document.getElementById('global-report-modal');
        this.form = document.getElementById('global-report-form');
        this.statusMsg = document.getElementById('report-status-msg');

        if (this.modal && this.form) {
            this.initWysiwyg();
            this.bindEvents();
            this.bindDropZone();
        }
    }

    initWysiwyg() {
        if (typeof window.$ !== 'undefined' && typeof window.$.fn.trumbowyg !== 'undefined') {
            window.$.trumbowyg.svgPath =
                'https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/ui/icons.svg';
            window.$('.public-wysiwyg').trumbowyg({
                lang: 'de',
                btns: [
                    ['viewHTML'],
                    ['undo', 'redo'],
                    ['strong', 'em', 'del'],
                    ['link'],
                    ['removeformat'],
                ],
            });
        }
    }

    bindEvents() {
        document.getElementById('open-global-report')?.addEventListener('click', (e) => {
            e.preventDefault();
            this.open();
        });
        document.getElementById('open-report-modal')?.addEventListener('click', (e) => {
            e.preventDefault();
            this.open();
        });
        document.getElementById('close-report-modal')?.addEventListener('click', () => {
            this.modal.style.display = 'none';
        });

        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) this.modal.style.display = 'none';
        });

        const typeSelect = document.getElementById('report_type');
        if (typeSelect) typeSelect.addEventListener('change', (e) => this.handleTypeChange(e));

        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
    }

    open() {
        this.modal.style.display = 'flex';

        const comicImg = document.getElementById('comic-image');
        const idInput = document.getElementById('report_comic_id');
        const idSection = document.getElementById('comic-id-section');
        const optTranscript = document.getElementById('report_type_transcript');
        const debugInput = document.getElementById('report_debug_info');
        const urlDisplay = document.getElementById('report_page_url_display');
        const charDescRaw = document.getElementById('raw-char-description');

        const telemetry = {
            url: window.location.href,
            system: {
                userAgent: navigator.userAgent,
                viewport: `${window.innerWidth}x${window.innerHeight}`,
                pixelRatio: window.devicePixelRatio || 1,
                theme: document.body.classList.contains('theme-night') ? 'dark' : 'light',
            },
            context: {},
        };

        if (urlDisplay) urlDisplay.value = window.location.href;

        if (comicImg && idInput) {
            idInput.value = comicImg.dataset.id || '';
            idSection.style.display = 'block';
            optTranscript.style.display = 'block';
            optTranscript.textContent = 'Tippfehler / Transkript-Fehler';

            telemetry.context.comicImages = {
                displayed: comicImg.src,
                hiresLink:
                    comicImg.parentElement.tagName === 'A' ? comicImg.parentElement.href : null,
                originalName: comicImg.dataset.enOriginal || null,
            };

            const charElements = document.querySelectorAll('.character-item .character-name');
            if (charElements.length > 0) {
                telemetry.context.visibleCharacters = Array.from(charElements).map((el) =>
                    el.textContent.trim()
                );
            }
        } else if (charDescRaw) {
            if (idInput) idInput.value = '';
            if (idSection) idSection.style.display = 'none';
            if (optTranscript) {
                optTranscript.style.display = 'block';
                optTranscript.textContent = 'Tippfehler / Biografie anpassen';
            }
            const charName = document.querySelector('.page-header')?.textContent.trim();
            telemetry.context.characterName = charName;
        } else {
            if (idInput) idInput.value = '';
            if (idSection) idSection.style.display = 'none';
            if (optTranscript) optTranscript.style.display = 'none';
        }

        if (debugInput) debugInput.value = JSON.stringify(telemetry, null, 4);
    }

    async handleTypeChange(e) {
        const transcriptSection = document.getElementById('transcript-edit-section');
        const descInput = document.getElementById('report_description');
        const originalInput = document.getElementById('report_transcript_original');
        const comicIdInput = document.getElementById('report_comic_id');
        const charDescRaw = document.getElementById('raw-char-description');
        const comicImg = document.getElementById('comic-image');

        if (e.target.value === 'transcript') {
            if (descInput) descInput.required = false;

            if (comicImg) {
                const comicId = comicIdInput?.value.trim();
                if (comicId?.length >= 8) {
                    this.showStatus(
                        '<i class="fa-solid fa-spinner fa-spin"></i> Lade aktuelles Transkript...',
                        'info'
                    );

                    try {
                        const json = await this.api.get('get_transcript', `id=${comicId}`);
                        this.statusMsg.style.display = 'none';

                        if (json.success) {
                            if (originalInput) originalInput.value = json.transcript;

                            // FIX: Vollständige Überprüfung von Trumbowyg hinzugefügt
                            if (
                                typeof window.$ !== 'undefined' &&
                                typeof window.$.fn.trumbowyg !== 'undefined'
                            ) {
                                window.$('.public-wysiwyg').trumbowyg('html', json.transcript);
                            }

                            if (transcriptSection) transcriptSection.style.display = 'block';
                            if (descInput)
                                descInput.placeholder = 'Zusätzliche Anmerkungen (Optional)...';
                        } else {
                            console.error(
                                '[ReportModal] API-Fehler beim Laden des Transkripts:',
                                json.error
                            );
                            alert(json.error);
                            e.target.value = '';
                        }
                    } catch (err) {
                        console.error(
                            '[ReportModal] Kritischer Fehler beim Abrufen des Transkripts:',
                            err
                        );
                        alert('Ein Netzwerkfehler ist aufgetreten.');
                        e.target.value = '';
                    }
                } else {
                    console.warn('[ReportModal] Systemfehler: Comic-ID fehlt für den Abruf.');
                    alert('Systemfehler: Comic-ID fehlt.');
                    e.target.value = '';
                }
            } else if (charDescRaw) {
                if (originalInput) originalInput.value = charDescRaw.value;

                // FIX: Vollständige Überprüfung von Trumbowyg hinzugefügt
                if (
                    typeof window.$ !== 'undefined' &&
                    typeof window.$.fn.trumbowyg !== 'undefined'
                ) {
                    window.$('.public-wysiwyg').trumbowyg('html', charDescRaw.value);
                }

                if (transcriptSection) transcriptSection.style.display = 'block';
                if (descInput) descInput.placeholder = 'Zusätzliche Anmerkungen (Optional)...';
            } else {
                console.warn('[ReportModal] Systemfehler: Weder Comic noch Charakter gefunden.');
                alert('Systemfehler: Keine Textquelle auf dieser Seite gefunden.');
                e.target.value = '';
            }
        } else {
            if (descInput) descInput.required = true;
            if (transcriptSection) transcriptSection.style.display = 'none';
            if (descInput) descInput.placeholder = 'Beschreibe kurz, was nicht stimmt...';
        }
    }

    bindDropZone() {
        const dropZone = document.getElementById('report-drop-zone');
        const fileInput = document.getElementById('report_screenshot');
        const fileName = document.getElementById('report-screenshot-name');

        if (!dropZone || !fileInput) return;

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
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                fileInput.dispatchEvent(new Event('change'));
            }
        });
        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > 0) {
                dropZone.style.borderColor = 'var(--status-green-text)';
                dropZone.style.backgroundColor = 'var(--status-green-bg)';
                if (fileName) {
                    fileName.textContent = `Bereit: ${fileInput.files[0].name}`;
                    fileName.style.display = 'block';
                }
            } else {
                dropZone.style.borderColor = 'var(--border-medium)';
                dropZone.style.backgroundColor = 'var(--table-row-even)';
                if (fileName) fileName.style.display = 'none';
            }
        });
    }

    async handleSubmit(e) {
        e.preventDefault();
        const btn = document.getElementById('report-submit-btn');
        if (!btn) return;

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sende...';

        try {
            const formData = new window.FormData(this.form);
            const json = await this.api.post('submit_report', formData);

            if (json.success) {
                this.showStatus(`<i class="fa-solid fa-check"></i> ${json.message}`, 'success');
                this.form.reset();

                // FIX: Vollständige Überprüfung von Trumbowyg hinzugefügt
                if (
                    typeof window.$ !== 'undefined' &&
                    typeof window.$.fn.trumbowyg !== 'undefined'
                ) {
                    window.$('.public-wysiwyg').trumbowyg('empty');
                }

                const transcriptSection = document.getElementById('transcript-edit-section');
                if (transcriptSection) transcriptSection.style.display = 'none';

                const dropZone = document.getElementById('report-drop-zone');
                if (dropZone) {
                    dropZone.style.borderColor = 'var(--border-medium)';
                    dropZone.style.backgroundColor = 'var(--table-row-even)';
                }
                const fileName = document.getElementById('report-screenshot-name');
                if (fileName) fileName.style.display = 'none';

                setTimeout(() => {
                    this.modal.style.display = 'none';
                    this.statusMsg.style.display = 'none';
                }, 3000);
            } else {
                console.warn('[ReportModal] Report abgelehnt vom Server:', json.error);
                this.showStatus(
                    `<i class="fa-solid fa-triangle-exclamation"></i> ${json.error}`,
                    'error'
                );
            }
        } catch (err) {
            console.error('[ReportModal] Unerwarteter Fehler beim Senden des Reports:', err);
            this.showStatus(
                '<i class="fa-solid fa-bomb"></i> Fehler bei der Serververbindung.',
                'error'
            );
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Fehler melden';
        }
    }

    showStatus(html, type) {
        if (!this.statusMsg) return;
        this.statusMsg.style.display = 'block';
        if (type === 'success') {
            this.statusMsg.style.backgroundColor = 'var(--status-green-bg)';
            this.statusMsg.style.color = 'var(--status-green-text)';
            this.statusMsg.style.border = '1px solid var(--status-green-border)';
        } else if (type === 'error') {
            this.statusMsg.style.backgroundColor = 'var(--status-red-bg)';
            this.statusMsg.style.color = 'var(--status-red-text)';
            this.statusMsg.style.border = '1px solid var(--status-red-border)';
        } else {
            this.statusMsg.style.backgroundColor = 'var(--status-info-bg)';
            this.statusMsg.style.color = 'var(--status-info-text)';
            this.statusMsg.style.border = '1px solid var(--status-info-border)';
        }
        this.statusMsg.innerHTML = html;
    }
}
