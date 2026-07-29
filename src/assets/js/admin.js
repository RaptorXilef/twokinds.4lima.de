document.addEventListener('DOMContentLoaded', () => {
    const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfTokenMeta?.content ?? '';
    const statusBox = document.getElementById('global-status-message');
    const baseUrlMatch = window.location.pathname.match(/^(.*)\/admin/);
    const baseUrl = baseUrlMatch ? baseUrlMatch[1] : '';

    // --- UNSAVED CHANGES WARNING ---
    window.isDirty = false; // Als globales Window-Objekt, damit ES6-Module darauf zugreifen können
    window.addEventListener('beforeunload', (e) => {
        if (window.isDirty) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    // Registriere Änderungen an Textfeldern
    document.addEventListener('input', (e) => {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
            window.isDirty = true;
        }
    });

    // --- GLOBAL ERROR HANDLER FOR IMAGES ---
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
    ); // "true" fängt das Event frühzeitig ab!

    // --- WYSIWYG TRUMBOWYG INITIALISIERUNG ---
    if (typeof $.fn.trumbowyg !== 'undefined') {
        $.trumbowyg.svgPath =
            'https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/ui/icons.svg';
        $('.wysiwyg-editor')
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
                window.isDirty = true;
            });
    }

    // Globale Helper-Funktion für Statusmeldungen im alten JS
    function showMsg(text, type) {
        if (!statusBox) return;
        statusBox.className = `status-message visible status-${type}`;
        statusBox.innerHTML = text;
        setTimeout(() => {
            statusBox.className = 'status-message';
        }, 5000);
    }

    // --- ROW HIGHLIGHT & SCROLL LOGIC ---
    function highlightAndScroll(id) {
        if (!id) return;
        // Wir suchen den Delete-Button, da dieser die reine ID im data-id Attribut hat
        const targetBtn =
            document.querySelector(`.btn-delete-comic[data-id="${id}"]`) ??
            document.querySelector(`.btn-delete-char[data-id="${id}"]`);
        const tr = targetBtn?.closest('tr');
        if (tr) {
            tr.scrollIntoView({ behavior: 'smooth', block: 'center' });
            tr.classList.add('row-highlight');
            setTimeout(() => {
                tr.classList.remove('row-highlight');
            }, 3000);
        }
    }

    // Beim Laden der Seite prüfen, ob wir gerade gespeichert haben
    const highlightId = sessionStorage.getItem('highlightEntityId');
    if (highlightId) {
        setTimeout(() => {
            highlightAndScroll(highlightId);
        }, 300); // 300ms warten, bis Tabs initialisiert sind
        sessionStorage.removeItem('highlightEntityId');
    }

    // --- TAB LOGIK ---
    const activeTab = sessionStorage.getItem('activeAdminTab') ?? 'section-comics';
    document.querySelectorAll('.content-section').forEach((sec) => sec.classList.remove('active'));
    document.querySelectorAll('.tab-link').forEach((l) => l.classList.remove('active'));

    const targetSection = document.getElementById(activeTab);
    const targetLink = document.querySelector(`.tab-link[data-target="${activeTab}"]`);
    if (targetSection) targetSection.classList.add('active');
    if (targetLink) targetLink.classList.add('active');

    document.querySelectorAll('.tab-link').forEach((link) => {
        link.addEventListener('click', function () {
            if (this.dataset.target) {
                sessionStorage.setItem('activeAdminTab', this.dataset.target);
                document
                    .querySelectorAll('.content-section')
                    .forEach((sec) => sec.classList.remove('active'));
                document.querySelectorAll('.tab-link').forEach((l) => l.classList.remove('active'));
                document.getElementById(this.dataset.target)?.classList.add('active');
                this.classList.add('active');
            }
        });
    });

    // --- PAGINIERUNG & INTELLIGENTE SUCHE (COMICS) ---
    const comicSearchInput = document.getElementById('comic-search');
    const comicPerPageSelect = document.getElementById('comic-per-page');
    const comicTableBody = document.querySelector('.comic-editor-table tbody');
    const comicPaginationContainer = document.getElementById('comic-pagination');

    if (comicTableBody && comicSearchInput && comicPerPageSelect && comicPaginationContainer) {
        const allComicRows = Array.from(comicTableBody.querySelectorAll('tr')).filter(
            (row) => !row.classList.contains('empty-table-message')
        );
        let currentPage = 1;
        let itemsPerPage = '15';
        let currentSearchQuery = '';

        function renderPaginationButtons(totalPages) {
            comicPaginationContainer.innerHTML = '';
            if (totalPages <= 1) return;

            const createBtn = (text, isDisabled, isActive, clickHandler) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = `button ${isActive ? 'edit ' : ''}${isDisabled ? 'disabled' : ''}`;
                btn.innerHTML = text;
                if (isDisabled) btn.style.opacity = '0.5';
                if (!isDisabled && clickHandler) btn.onclick = clickHandler;
                return btn;
            };

            // "Zurück" Button
            comicPaginationContainer.appendChild(
                createBtn('&laquo;', currentPage === 1, false, () => {
                    currentPage--;
                    renderComicTable();
                })
            );

            // Dynamische Seitenzahlen (max 5 Buttons in der Mitte anzeigen, damit es nicht ausufert)
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(totalPages, currentPage + 2);

            if (startPage > 1) {
                comicPaginationContainer.appendChild(
                    createBtn('1', false, false, () => {
                        currentPage = 1;
                        renderComicTable();
                    })
                );
                if (startPage > 2)
                    comicPaginationContainer.appendChild(createBtn('...', true, false, null));
            }

            for (let i = startPage; i <= endPage; i++) {
                comicPaginationContainer.appendChild(
                    createBtn(i.toString(), false, i === currentPage, () => {
                        currentPage = i;
                        renderComicTable();
                    })
                );
            }

            if (endPage < totalPages) {
                if (endPage < totalPages - 1)
                    comicPaginationContainer.appendChild(createBtn('...', true, false, null));
                comicPaginationContainer.appendChild(
                    createBtn(totalPages.toString(), false, false, () => {
                        currentPage = totalPages;
                        renderComicTable();
                    })
                );
            }

            // "Vor" Button
            comicPaginationContainer.appendChild(
                createBtn('&raquo;', currentPage === totalPages, false, () => {
                    currentPage++;
                    renderComicTable();
                })
            );
        }

        function renderComicTable() {
            // 1. Filtern (Intelligente Suche über den gesamten Text in der Zeile)
            const filteredRows = allComicRows.filter((row) =>
                row.textContent.toLowerCase().includes(currentSearchQuery.toLowerCase())
            );

            // 2. Limits & Seiten berechnen
            const totalItems = filteredRows.length;
            const limit = itemsPerPage === 'all' ? totalItems : parseInt(itemsPerPage, 10);
            const totalPages = limit > 0 ? Math.ceil(totalItems / limit) : 1;

            if (currentPage > totalPages) currentPage = totalPages || 1;
            const startIndex = limit === totalItems ? 0 : (currentPage - 1) * limit;
            const endIndex = startIndex + limit;

            allComicRows.forEach((row) => (row.style.display = 'none'));
            filteredRows.slice(startIndex, endIndex).forEach((row) => (row.style.display = ''));

            // Info, wenn Suche keine Treffer liefert
            let emptyMsg = comicTableBody.querySelector('.dyn-empty-msg');
            if (filteredRows.length === 0) {
                if (!emptyMsg) {
                    emptyMsg = document.createElement('tr');
                    emptyMsg.className = 'dyn-empty-msg empty-table-message';
                    emptyMsg.innerHTML =
                        '<td colspan="6">Keine Comics für diesen Suchbegriff gefunden.</td>';
                    comicTableBody.appendChild(emptyMsg);
                }
                emptyMsg.style.display = '';
            } else if (emptyMsg) {
                emptyMsg.style.display = 'none';
            }
            renderPaginationButtons(totalPages);
        }

        comicSearchInput.addEventListener('input', (e) => {
            currentSearchQuery = e.target.value;
            currentPage = 1;
            renderComicTable();
        });
        comicPerPageSelect.addEventListener('change', (e) => {
            itemsPerPage = e.target.value;
            currentPage = 1;
            renderComicTable();
        });

        // Initiale Paginierung auslösen
        renderComicTable();
    }

    // --- KLICK-ZOOM OVERLAY (LIGHTBOX) ---
    const hoverOverlay = document.getElementById('image-hover-overlay');
    const hoverOverlayImg = document.getElementById('hover-overlay-img');

    document.querySelectorAll('.hover-zoom-trigger').forEach((img) => {
        img.addEventListener('click', () => {
            if (img.src && !img.src.includes('placehold.co')) {
                if (hoverOverlayImg && hoverOverlay) {
                    hoverOverlayImg.src = img.src;
                    hoverOverlay.style.display = 'flex';
                }
            }
        });
    });

    // Klick ins Schwarze schließt die Vorschau wieder
    hoverOverlay?.addEventListener('click', () => {
        hoverOverlay.style.display = 'none';
        if (hoverOverlayImg) hoverOverlayImg.src = '';
    });

    // --- MASSEN UPLOAD WARTESCHLANGE (QUEUE) ---
    const massDropZone = document.getElementById('mass-drop-zone');
    const massFileInput = document.getElementById('mass-upload-input');
    const queueTableBody = document.querySelector('#upload-queue-table tbody');
    const btnStartMassUpload = document.getElementById('btn-start-mass-upload');
    const cfgWidth = document.getElementById('cfg-hires-width');
    const cfgHeight = document.getElementById('cfg-hires-height');
    const btnResetThresholds = document.getElementById('btn-reset-thresholds');

    const uploadQueue = new Map(); // Speichert { id: { hires: File, lowres: File } }

    if (massDropZone && queueTableBody) {
        // LocalStorage für Schwellenwerte laden
        if (localStorage.getItem('hires_min_width'))
            cfgWidth.value = localStorage.getItem('hires_min_width');
        if (localStorage.getItem('hires_min_height'))
            cfgHeight.value = localStorage.getItem('hires_min_height');

        cfgWidth.addEventListener('input', () =>
            localStorage.setItem('hires_min_width', cfgWidth.value)
        );
        cfgHeight.addEventListener('input', () =>
            localStorage.setItem('hires_min_height', cfgHeight.value)
        );

        btnResetThresholds.addEventListener('click', () => {
            cfgWidth.value = btnResetThresholds.dataset.defaultW;
            cfgHeight.value = btnResetThresholds.dataset.defaultH;
            localStorage.removeItem('hires_min_width');
            localStorage.removeItem('hires_min_height');
        });

        massDropZone.addEventListener('click', () => massFileInput.click());
        massDropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            massDropZone.style.backgroundColor = 'var(--table-row-hover)';
        });
        massDropZone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            massDropZone.style.backgroundColor = 'var(--table-row-even)';
        });
        massDropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            massDropZone.style.backgroundColor = 'var(--table-row-even)';
            processDroppedFiles(e.dataTransfer.files);
        });
        massFileInput.addEventListener('change', () => {
            processDroppedFiles(massFileInput.files);
            massFileInput.value = ''; // Reset für weitere Klicks
        });

        async function processDroppedFiles(files) {
            const thresholdW = parseInt(cfgWidth.value, 10);
            const thresholdH = parseInt(cfgHeight.value, 10);

            // Wir nutzen eine for...of Schleife, um asynchron auf User-Entscheidungen (Modals) warten zu können
            for (const file of Array.from(files)) {
                // Strikt nach genau 8 Ziffern am Anfang suchen
                const match = file.name.match(/^(\d{8})/);
                if (!match) {
                    showMsg(
                        `Datei "${file.name}" ignoriert (Keine 8-stellige ID am Anfang).`,
                        'orange'
                    );
                    continue;
                }
                const baseId = match[1];

                // 1. Auflösung ermitteln (Hires vs Lowres)
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

                // 2. Prüfen, ob die ID in der LOKALEN Warteschlange schon belegt ist
                if (uploadQueue.has(targetId)) {
                    const existingEntry = uploadQueue.get(targetId);
                    if ((isHires && existingEntry.hires) || (!isHires && existingEntry.lowres)) {
                        if (
                            confirm(
                                `Für die ID "${baseId}" liegt lokal bereits ein ${fileTypeStr}-Bild in der Warteschlange.\nMöchtest du "${file.name}" als Variante (a, b, c...) hinzufügen?`
                            )
                        ) {
                            targetId = await findFreeVariantId(baseId, isHires);
                            if (!targetId) {
                                alert('Zu viele Unterseiten! Datei übersprungen.');
                                continue;
                            }
                        } else {
                            continue;
                        }
                    }
                }

                const folder = isHires ? 'hires' : 'lowres';
                const serverUrl = `${baseUrl}/assets/images/comic/${folder}/${targetId}.webp`;

                const serverExists = await new Promise((resolve) => {
                    const img = new Image();
                    img.onload = () => resolve(true);
                    img.onerror = () => resolve(false);
                    img.src = serverUrl;
                });

                if (serverExists) {
                    const decision = await showOverwriteModal(
                        targetId,
                        file,
                        serverUrl,
                        fileTypeStr
                    );
                    if (decision === 'skip') continue;
                    if (decision === 'variant') {
                        targetId = await findFreeVariantId(baseId, isHires);
                        if (!targetId) {
                            alert('Zu viele Unterseiten! Datei übersprungen.');
                            continue;
                        }
                    }
                }

                // 4. In die Warteschlange einfügen
                if (!uploadQueue.has(targetId)) {
                    uploadQueue.set(targetId, { hires: null, lowres: null, status: 'Wartet' });
                }
                const entry = uploadQueue.get(targetId);
                if (isHires) entry.hires = file;
                else entry.lowres = file;

                renderQueueTable();
            }
        }

        // --- HELFER: Modal für Server-Kollision aufrufen ---
        function showOverwriteModal(id, file, serverSrc, typeStr) {
            return new Promise((resolve) => {
                const modal = document.getElementById('overwrite-modal');
                document.getElementById('overwrite-id-display').textContent = `${id} (${typeStr})`;
                const localUrl = URL.createObjectURL(file);
                document.getElementById('overwrite-new-img').src = localUrl;
                // '?t=' verhindert, dass der Browser ein altes gecachtes Bild anzeigt
                document.getElementById('overwrite-server-img').src =
                    `${serverSrc}?t=${Date.now()}`;

                const cleanup = () => {
                    document
                        .getElementById('btn-overwrite-skip')
                        .removeEventListener('click', onSkip);
                    document
                        .getElementById('btn-overwrite-variant')
                        .removeEventListener('click', onVariant);
                    document
                        .getElementById('btn-overwrite-confirm')
                        .removeEventListener('click', onOverwrite);
                    modal.style.display = 'none';
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

                document.getElementById('btn-overwrite-skip').addEventListener('click', onSkip);
                document
                    .getElementById('btn-overwrite-variant')
                    .addEventListener('click', onVariant);
                document
                    .getElementById('btn-overwrite-confirm')
                    .addEventListener('click', onOverwrite);

                modal.style.display = 'flex';
            });
        }

        // --- HELFER: Freie Variante (a-z) suchen (Lokal + Server) ---
        async function findFreeVariantId(baseId, isHires) {
            const alphabet = 'abcdefghijklmnopqrstuvwxyz';
            const folder = isHires ? 'hires' : 'lowres';

            for (const letter of alphabet) {
                const testId = baseId + letter;

                // 1. Lokal prüfen
                if (uploadQueue.has(testId)) {
                    const testEntry = uploadQueue.get(testId);
                    // Ist lokal schon belegt -> nächster Buchstabe
                    if ((isHires && testEntry.hires) || (!isHires && testEntry.lowres)) continue;
                }
                const serverExists = await new Promise((resolve) => {
                    const img = new Image();
                    img.onload = () => resolve(true);
                    img.onerror = () => resolve(false);
                    img.src = `${baseUrl}/assets/images/comic/${folder}/${testId}.webp`;
                });
                if (!serverExists) return testId;
            }
            return null;
        }

        function renderQueueTable() {
            queueTableBody.innerHTML = '';
            if (uploadQueue.size === 0) {
                queueTableBody.innerHTML =
                    '<tr id="queue-empty-msg"><td colspan="5" class="empty-table-message">Warteschlange ist leer.</td></tr>';
                btnStartMassUpload.disabled = true;
                return;
            }

            btnStartMassUpload.disabled = false;

            // Sortiere nach ID
            const sortedIds = Array.from(uploadQueue.keys()).sort();

            sortedIds.forEach((id) => {
                const data = uploadQueue.get(id);
                const tr = document.createElement('tr');
                const hiresName = data.hires
                    ? data.hires.name
                    : '<span style="color:var(--text-color-faded)">Wird auto-generiert</span>';
                const lowresName = data.lowres
                    ? data.lowres.name
                    : '<span style="color:var(--text-color-faded)">Wird auto-skaliert</span>';

                let statusHtml = `<strong>${data.status}</strong>`;
                if (data.status === 'Lädt...')
                    statusHtml = `<i class="fa-solid fa-spinner fa-spin"></i> Verarbeitung...`;
                if (data.status === 'Fertig')
                    statusHtml = `<span style="color:var(--status-green-text)"><i class="fa-solid fa-check"></i> Fertig</span>`;
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
                queueTableBody.appendChild(tr);
            });
        }

        // Delegation für "Entfernen" Button in der Queue
        queueTableBody.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-remove-queue');
            if (btn) {
                uploadQueue.delete(btn.dataset.id);
                renderQueueTable();
            }
        });

        // Batch Upload ausführen
        btnStartMassUpload.addEventListener('click', async () => {
            btnStartMassUpload.disabled = true;
            const ids = Array.from(uploadQueue.keys());

            for (const id of ids) {
                const data = uploadQueue.get(id);
                if (data.status === 'Fertig') continue; // Bereits erfolgreich überspringen
                if (!data.hires && !data.lowres) continue;

                data.status = 'Lädt...';
                renderQueueTable();

                // Wir packen den Upload in eine rekursive Funktion, um ihn bei "force" zu wiederholen
                const processUpload = async (force = false) => {
                    const fd = new FormData();
                    fd.append('comic_id', id);
                    fd.append('csrf_token', csrfToken);
                    if (force) fd.append('force', '1');
                    if (data.hires) fd.append('upload_hires', data.hires);
                    if (data.lowres) fd.append('upload_lowres', data.lowres);

                    const res = await fetch(`${baseUrl}/api/upload_comic_media`, {
                        method: 'POST',
                        body: fd,
                    });
                    const json = await res.json();

                    // Spezieller Catch für "Comic existiert noch nicht"
                    if (!res.ok && json.error === 'COMIC_NOT_FOUND') {
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
                    return json;
                };

                try {
                    const json = await processUpload(false);
                    if (json.success) data.status = 'Fertig';
                    else data.status = `Fehler: ${json.error}`;
                } catch (err) {
                    console.error(`[Mass Upload] Network error for comic ${id}:`, err);
                    data.status = 'Fehler: Netzwerk';
                }
                renderQueueTable();
            }
            showMsg('Massenverarbeitung abgeschlossen! Warteschlange geleert.', 'green');
            btnStartMassUpload.disabled = false;
        });
    }

    // --- MEDIA GALLERY LOGIK ---
    const mediaSection = document.getElementById('section-media');
    if (mediaSection) {
        const galChars = document.getElementById('media-gallery-characters');
        const galComics = document.getElementById('media-gallery-comics');
        const mediaDropZone = document.getElementById('media-drop-zone');
        const mediaUploadInput = document.getElementById('media-upload-input');
        const mediaSearchInput = document.getElementById('media-search');
        let currentMediaTab = 'characters';

        // TABS UMSCHALTEN
        document.querySelectorAll('.media-tab-btn').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                document.querySelectorAll('.media-tab-btn').forEach((b) => {
                    b.classList.remove('active');
                    b.classList.add('edit');
                });
                e.target.classList.remove('edit');
                e.target.classList.add('active');
                currentMediaTab = e.target.dataset.type;
                document.getElementById('media-view-characters').style.display =
                    currentMediaTab === 'characters' ? 'block' : 'none';
                document.getElementById('media-view-comics').style.display =
                    currentMediaTab === 'comics' ? 'block' : 'none';
                window.loadMedia();
            });
        });

        // LIVE-FILTER LOGIK
        function applyMediaFilter() {
            if (!mediaSearchInput) return;
            const term = mediaSearchInput.value.toLowerCase().trim();
            const activeGallery = currentMediaTab === 'characters' ? galChars : galComics;
            if (activeGallery) {
                activeGallery.querySelectorAll('.preview-box').forEach((box) => {
                    const searchable = box.dataset.search || '';
                    box.style.display = searchable.includes(term) ? 'block' : 'none';
                });
            }
        }
        mediaSearchInput?.addEventListener('input', applyMediaFilter);

        window.loadMedia = async () => {
            try {
                if (currentMediaTab === 'characters') {
                    const res = await fetch(`${baseUrl}/api/list_media`);
                    const json = await res.json();
                    galChars.innerHTML = json.files
                        .map(
                            (f) => `
                        <div class="preview-box" data-search="${f.filename.toLowerCase()}" style="position: relative;">
                            <img src="${f.url}" loading="lazy" style="width: 100%; height: 140px; object-fit: cover; border-radius: 4px;">
                            <p style="font-size: 0.7em; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin: 8px 0;" title="${f.filename}">${f.filename}</p>
                            <button type="button" class="button delete btn-delete-gallery-item" data-type="character" data-id="${f.filename}" style="width: 100%; padding: 5px;"><i class="fa-solid fa-trash"></i> Löschen</button>
                        </div>
                    `
                        )
                        .join('');
                } else if (currentMediaTab === 'comics') {
                    const res = await fetch(`${baseUrl}/api/list_comic_media`);
                    const json = await res.json();
                    galComics.innerHTML = json.files
                        .map(
                            (f) => `
                        <div class="preview-box" data-search="${f.id.toLowerCase()}" style="position: relative; border: 2px solid var(--border-medium);">
                            <img src="${f.url}" loading="lazy" style="width: 100%; height: 225px; object-fit: contain; border-radius: 4px; background: #fff;">
                            <h4 style="margin: 8px 0 4px 0;" class="mono">${f.id}</h4>
                            <div style="display: flex; gap: 4px; justify-content: center; margin-bottom: 8px; font-size: 0.75em; color: var(--text-color-faded);">
                                <span style="color: ${f.has_hires ? 'var(--status-green-text)' : 'inherit'}">HR</span> |
                                <span style="color: ${f.has_lowres ? 'var(--status-green-text)' : 'inherit'}">LR</span> |
                                <span style="color: ${f.has_social ? 'var(--status-green-text)' : 'inherit'}">SM</span> |
                                <span style="color: ${f.has_thumb ? 'var(--status-green-text)' : 'inherit'}">TN</span>
                            </div>
                            <button type="button" class="button delete btn-delete-gallery-item" data-type="comic" data-id="${f.id}" style="width: 100%; padding: 5px;"><i class="fa-solid fa-trash"></i> Alle 4 löschen</button>
                        </div>
                    `
                        )
                        .join('');
                }

                // Filter direkt anwenden, falls beim Laden schon Text im Suchfeld steht
                applyMediaFilter();
            } catch (err) {
                console.error('[Media Gallery] Failed to load media list:', err);
            }
        };

        // ZENTRALE EVENT DELEGATION FÜR LÖSCHEN (BEIDE TABS)
        mediaSection.addEventListener('click', async (e) => {
            const btn = e.target.closest('.btn-delete-gallery-item');
            if (!btn) return;
            const type = btn.dataset.type;
            const id = btn.dataset.id;

            if (type === 'character') {
                if (!confirm(`Datei ${id} wirklich löschen?`)) return;
                const fd = new FormData();
                fd.append('filename', id);
                fd.append('csrf_token', csrfToken);
                await fetch(`${baseUrl}/api/delete_media`, { method: 'POST', body: fd });
            } else if (type === 'comic') {
                const check = prompt(
                    `ACHTUNG: Dies löscht physisch ALLE Varianten der Comicseite ${id}.\nTippe "${id}" zum Bestätigen:`
                );
                if (check === id) {
                    const fd = new FormData();
                    fd.append('comic_id', id);
                    fd.append('csrf_token', csrfToken);
                    await fetch(`${baseUrl}/api/delete_comic_media`, { method: 'POST', body: fd });
                }
            }
            window.loadMedia();
        });

        // --- DRAG & DROP LOGIK FÜR CHARAKTERE ---
        if (mediaDropZone && mediaUploadInput) {
            mediaDropZone.addEventListener('click', () => mediaUploadInput.click());
            mediaDropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                mediaDropZone.style.backgroundColor = 'var(--table-row-hover)';
            });
            mediaDropZone.addEventListener('dragleave', (e) => {
                e.preventDefault();
                mediaDropZone.style.backgroundColor = 'var(--table-row-even)';
            });
            mediaDropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                mediaDropZone.style.backgroundColor = 'var(--table-row-even)';
                if (e.dataTransfer.files.length) {
                    mediaUploadInput.files = e.dataTransfer.files;
                    mediaUploadInput.dispatchEvent(new Event('change'));
                }
            });
            mediaUploadInput.addEventListener('change', async () => {
                if (mediaUploadInput.files.length === 0) return;
                showMsg(
                    '<i class="fa-solid fa-spinner fa-spin"></i> Lade Bilder hoch...',
                    'orange'
                );
                const fd = new FormData();
                for (const file of mediaUploadInput.files) fd.append('files[]', file);
                fd.append('csrf_token', csrfToken);
                try {
                    const res = await fetch(`${baseUrl}/api/upload_media`, {
                        method: 'POST',
                        body: fd,
                    });
                    const json = await res.json();
                    if (json.success) {
                        showMsg(`<i class="fa-solid fa-check"></i> ${json.message}`, 'green');
                        window.loadMedia();
                    } else {
                        showMsg(
                            `<i class="fa-solid fa-triangle-exclamation"></i> ${json.error}`,
                            'red'
                        );
                    }
                } catch (err) {
                    console.error('[Media Upload] File upload failed:', err);
                    showMsg('<i class="fa-solid fa-bomb"></i> Fehler beim Upload', 'red');
                }
                mediaUploadInput.value = ''; // WICHTIG: Setzt das Input-Feld zurück
            });
        }
        window.loadMedia();
    }

    // --- DYNAMISCHES BILDER GALERIE MODAL (Für Charaktere) ---
    let currentGalleryTargetInput = null;
    document.querySelectorAll('.btn-open-gallery-dynamic').forEach((btn) => {
        btn.addEventListener('click', async (e) => {
            const button = e.target.closest('.btn-open-gallery-dynamic');
            const targetId = button.dataset.target;
            const folder = button.dataset.folder;
            currentGalleryTargetInput = document.getElementById(targetId);

            const modalTitle = document.getElementById('gallery-modal-title');
            if (modalTitle) modalTitle.textContent = `Galerie: ${folder}`;

            const modal = document.getElementById('gallery-modal');
            const galGrid = document.getElementById('gallery-grid-dynamic');
            if (modal) modal.style.display = 'flex';
            if (galGrid) galGrid.innerHTML = '<p>Lade Bilder...</p>';

            try {
                const res = await fetch(`${baseUrl}/api/list_media?folder=${folder}`);
                const json = await res.json();
                if (json.files.length === 0) {
                    galGrid.innerHTML = '<p style="font-style:italic;">Ordner ist leer.</p>';
                    return;
                }

                galGrid.innerHTML = json.files
                    .map(
                        (f) => `
                    <div class="char-selection-item gallery-item" data-filename="${f.filename}" style="position: relative;">
                        <img src="${f.url}" loading="lazy" style="width:60px;height:60px;object-fit:cover;border-radius:5px;">
                        <span style="font-size: 0.75em; word-break: break-all;">${f.filename}</span>
                    </div>
                `
                    )
                    .join('');

                galGrid.querySelectorAll('.gallery-item').forEach((item) => {
                    item.addEventListener('click', function () {
                        window.isDirty = true;
                        if (currentGalleryTargetInput) {
                            if (currentGalleryTargetInput.id === 'ref_sheets_urls') {
                                const vals = currentGalleryTargetInput.value
                                    .split(',')
                                    .map((s) => s.trim())
                                    .filter(Boolean);
                                if (!vals.includes(this.dataset.filename))
                                    vals.push(this.dataset.filename);
                                currentGalleryTargetInput.value = vals.join(', ');
                            } else {
                                currentGalleryTargetInput.value = this.dataset.filename;
                            }
                            currentGalleryTargetInput.dispatchEvent(new Event('input'));
                        }
                        if (modal) modal.style.display = 'none';
                    });
                });
            } catch (err) {
                galGrid.innerHTML = '<p style="color:red;">Fehler beim Laden der Galerie.</p>';
            }
        });
    });

    document.querySelectorAll('.btn-close-gallery-modal').forEach((btn) => {
        btn.addEventListener('click', () => {
            const modal = document.getElementById('gallery-modal');
            if (modal) modal.style.display = 'none';
        });
    });

    // --- NEWSLETTER MANUELL AUSLÖSEN ---
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.btn-trigger-newsletter');
        if (!btn) return;
        e.preventDefault();

        const type = btn.dataset.type;
        const pageNumber = btn.dataset.page;
        const comicName = 'TwoKinds';
        const pageUrl = btn.dataset.url;

        if (
            !confirm(
                `Bist du sicher, dass du den ${type}-Newsletter für Seite ${pageNumber} versenden möchtest?`
            )
        )
            return;

        const formData = new window.FormData();
        formData.append('type', type);
        formData.append('comic_name', comicName);
        formData.append('page_number', pageNumber);
        formData.append('page_url', pageUrl);
        formData.append('csrf_token', csrfToken);

        const origText = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sende...';
        btn.disabled = true;

        try {
            const res = await fetch(`${baseUrl}/api/admin_trigger_newsletter`, {
                method: 'POST',
                body: formData,
            });
            const json = await res.json();
            if (json.success) showMsg(`<i class="fa-solid fa-check"></i> ${json.message}`, 'green');
            else
                showMsg(
                    `<i class="fa-solid fa-triangle-exclamation"></i> Fehler: ${json.error}`,
                    'red'
                );
        } catch (err) {
            showMsg('<i class="fa-solid fa-bomb"></i> Netzwerkfehler.', 'red');
        }
        btn.innerHTML = origText;
        btn.disabled = false;
    });

    // --- LOGOUT ---
    document.getElementById('admin-logout-btn')?.addEventListener('click', async (e) => {
        e.preventDefault();
        const fd = new window.FormData();
        fd.append('csrf_token', csrfToken);
        await fetch(`${baseUrl}/api/admin_logout`, { method: 'POST', body: fd });
        window.location.reload();
    });

    // --- SOCIAL MEDIA CROPPER LOGIK ---
    let cropperInstance = null;

    // Globale Funktion, um das Modal von überall aus aufzurufen
    window.openCropperModal = (comicId, imgUrl) => {
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
            if (cropperInstance) cropperInstance.destroy();
            cropperInstance = new Cropper(cropperImage, {
                aspectRatio: 1200 / 630, // Der goldene Open-Graph Standard (1.91:1)
                viewMode: 1, // Rahmen darf das Bild nicht verlassen
                autoCropArea: 0.8, // Startgröße (80% des Bildes)
                background: false, // Grid-Hintergrund verstecken
                zoomable: false, // Wir wollen nur zuschneiden, nicht zoomen
                guides: true,
            });
        }, 100);
    };

    // Zentrale Klick-Events für den Cropper (Ausfallsicher delegiert)
    document.addEventListener('click', async (e) => {
        // 1. Cropper öffnen
        if (e.target.closest('#btn-open-cropper')) {
            e.preventDefault();
            const comicId = document.getElementById('comic_id')?.value.trim();
            if (!comicId || comicId.length !== 8) {
                alert('Bitte zuerst eine gültige 8-stellige Comic-ID eingeben oder speichern!');
                return;
            }
            const imgUrl = `${baseUrl}/assets/images/comic/hires/${comicId}.webp?t=${Date.now()}`;
            const testImg = new Image();
            testImg.onload = () => window.openCropperModal(comicId, imgUrl);
            testImg.onerror = () =>
                alert(
                    'Es existiert noch kein Hires-Bild für diesen Comic. Lade die Bilder zuerst hoch.'
                );
            testImg.src = imgUrl;
        }

        // 2. Cropper schließen (Abbrechen oder X)
        if (e.target.closest('.btn-close-cropper-modal')) {
            e.preventDefault();
            const cropperModal = document.getElementById('cropper-modal');
            if (cropperModal) cropperModal.style.display = 'none';
            if (cropperInstance) {
                cropperInstance.destroy();
                cropperInstance = null;
            }
        }

        // 3. Zuschneiden & Speichern
        if (e.target.closest('#btn-save-crop')) {
            e.preventDefault();
            const btnSaveCrop = e.target.closest('#btn-save-crop');
            if (!cropperInstance) return;

            const cropData = cropperInstance.getData(true);
            const comicId = document.getElementById('crop_comic_id').value;
            const origText = btnSaveCrop.innerHTML;
            btnSaveCrop.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Schneide zu...';
            btnSaveCrop.style.pointerEvents = 'none';

            const fd = new FormData();
            fd.append('comic_id', comicId);
            fd.append('x', cropData.x);
            fd.append('y', cropData.y);
            fd.append('width', cropData.width);
            fd.append('height', cropData.height);
            fd.append('csrf_token', csrfToken);

            try {
                const res = await fetch(`${baseUrl}/api/crop_social_media`, {
                    method: 'POST',
                    body: fd,
                });
                const json = await res.json();

                if (json.success) {
                    showMsg(`<i class="fa-solid fa-check"></i> ${json.message}`, 'green');

                    // 1. Cropper Modal sofort schließen
                    const cropperModal = document.getElementById('cropper-modal');
                    if (cropperModal) cropperModal.style.display = 'none';
                    if (cropperInstance) {
                        cropperInstance.destroy();
                        cropperInstance = null;
                    }

                    const timestamp = Date.now(); // Cache-Buster

                    // 2. Vorschau-Bild im Comic-Modal aktualisieren
                    const prevSocial = document.getElementById('prev-comic-social');
                    if (prevSocial)
                        prevSocial.src = `${baseUrl}/assets/images/comic/socialmedia/${comicId}.jpg?t=${timestamp}`;

                    // 3. Mini-Vorschau in der Haupttabelle im Hintergrund mit aktualisieren!
                    const tableRow = document
                        .querySelector(`.btn-delete-comic[data-id="${comicId}"]`)
                        ?.closest('tr');
                    if (tableRow) {
                        const tableThumb = tableRow.querySelectorAll('img')[1];
                        if (tableThumb) {
                            tableThumb.src = `${baseUrl}/assets/images/comic/socialmedia/${comicId}.jpg?t=${timestamp}`;
                            tableThumb.style.display = 'inline-block';
                        }
                    }
                } else {
                    showMsg(
                        `<i class="fa-solid fa-triangle-exclamation"></i> ${json.error}`,
                        'red'
                    );
                }
            } catch (err) {
                showMsg(`<i class="fa-solid fa-bomb"></i> Fehler: ${err.message}`, 'red');
            }
            btnSaveCrop.innerHTML = origText;
            btnSaveCrop.style.pointerEvents = 'auto';
        }
    });
});
