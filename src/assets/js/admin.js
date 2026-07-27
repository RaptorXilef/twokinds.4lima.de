document.addEventListener('DOMContentLoaded', () => {
    const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfTokenMeta?.content ?? '';
    const statusBox = document.getElementById('global-status-message');
    const baseUrlMatch = window.location.pathname.match(/^(.*)\/admin/);
    const baseUrl = baseUrlMatch ? baseUrlMatch[1] : '';

    // --- UNSAVED CHANGES WARNING ---
    let isDirty = false;
    window.addEventListener('beforeunload', (e) => {
        if (isDirty) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    // Registriere Änderungen an Textfeldern
    document.addEventListener('input', (e) => {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
            isDirty = true;
        }
    });

    // Globaler Listener für kaputte Bilder (CSP-Konform für onerror)
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
                isDirty = true;
            });
    }

    function showMsg(text, type) {
        if (!statusBox) return;
        statusBox.className = `status-message visible status-${type}`;
        statusBox.innerHTML = text;
        setTimeout(() => {
            statusBox.className = 'status-message';
        }, 5000);
    }

    async function sendApiRequest(endpoint, formData, btnElement = null, origBtnHtml = '') {
        formData.append('csrf_token', csrfToken);
        try {
            const response = await fetch(`${baseUrl}/api/${endpoint}`, {
                method: 'POST',
                body: formData,
            });
            const data = await response.json();
            if (data.success) {
                isDirty = false;
                window.location.reload();
            } else {
                showMsg(`<i class="fa-solid fa-triangle-exclamation"></i> ${data.error}`, 'red');
                // Button wieder freigeben bei Server-Fehler (z.B. 400 Bad Request)
                if (btnElement) {
                    btnElement.innerHTML = origBtnHtml;
                    btnElement.style.pointerEvents = 'auto';
                }
            }
        } catch {
            showMsg('<i class="fa-solid fa-bomb"></i> Netzwerkfehler.', 'red');
            // Button wieder freigeben bei Absturz (z.B. 500 Internal Server Error)
            if (btnElement) {
                btnElement.innerHTML = origBtnHtml;
                btnElement.style.pointerEvents = 'auto';
            }
        }
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
    document.querySelectorAll('.content-section').forEach((sec) => {
        sec.classList.remove('active');
    });
    document.querySelectorAll('.tab-link').forEach((l) => {
        l.classList.remove('active');
    });

    const targetSection = document.getElementById(activeTab);
    const targetLink = document.querySelector(`.tab-link[data-target="${activeTab}"]`);
    if (targetSection) targetSection.classList.add('active');
    if (targetLink) targetLink.classList.add('active');

    document.querySelectorAll('.tab-link').forEach((link) => {
        link.addEventListener('click', function () {
            if (this.dataset.target) {
                sessionStorage.setItem('activeAdminTab', this.dataset.target);
                document.querySelectorAll('.content-section').forEach((sec) => {
                    sec.classList.remove('active');
                });
                document.querySelectorAll('.tab-link').forEach((l) => {
                    l.classList.remove('active');
                });
                document.getElementById(this.dataset.target)?.classList.add('active');
                this.classList.add('active');
            }
        });
    });

    // --- PAGINIERUNG & INTELLIGENTE SUCHE ---
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
                if (startPage > 2) {
                    comicPaginationContainer.appendChild(createBtn('...', true, false, null));
                }
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
                if (endPage < totalPages - 1) {
                    comicPaginationContainer.appendChild(createBtn('...', true, false, null));
                }
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
            const filteredRows = allComicRows.filter((row) => {
                return row.textContent.toLowerCase().includes(currentSearchQuery.toLowerCase());
            });

            // 2. Limits & Seiten berechnen
            const totalItems = filteredRows.length;
            const limit = itemsPerPage === 'all' ? totalItems : parseInt(itemsPerPage, 10);
            const totalPages = limit > 0 ? Math.ceil(totalItems / limit) : 1;

            if (currentPage > totalPages) currentPage = totalPages || 1;

            const startIndex = limit === totalItems ? 0 : (currentPage - 1) * limit;
            const endIndex = startIndex + limit;

            allComicRows.forEach((row) => {
                row.style.display = 'none';
            });
            filteredRows.slice(startIndex, endIndex).forEach((row) => {
                row.style.display = '';
            });

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

    // --- COMIC MODAL: LIVE PREVIEWS & AUTO-FILL ---
    const hiddenSelect = document.getElementById('hidden-comic-chars');
    const comicIdInput = document.getElementById('comic_id');
    const origUrlInput = document.getElementById('url_originalbild');
    const origSketchInput = document.getElementById('url_originalsketch');

    const prevLocal = document.getElementById('prev-comic-local');
    const prevOrig = document.getElementById('prev-comic-orig');
    const prevSketch = document.getElementById('prev-comic-sketch');
    const prevSocial = document.getElementById('prev-comic-social');

    // Hilfsfunktion testet Endungen durch, indem sie unsichtbare Image-Objekte lädt
    function loadPreviewWithProbe(imgElement, basePath, extensions, fallbackUrl) {
        let i = 0;
        imgElement.src = 'https://placehold.co/100x140?text=L%C3%A4dt...';

        const testNext = () => {
            if (i >= extensions.length) {
                imgElement.src = fallbackUrl;
                return;
            }
            const ext = extensions[i++];
            const testImg = new Image();
            testImg.onload = () => {
                imgElement.src = testImg.src;
            };
            testImg.onerror = testNext; // Wenn Fehler (404), versuche die nächste Endung
            testImg.src = `${basePath}.${ext}`;
        };
        testNext();
    }

    function updateComicPreviews() {
        const idVal = comicIdInput?.value.trim() ?? '';
        const oldIdVal = document.getElementById('old_comic_id')?.value.trim() ?? '';

        // Nutze für die Vorschau das alte Bild, solange nicht gespeichert wurde!
        const localPreviewId = oldIdVal !== '' ? oldIdVal : idVal;

        const origVal = origUrlInput?.value.trim() ?? '';
        const sketchVal = origSketchInput?.value.trim() ?? '';

        const remoteExts = ['png', 'jpg', 'gif', 'jpeg', 'webp'];
        const localExts = ['webp', 'png', 'jpg', 'jpeg', 'gif'];
        const fallback = 'https://placehold.co/100x140?text=Fehlt';

        // 1. Lokal Lowres
        if (prevLocal) {
            if (localPreviewId.length >= 8) {
                loadPreviewWithProbe(
                    prevLocal,
                    `${baseUrl}/assets/images/comic/lowres/${localPreviewId}`,
                    localExts,
                    fallback
                );
            } else {
                prevLocal.src = fallback;
            }
        }

        // 2. Keenspot Original
        if (prevOrig) {
            if (origVal !== '') {
                if (origVal.startsWith('http')) {
                    prevOrig.src = origVal;
                } else if (origVal.includes('.')) {
                    prevOrig.src = `https://cdn.twokinds.keenspot.com/comics/${origVal}`;
                } else {
                    loadPreviewWithProbe(
                        prevOrig,
                        `https://cdn.twokinds.keenspot.com/comics/${origVal}`,
                        remoteExts,
                        fallback
                    );
                }
            } else {
                prevOrig.src = fallback;
            }
        }

        // 3. Keenspot Sketch
        if (prevSketch) {
            if (sketchVal !== '') {
                if (sketchVal.startsWith('http')) {
                    prevSketch.src = sketchVal;
                } else if (sketchVal.includes('.')) {
                    prevSketch.src = `https://twokindscomic.com/images/${sketchVal}`;
                } else {
                    // Hängt _sketch an, wenn es fehlt
                    let baseSketch = sketchVal;
                    if (!baseSketch.endsWith('_sketch')) baseSketch += '_sketch';
                    loadPreviewWithProbe(
                        prevSketch,
                        `https://twokindscomic.com/images/${baseSketch}`,
                        remoteExts,
                        fallback
                    );
                }
            } else {
                prevSketch.src = fallback;
            }
        }

        // 4. Social Media (JPG)
        if (prevSocial) {
            if (localPreviewId.length >= 8) {
                // Suchen nach .jpg (primär), danach Fallback auf webp/png
                loadPreviewWithProbe(
                    prevSocial,
                    `${baseUrl}/assets/images/comic/socialmedia/${localPreviewId}`,
                    ['jpg', 'jpeg', 'webp', 'png'],
                    'https://placehold.co/191x100?text=Fehlt'
                );
            } else {
                prevSocial.src = 'https://placehold.co/191x100?text=Fehlt';
            }
        }
    }

    // Event Listener für die 3 Felder
    comicIdInput?.addEventListener('input', updateComicPreviews);
    origUrlInput?.addEventListener('input', updateComicPreviews);
    origSketchInput?.addEventListener('input', updateComicPreviews);

    // Auto-Fill nach Verlassen der Comic-ID (NUR bei neuen Comics!)
    comicIdInput?.addEventListener('blur', () => {
        const val = comicIdInput.value.trim();
        // !comicIdInput.readOnly bedeutet: Wir sind im "Neu anlegen" Modus
        if (val.length === 8 && !comicIdInput.readOnly) {
            // YYYYMMDD
            if (origUrlInput.value === '') origUrlInput.value = val;
            if (origSketchInput.value === '') origSketchInput.value = val;
            updateComicPreviews();
        }
    });

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

    window.openComicModal = (data = null) => {
        const form = document.getElementById('comic-form');
        form?.reset();

        // Alle Selections zurücksetzen (Charakter-Avatare)
        if (hiddenSelect) {
            Array.from(hiddenSelect.options).forEach((opt) => {
                opt.selected = false;
            });
        }
        document.querySelectorAll('.char-selection-item').forEach((item) => {
            item.classList.remove('selected');
        });

        const oldIdInput = document.getElementById('old_comic_id');

        if (data) {
            const titleEl = document.getElementById('modal-title-comic');

            if (titleEl) titleEl.textContent = `Comic bearbeiten: ${data.id}`;

            if (comicIdInput) {
                comicIdInput.value = data.id;
                comicIdInput.readOnly = false; // HIER ENTSPERRT!
            }
            if (oldIdInput) oldIdInput.value = data.id;

            const typeInput = document.getElementById('type');
            if (typeInput) typeInput.value = data.type;

            const nameInput = document.getElementById('name');
            if (nameInput) nameInput.value = data.name;

            const chapInput = document.getElementById('chapter_id');
            if (chapInput) chapInput.value = data.chapterId;

            if (origUrlInput) origUrlInput.value = data.originalUrl;
            if (origSketchInput) origSketchInput.value = data.sketchUrl;

            if (typeof $.fn.trumbowyg !== 'undefined') {
                $('#transcript').trumbowyg('html', data.transcript);
            }

            if (data.characters && Array.isArray(data.characters)) {
                data.characters.forEach((charId) => {
                    const opt = hiddenSelect?.querySelector(`option[value="${charId}"]`);
                    if (opt) opt.selected = true;
                    document
                        .querySelectorAll(`.char-selection-item[data-char-id="${charId}"]`)
                        .forEach((item) => {
                            item.classList.add('selected');
                        });
                });
            }

            // Buttons einblenden und mit Daten füttern
            const btnNlTrans = document.getElementById('btn-nl-trans');
            const btnNlFull = document.getElementById('btn-nl-full');
            if (btnNlTrans && btnNlFull) {
                btnNlTrans.style.display = 'inline-block';
                btnNlFull.style.display = 'inline-block';

                // NEU: Holt das Protokoll und die Domain (http://...local oder https://...de) automatisch
                const pageUrl = `${window.location.origin}${baseUrl}/comic/${data.id}`;

                btnNlTrans.dataset.page = data.id;
                btnNlTrans.dataset.url = pageUrl;
                btnNlFull.dataset.page = data.id;
                btnNlFull.dataset.url = pageUrl;
            }
        } else {
            const titleEl = document.getElementById('modal-title-comic');

            if (titleEl) titleEl.textContent = 'Neuen Comic anlegen';

            if (comicIdInput) comicIdInput.readOnly = false;
            if (oldIdInput) oldIdInput.value = '';

            // Höchstes Kapitel suchen und automatisch eintragen
            const chapInput = document.getElementById('chapter_id');
            if (chapInput) {
                const chapterOptions = Array.from(
                    document.querySelectorAll('#chapter-datalist option')
                )
                    .map((opt) => parseInt(opt.value, 10))
                    .filter((num) => !Number.isNaN(num));

                if (chapterOptions.length > 0) {
                    chapInput.value = Math.max(...chapterOptions).toString();
                } else {
                    chapInput.value = '';
                }
            }

            if (typeof $.fn.trumbowyg !== 'undefined') {
                $('#transcript').trumbowyg('empty');
            }

            // Buttons einblenden und mit Daten füttern
            const btnNlTrans = document.getElementById('btn-nl-trans');
            const btnNlFull = document.getElementById('btn-nl-full');
            if (btnNlTrans && btnNlFull) {
                btnNlTrans.style.display = 'none';
                btnNlFull.style.display = 'none';
            }
        }

        // Merken fürs spätere Hervorheben bei "Abbrechen"
        if (comicIdInput) {
            sessionStorage.setItem('highlightEntityIdCancel', comicIdInput.value.trim());
        }

        updateComicPreviews(); // Vorschauen direkt triggern
        const modal = document.getElementById('comic-modal');
        if (modal) modal.style.display = 'flex';
    };

    // --- BILD UPLOAD DRAG & DROP FÜR COMICS ---
    function setupComicDropZone(zoneId, inputId, previewId) {
        const dropZone = document.getElementById(zoneId);
        const fileInput = document.getElementById(inputId);
        const previewName = document.getElementById(previewId);

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
            dropZone.style.borderColor = 'var(--status-green-text)';
            dropZone.style.backgroundColor = 'var(--status-green-bg)';
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                isDirty = true;
                if (previewName) previewName.textContent = `Ausgewählt: ${fileInput.files[0].name}`;
            }
        });

        fileInput.addEventListener('change', () => {
            const file = fileInput.files?.[0];
            if (file) {
                isDirty = true;
                dropZone.style.borderColor = 'var(--status-green-text)';
                dropZone.style.backgroundColor = 'var(--status-green-bg)';
                if (previewName) previewName.textContent = `Ausgewählt: ${file.name}`;
            }
        });
    }

    setupComicDropZone('comic-drop-zone-hires', 'upload_hires', 'preview-name-hires');
    setupComicDropZone('comic-drop-zone-lowres', 'upload_lowres', 'preview-name-lowres');

    // Erweitere die bestehende openComicModal Funktion, um die Boxen beim Öffnen zu leeren:
    const originalOpenComicModal = window.openComicModal;
    window.openComicModal = (data = null) => {
        originalOpenComicModal(data);

        // Upload-Felder resetten
        document.getElementById('preview-name-hires').textContent = '';
        document.getElementById('preview-name-lowres').textContent = '';
        document.getElementById('comic-drop-zone-hires').style.borderColor = 'var(--border-medium)';
        document.getElementById('comic-drop-zone-hires').style.backgroundColor =
            'var(--table-row-even)';
        document.getElementById('comic-drop-zone-lowres').style.borderColor =
            'var(--border-medium)';
        document.getElementById('comic-drop-zone-lowres').style.backgroundColor =
            'var(--table-row-even)';
    };

    window.closeComicModal = () => {
        const modal = document.getElementById('comic-modal');
        if (modal) modal.style.display = 'none';
        const cancelId = sessionStorage.getItem('highlightEntityIdCancel');
        if (cancelId) {
            highlightAndScroll(cancelId);
            sessionStorage.removeItem('highlightEntityIdCancel');
        }
    };

    // Toggle Alphabetical / Grouped View
    let charViewAlpha = true;
    document.getElementById('btn-toggle-char-view')?.addEventListener('click', (e) => {
        charViewAlpha = !charViewAlpha;
        e.target.innerHTML = charViewAlpha
            ? '<i class="fa-solid fa-layer-group"></i> Gruppiert anzeigen'
            : '<i class="fa-solid fa-font"></i> Alphabetisch anzeigen';
        const alphaView = document.getElementById('view-chars-alpha');
        const groupedView = document.getElementById('view-chars-grouped');
        if (alphaView) alphaView.style.display = charViewAlpha ? 'flex' : 'none';
        if (groupedView) groupedView.style.display = charViewAlpha ? 'none' : 'block';
    });

    // --- CHARAKTER MODAL ---
    const charPreviewImg = document.getElementById('char-preview-img');
    const charDisplayId = document.getElementById('char-display-id');
    const picUrlInput = document.getElementById('pic_url');

    // Live update preview on text input
    picUrlInput?.addEventListener('input', (e) => {
        const val = e.target.value.trim();
        if (charPreviewImg) {
            if (val) {
                charPreviewImg.src = `${baseUrl}/assets/images/characters/profiles/${val}`;
            } else {
                charPreviewImg.src = 'https://placehold.co/120x120?text=Kein+Bild';
            }
        }
    });

    window.openCharModal = (data = null) => {
        const form = document.getElementById('char-form');
        form?.reset();

        const previewNameEl = document.getElementById('upload-preview-name');
        if (previewNameEl) previewNameEl.textContent = '';

        const idInput = document.getElementById('character_id');
        const nameInput = document.getElementById('char_name');
        const fullInput = document.getElementById('full_name');
        const altInput = document.getElementById('alt_names');
        const genderInput = document.getElementById('gender');
        const ageInput = document.getElementById('age');
        const rankInput = document.getElementById('char_rank');
        const speciesInput = document.getElementById('species');
        const subInput = document.getElementById('subspecies');
        const langInput = document.getElementById('languages');
        const picUrlInput = document.getElementById('pic_url');

        // File-Inputs immer leeren und Rahmen zurücksetzen
        ['profile_image', 'main_pic', 'swatch_pic', 'ref_sheets'].forEach((id) => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        [
            'char-drop-zone',
            'char-drop-zone-main',
            'char-drop-zone-swatch',
            'char-drop-zone-refs',
        ].forEach((id) => {
            const el = document.getElementById(id);
            if (el) {
                el.style.borderColor = 'var(--border-medium)';
                el.style.backgroundColor = 'var(--table-row-even)';
            }
        });

        // Die Text-Inputs für die erweiterten Bilder holen
        const mainPicInput = document.getElementById('main_pic_url');
        const swatchPicInput = document.getElementById('swatch_pic_url');
        const refSheetsInput = document.getElementById('ref_sheets_urls');

        if (data) {
            const titleEl = document.getElementById('modal-title-char');
            if (titleEl) titleEl.textContent = 'Charakter bearbeiten';

            if (idInput) idInput.value = data.id;
            if (charDisplayId) charDisplayId.textContent = `ID: ${data.id}`;
            if (nameInput) nameInput.value = data.name;
            if (fullInput) fullInput.value = data.fullName ?? '';
            if (altInput) altInput.value = data.altNames ?? '';
            if (genderInput) genderInput.value = data.gender ?? '';
            if (ageInput) ageInput.value = data.age ?? '';
            if (rankInput) rankInput.value = data.rank ?? '';
            if (speciesInput) speciesInput.value = data.species ?? '';
            if (subInput) subInput.value = data.subspecies ?? '';
            if (langInput) langInput.value = data.languages ?? '';
            if (picUrlInput) picUrlInput.value = data.picUrl ?? '';

            if (typeof $.fn.trumbowyg !== 'undefined') {
                $('#char_description').trumbowyg('html', data.description);
            }

            if (charPreviewImg) {
                charPreviewImg.src = data.picUrl
                    ? `${baseUrl}/assets/images/characters/profiles/${data.picUrl}`
                    : 'https://placehold.co/120x120?text=Kein+Bild';
            }

            // Erweiterte Bilder über Textfelder befüllen und Previews triggern
            if (mainPicInput) {
                mainPicInput.value = data.mainPic ?? '';
                mainPicInput.dispatchEvent(new Event('input'));
            }
            if (swatchPicInput) {
                swatchPicInput.value = data.swatchPic ?? '';
                swatchPicInput.dispatchEvent(new Event('input'));
            }
            if (refSheetsInput) {
                refSheetsInput.value = data.refSheets?.length ? data.refSheets.join(', ') : '';
                refSheetsInput.dispatchEvent(new Event('input'));
            }
        } else {
            const titleEl = document.getElementById('modal-title-char');
            if (titleEl) titleEl.textContent = 'Neuen Charakter anlegen';
            if (idInput) idInput.value = 'new';
            if (charDisplayId) charDisplayId.textContent = 'ID: NEW';

            if (fullInput) fullInput.value = '';
            if (altInput) altInput.value = '';
            if (genderInput) genderInput.value = '';
            if (ageInput) ageInput.value = '';
            if (rankInput) rankInput.value = '';
            if (speciesInput) speciesInput.value = '';
            if (subInput) subInput.value = '';
            if (langInput) langInput.value = '';
            if (picUrlInput) picUrlInput.value = '';

            if (typeof $.fn.trumbowyg !== 'undefined') {
                $('#char_description').trumbowyg('empty');
            }
            if (charPreviewImg) charPreviewImg.src = 'https://placehold.co/120x120?text=Kein+Bild';

            // Previews leeren bei Neu-Anlage
            if (mainPicInput) {
                mainPicInput.value = '';
                mainPicInput.dispatchEvent(new Event('input'));
            }
            if (swatchPicInput) {
                swatchPicInput.value = '';
                swatchPicInput.dispatchEvent(new Event('input'));
            }
            if (refSheetsInput) {
                refSheetsInput.value = '';
                refSheetsInput.dispatchEvent(new Event('input'));
            }
        }

        if (idInput) {
            sessionStorage.setItem('highlightEntityIdCancel', idInput.value.trim());
        }

        const modal = document.getElementById('char-modal');
        if (modal) modal.style.display = 'flex';
    };

    window.closeCharModal = () => {
        const modal = document.getElementById('char-modal');
        if (modal) modal.style.display = 'none';
        const cancelId = sessionStorage.getItem('highlightEntityIdCancel');
        if (cancelId) {
            highlightAndScroll(cancelId);
            sessionStorage.removeItem('highlightEntityIdCancel');
        }
    };

    // --- BILD UPLOAD DRAG & DROP ---

    // Universelle Drag & Drop Setup Funktion (Für Profil, Main, Swatch)
    function setupCharDropZone(zoneId, inputId, previewImgId, previewTextId = null) {
        const zone = document.getElementById(zoneId);
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewImgId);
        const previewText = document.getElementById(previewTextId);

        if (!zone || !input) return;

        zone.addEventListener('click', () => input.click());

        zone.addEventListener('dragover', (e) => {
            e.preventDefault();
            zone.style.borderColor = 'var(--link-color)';
            zone.style.backgroundColor = 'var(--table-row-hover)';
        });

        zone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            zone.style.borderColor = 'var(--border-medium)';
            zone.style.backgroundColor = 'var(--content-bg)';
        });

        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            zone.style.borderColor = 'var(--status-green-text)';
            zone.style.backgroundColor = 'var(--status-green-bg)';
            if (e.dataTransfer.files.length) {
                input.files = e.dataTransfer.files;
                input.dispatchEvent(new Event('change'));
            }
        });

        input.addEventListener('change', () => {
            if (input.files?.[0]) {
                isDirty = true;
                zone.style.borderColor = 'var(--status-green-text)';
                zone.style.backgroundColor = 'var(--status-green-bg)';

                if (previewText) {
                    previewText.textContent = `Bereit: ${input.files[0].name}`;
                }

                // Live Vorschau setzen
                if (preview) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        preview.src = e.target.result;
                        if (preview.style.display === 'none') preview.style.display = 'block';
                    };
                    reader.readAsDataURL(input.files[0]);
                }

                // Falls es das Profilbild ist, das Inputfeld für Text zurücksetzen
                if (inputId === 'profile_image' && picUrlInput) {
                    picUrlInput.value = '';
                }
            }
        });
    }

    // 1. Profilbild (Kleines Avatar)
    setupCharDropZone('char-drop-zone', 'profile_image', 'char-preview-img', 'upload-preview-name');

    // 2. Erweitert: Hauptbild & Swatch
    setupCharDropZone('char-drop-zone-main', 'main_pic', 'preview-img-main');
    setupCharDropZone('char-drop-zone-swatch', 'swatch_pic', 'preview-img-swatch');

    // 3. Erweitert: Reference Sheets (Multiple)
    const zoneRefs = document.getElementById('char-drop-zone-refs');
    const inputRefs = document.getElementById('ref_sheets');
    const containerRefs = document.getElementById('preview-container-refs');

    // Dieser Speicher hält alle Dateien, bis das Formular abgeschickt wird
    let accumulatedRefFiles = new DataTransfer();

    function updateRefPreviews() {
        if (!containerRefs) return;
        isDirty = true;
        if (zoneRefs) {
            zoneRefs.style.borderColor = 'var(--status-green-text)';
            zoneRefs.style.backgroundColor = 'var(--status-green-bg)';
        }

        // LINTER-FIX: Entferne alte "neue" Bilder mit geschweiften Klammern
        Array.from(containerRefs.querySelectorAll('img.is-new')).forEach((img) => {
            img.remove();
        });

        // Alle Dateien im Sammler neu rendern
        Array.from(accumulatedRefFiles.files).forEach((file) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'is-new';
                img.style.maxWidth = '80px';
                img.style.maxHeight = '80px';
                img.style.objectFit = 'cover';
                img.style.borderRadius = '4px';
                img.style.border = '2px solid var(--status-green-text)';
                containerRefs.appendChild(img);
            };
            reader.readAsDataURL(file);
        });
    }

    if (zoneRefs && inputRefs && containerRefs) {
        zoneRefs.addEventListener('click', () => inputRefs.click());

        zoneRefs.addEventListener('dragover', (e) => {
            e.preventDefault();
            zoneRefs.style.borderColor = 'var(--link-color)';
            zoneRefs.style.backgroundColor = 'var(--table-row-hover)';
        });

        zoneRefs.addEventListener('dragleave', (e) => {
            e.preventDefault();
            zoneRefs.style.borderColor = 'var(--border-medium)';
            zoneRefs.style.backgroundColor = 'var(--content-bg)';
        });

        zoneRefs.addEventListener('drop', (e) => {
            e.preventDefault();
            zoneRefs.style.borderColor = 'var(--status-green-text)';
            zoneRefs.style.backgroundColor = 'var(--status-green-bg)';
            if (e.dataTransfer.files.length) {
                // Bei Drag & Drop: LINTER-FIX (geschweifte Klammern)
                Array.from(e.dataTransfer.files).forEach((file) => {
                    accumulatedRefFiles.items.add(file);
                });
                // Echten Input updaten und Preview generieren (ohne change Event zu feuern)
                inputRefs.files = accumulatedRefFiles.files;
                updateRefPreviews();
            }
        });

        inputRefs.addEventListener('change', () => {
            if (inputRefs.files.length > 0) {
                // Wenn Nutzer auf Feld klickt und Dateien im Dialog auswählt
                // LINTER-FIX (geschweifte Klammern)
                Array.from(inputRefs.files).forEach((newFile) => {
                    let exists = false;
                    for (let i = 0; i < accumulatedRefFiles.files.length; i++) {
                        if (
                            accumulatedRefFiles.files[i].name === newFile.name &&
                            accumulatedRefFiles.files[i].size === newFile.size
                        ) {
                            exists = true;
                            break;
                        }
                    }
                    if (!exists) {
                        accumulatedRefFiles.items.add(newFile);
                    }
                });

                inputRefs.files = accumulatedRefFiles.files;
                updateRefPreviews();
            }
        });

        // WICHTIG: Wenn das Modal neu geöffnet wird, leeren wir unseren Sammler!
        const originalOpenCharModalRefs = window.openCharModal;
        window.openCharModal = (data = null) => {
            accumulatedRefFiles = new DataTransfer(); // Leeren!
            if (originalOpenCharModalRefs) {
                originalOpenCharModalRefs(data);
            }
        };
    }

    // --- BILDER GALERIE MODAL ---
    document.getElementById('btn-open-gallery')?.addEventListener('click', () => {
        const modal = document.getElementById('gallery-modal');
        if (modal) modal.style.display = 'flex';
    });

    document.querySelectorAll('.btn-close-gallery-modal').forEach((btn) => {
        btn.addEventListener('click', () => {
            const modal = document.getElementById('gallery-modal');
            if (modal) modal.style.display = 'none';
        });
    });

    document.querySelectorAll('.gallery-item').forEach((item) => {
        item.addEventListener('click', function () {
            isDirty = true;
            if (picUrlInput) picUrlInput.value = this.dataset.filename;
            if (charPreviewImg) charPreviewImg.src = this.dataset.url;

            const modal = document.getElementById('gallery-modal');
            if (modal) modal.style.display = 'none';
            if (fileInput) fileInput.value = '';
            if (previewName) previewName.textContent = '';
        });
    });

    // --- LIVE PREVIEWS DURCH TEXT-INPUTS ---
    const mainPicInput = document.getElementById('main_pic_url');
    const swatchPicInput = document.getElementById('swatch_pic_url');
    const refSheetsInput = document.getElementById('ref_sheets_urls');

    mainPicInput?.addEventListener('input', (e) => {
        const prevMain = document.getElementById('preview-img-main');
        const val = e.target.value.trim();
        if (prevMain) {
            if (val) {
                prevMain.src = `${baseUrl}/assets/images/characters/main/${val}`;
                prevMain.style.display = 'block';
            } else {
                prevMain.style.display = 'none';
                prevMain.src = '';
            }
        }
    });

    swatchPicInput?.addEventListener('input', (e) => {
        const prevSwatch = document.getElementById('preview-img-swatch');
        const val = e.target.value.trim();
        if (prevSwatch) {
            if (val) {
                prevSwatch.src = `${baseUrl}/assets/images/characters/swatches/${val}`;
                prevSwatch.style.display = 'block';
            } else {
                prevSwatch.style.display = 'none';
                prevSwatch.src = '';
            }
        }
    });

    refSheetsInput?.addEventListener('input', (e) => {
        const containerRefs = document.getElementById('preview-container-refs');
        if (containerRefs) {
            containerRefs.innerHTML = '';
            const vals = e.target.value
                .split(',')
                .map((s) => s.trim())
                .filter(Boolean);
            vals.forEach((sheet) => {
                const img = document.createElement('img');
                img.src = `${baseUrl}/assets/images/characters/refsheets/${sheet}`;
                img.style.maxWidth = '80px';
                img.style.maxHeight = '80px';
                img.style.objectFit = 'cover';
                img.style.borderRadius = '4px';
                img.style.border = '1px solid var(--border-medium)';
                containerRefs.appendChild(img);
            });
        }
    });

    // --- DYNAMISCHES BILDER GALERIE MODAL ---
    let currentGalleryTargetInput = null;

    document.querySelectorAll('.btn-open-gallery-dynamic').forEach((btn) => {
        btn.addEventListener('click', async (e) => {
            const button = e.target.closest('.btn-open-gallery-dynamic');
            const targetId = button.dataset.target;
            const folder = button.dataset.folder;

            currentGalleryTargetInput = document.getElementById(targetId);

            const modalTitle = document.getElementById('gallery-modal-title');
            if (modalTitle) modalTitle.textContent = `Galerie: ${folder}`;

            // Modal öffnen & Lade-Status anzeigen
            const modal = document.getElementById('gallery-modal');
            const galGrid = document.getElementById('gallery-grid-dynamic');
            if (modal) modal.style.display = 'flex';
            if (galGrid) galGrid.innerHTML = '<p>Lade Bilder...</p>';

            try {
                const res = await fetch(`${baseUrl}/api/list_media?folder=${folder}`);
                const json = await res.json();

                if (json.files.length === 0) {
                    galGrid.innerHTML =
                        '<p style="font-style:italic; color:var(--text-color-light);">Ordner ist leer.</p>';
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

                // Klick auf ein Galerie-Bild
                galGrid.querySelectorAll('.gallery-item').forEach((item) => {
                    item.addEventListener('click', function () {
                        isDirty = true;
                        if (currentGalleryTargetInput) {
                            if (currentGalleryTargetInput.id === 'ref_sheets_urls') {
                                // Bei Ref Sheets kommagetrennt anhängen
                                const vals = currentGalleryTargetInput.value
                                    .split(',')
                                    .map((s) => s.trim())
                                    .filter(Boolean);
                                if (!vals.includes(this.dataset.filename)) {
                                    vals.push(this.dataset.filename);
                                }
                                currentGalleryTargetInput.value = vals.join(', ');
                            } else {
                                // Bei den anderen einfach überschreiben
                                currentGalleryTargetInput.value = this.dataset.filename;
                            }
                            // WICHTIG: Das Input-Event auslösen, damit die Live-Preview lädt!
                            currentGalleryTargetInput.dispatchEvent(new Event('input'));
                        }
                        if (modal) modal.style.display = 'none';
                    });
                });
            } catch {
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

    // --- GRUPPEN DRAG & DROP LOGIK ---
    function initSortable() {
        if (typeof Sortable === 'undefined') return;

        // 1. Der Pool: Erzeugt Klone beim Ziehen!
        const poolEl = document.getElementById('char-pool');
        if (poolEl && !poolEl.dataset.sortableInitialized) {
            new Sortable(poolEl, {
                group: { name: 'shared', pull: 'clone', put: false },
                sort: false,
                animation: 150,
                onEnd: () => {
                    isDirty = true;
                },
            });
            poolEl.dataset.sortableInitialized = 'true';
        }

        // 2. Die Gruppen: Nehmen Charaktere auf
        document.querySelectorAll('.sortable-group').forEach((groupEl) => {
            if (!groupEl.dataset.sortableInitialized) {
                const manual = groupEl.dataset.manual === 'true';
                new Sortable(groupEl, {
                    group: 'shared',
                    animation: 150,
                    sort: manual, // Sortieren innerhalb der Gruppe nur, wenn manualSort aktiv ist
                    onEnd: () => {
                        isDirty = true;
                    },
                });
                groupEl.dataset.sortableInitialized = 'true';
            }
        });

        // 3. Die Gruppen-Container selbst (Reihenfolge der Gruppen)
        const wrapper = document.getElementById('groups-wrapper');
        if (wrapper && !wrapper.dataset.sortableInitialized) {
            new Sortable(wrapper, {
                animation: 150,
                handle: '.group-drag-handle',
                onEnd: () => {
                    isDirty = true;
                },
            });
            wrapper.dataset.sortableInitialized = 'true';
        }
    }

    initSortable();

    document.getElementById('btn-add-group')?.addEventListener('click', () => {
        isDirty = true;
        const wrapper = document.getElementById('groups-wrapper');
        if (!wrapper) return;

        const html = `
        <div class="character-group">
            <div class="character-group-header" style="display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-grip-vertical group-drag-handle" title="Gruppe verschieben"></i>
                <div style="flex: 1;">
                    <h3 contenteditable="true" spellcheck="false" class="group-title-edit" style="outline: none; border-bottom: 1px dashed var(--border-medium); margin: 0;">Neue Gruppe</h3>
                    <label style="font-size: 0.8em; color: var(--text-color-light); font-weight: normal; margin-top: 5px; display: block;">
                        <input type="checkbox" class="manual-sort-cb"> Manuell sortieren
                    </label>
                </div>
                <button type="button" class="button delete btn-delete-group" title="Gruppe löschen">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="character-list-container sortable-group" data-manual="false" style="min-height: 50px; padding: 10px;">
            </div>
        </div>`;
        wrapper.insertAdjacentHTML('beforeend', html);

        // Fokus direkt in den Titel setzen
        const titleEdit = wrapper.lastElementChild.querySelector('.group-title-edit');
        if (titleEdit) {
            titleEdit.focus();
            document.execCommand('selectAll', false, null);
        }

        // Sortable für die neue Box neu initialisieren
        initSortable(); // Neu einbinden
    });

    document.getElementById('btn-save-groups')?.addEventListener('click', (e) => {
        const btn = e.target.closest('#btn-save-groups');
        if (!btn) return;
        const origText = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Speichere...';
        btn.style.pointerEvents = 'none';

        const groupElements = document.querySelectorAll('#groups-wrapper .character-group');
        const groupsData = [];
        groupElements.forEach((groupEl) => {
            const titleEl = groupEl.querySelector('.group-title-edit');
            const title = titleEl?.textContent.trim() ?? '';
            if (!title) return;

            const checkbox = groupEl.querySelector('.manual-sort-cb');
            const manualSort = checkbox?.checked ?? false;

            const charElements = groupEl.querySelectorAll('.character-entry');
            const charIds = Array.from(charElements).map((el) => el.dataset.id);

            groupsData.push({ name: title, manual_sort: manualSort, characters: charIds });
        });

        const fd = new FormData();
        fd.append('groups_data', JSON.stringify(groupsData));
        sendApiRequest('save_character_groups', fd, btn, origText);
    });

    // Checkbox für manuelles Sortieren -> LIVE UPDATE IN SORTABLE!
    document.addEventListener('change', (e) => {
        if (e.target.classList.contains('manual-sort-cb')) {
            isDirty = true;
            const container = e.target
                .closest('.character-group')
                ?.querySelector('.sortable-group');
            if (container) {
                const isManual = e.target.checked;
                container.dataset.manual = isManual ? 'true' : 'false';
                if (typeof Sortable !== 'undefined') {
                    Sortable.get(container)?.option('sort', isManual);
                }

                // Hinweis für den User
                showMsg('Sortier-Modus geändert. Nicht vergessen zu speichern.', 'orange');
            }
        }
    });

    let poolViewAll = true;
    document.getElementById('btn-toggle-pool')?.addEventListener('click', (e) => {
        poolViewAll = !poolViewAll;
        e.target.textContent = poolViewAll ? 'Nur Unzugeordnete zeigen' : 'Alle anzeigen';
        document.querySelectorAll('#char-pool .character-entry.is-assigned').forEach((el) => {
            el.style.display = poolViewAll ? 'flex' : 'none';
        });
    });

    // --- REPORT PAGINIERUNG, FILTER & LOGIK ---
    const reportSearchInput = document.getElementById('report-search');
    const reportPerPageSelect = document.getElementById('report-per-page');
    const reportStatusSelect = document.getElementById('report-status-filter');
    const reportTableBody = document.querySelector('#reports-table tbody');
    const reportPaginationContainer = document.getElementById('report-pagination');

    let currentReportPayload = null;

    if (
        reportTableBody &&
        reportSearchInput &&
        reportPerPageSelect &&
        reportStatusSelect &&
        reportPaginationContainer
    ) {
        const allReportRows = Array.from(reportTableBody.querySelectorAll('tr')).filter(
            (row) => !row.classList.contains('empty-table-message')
        );

        let repPage = 1;
        let repLimit = '15';
        let repSearch = '';
        let repStatus = 'open';

        function renderReportTable() {
            const filteredRows = allReportRows.filter((row) => {
                const matchesSearch = row.textContent
                    .toLowerCase()
                    .includes(repSearch.toLowerCase());
                const matchesStatus = repStatus === 'all' || row.dataset.status === repStatus;
                return matchesSearch && matchesStatus;
            });

            const totalItems = filteredRows.length;
            const limit = repLimit === 'all' ? totalItems : parseInt(repLimit, 10);
            const totalPages = limit > 0 ? Math.ceil(totalItems / limit) : 1;

            if (repPage > totalPages) repPage = totalPages || 1;

            const startIndex = limit === totalItems ? 0 : (repPage - 1) * limit;
            const endIndex = startIndex + limit;

            allReportRows.forEach((row) => {
                row.style.display = 'none';
            });
            filteredRows.slice(startIndex, endIndex).forEach((row) => {
                row.style.display = '';
            });

            let emptyMsg = reportTableBody.querySelector('.dyn-empty-msg');
            if (filteredRows.length === 0) {
                if (!emptyMsg) {
                    emptyMsg = document.createElement('tr');
                    emptyMsg.className = 'dyn-empty-msg empty-table-message';
                    emptyMsg.innerHTML =
                        '<td colspan="6">Keine Reports für diese Filter gefunden.</td>';
                    reportTableBody.appendChild(emptyMsg);
                }
                emptyMsg.style.display = '';
            } else if (emptyMsg) {
                emptyMsg.style.display = 'none';
            }

            // Pagination Buttons generieren
            reportPaginationContainer.innerHTML = '';
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

            reportPaginationContainer.appendChild(
                createBtn('&laquo;', repPage === 1, false, () => {
                    repPage--;
                    renderReportTable();
                })
            );
            for (let i = 1; i <= totalPages; i++) {
                reportPaginationContainer.appendChild(
                    createBtn(i.toString(), false, i === repPage, () => {
                        repPage = i;
                        renderReportTable();
                    })
                );
            }
            reportPaginationContainer.appendChild(
                createBtn('&raquo;', repPage === totalPages, false, () => {
                    repPage++;
                    renderReportTable();
                })
            );
        }

        reportSearchInput.addEventListener('input', (e) => {
            repSearch = e.target.value;
            repPage = 1;
            renderReportTable();
        });
        reportPerPageSelect.addEventListener('change', (e) => {
            repLimit = e.target.value;
            repPage = 1;
            renderReportTable();
        });
        reportStatusSelect.addEventListener('change', (e) => {
            repStatus = e.target.value;
            repPage = 1;
            renderReportTable();
        });

        renderReportTable(); // Init
    }

    // Hilfsfunktion: HTML in reinen Text wandeln (Für Diffing)
    function convertHtmlToText(html) {
        if (!html) return '';
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        tempDiv.querySelectorAll('p, br').forEach((el) => {
            el.after(document.createTextNode('\n'));
        });
        return (tempDiv.textContent || tempDiv.innerText || '').trim();
    }

    function openReportModal(data) {
        currentReportPayload = data;
        const comicIdContainer = document.getElementById('rep-modal-comic-id');
        if (data.comicId) {
            comicIdContainer.innerHTML = `<a href="${baseUrl}/comic/${data.comicId}" target="_blank">${data.comicId}</a>`;
        } else {
            comicIdContainer.innerHTML =
                '<em style="color: var(--text-color-faded);">Allgemeine Website</em>';
        }
        document.getElementById('rep-modal-submitter').textContent = data.submitter;
        document.getElementById('rep-modal-date').textContent = data.date;
        document.getElementById('rep-modal-desc').textContent =
            data.description || 'Keine Beschreibung angegeben.';
        const debugRaw = document.getElementById('rep-modal-debug');
        const debugRendered = document.getElementById('rep-modal-debug-rendered');
        const btnToggleDebug = document.getElementById('btn-toggle-debug-view');

        if (debugRaw) debugRaw.value = data.debug || 'Keine Telemetrie vorhanden.';

        // Versuch, das JSON zu rendern
        if (debugRendered && data.debug) {
            try {
                const parsed = JSON.parse(data.debug);
                debugRendered.innerHTML = renderJsonToHtml(parsed);
                debugRendered.style.display = 'block';
                if (debugRaw) debugRaw.style.display = 'none';
                if (btnToggleDebug) {
                    btnToggleDebug.style.display = 'inline-block';
                    btnToggleDebug.innerHTML = '<i class="fa-solid fa-code"></i> Rohdaten anzeigen';
                }
            } catch (e) {
                // Falls es kein valides JSON ist (z.B. alter Report), zeige nur Rohtext
                debugRendered.style.display = 'none';
                if (debugRaw) debugRaw.style.display = 'block';
                if (btnToggleDebug) btnToggleDebug.style.display = 'none';
            }
        } else {
            if (debugRendered) debugRendered.style.display = 'none';
            if (btnToggleDebug) btnToggleDebug.style.display = 'none';
            if (debugRaw) debugRaw.style.display = 'block';
        }

        const transcriptSec = document.getElementById('rep-modal-transcript-section');
        const diffBox = document.getElementById('rep-modal-diff');
        const screenshotSec = document.getElementById('rep-modal-screenshot-section');
        const screenshotImg = document.getElementById('rep-modal-screenshot-img');
        const screenshotLink = document.getElementById('rep-modal-screenshot-link');

        // --- Screenshot Logik ---
        if (data.screenshotUrl) {
            const fullUrl = `${baseUrl}/assets/images/reports/${data.screenshotUrl}`;
            screenshotImg.src = fullUrl;
            screenshotLink.href = fullUrl;
            screenshotSec.style.display = 'block';
        } else {
            screenshotSec.style.display = 'none';
            screenshotImg.src = '';
            screenshotLink.href = '#';
        }

        if (data.type === 'transcript') {
            transcriptSec.style.display = 'block';
            if (typeof Diff !== 'undefined' && Diff.diffLines) {
                const oldTxt = convertHtmlToText(data.original);
                const newTxt = convertHtmlToText(data.suggestion);
                const diff = Diff.diffLines(oldTxt, newTxt, { newlineIsToken: true });

                const fragment = document.createDocumentFragment();
                diff.forEach((part) => {
                    const node = document.createElement(
                        part.added ? 'ins' : part.removed ? 'del' : 'span'
                    );
                    node.appendChild(document.createTextNode(part.value));
                    fragment.appendChild(node);
                });
                diffBox.innerHTML = '';
                diffBox.appendChild(fragment);
            } else {
                diffBox.innerHTML = `Diff-Bibliothek nicht geladen. Vorschlag:\n\n${data.suggestion}`;
            }
        } else {
            transcriptSec.style.display = 'none';
        }

        // Buttons ausblenden, falls schon erledigt/spam
        const btnResolve = document.getElementById('btn-rep-resolve');
        const btnSpam = document.getElementById('btn-rep-spam');
        if (btnResolve) btnResolve.style.display = data.status === 'open' ? 'inline-block' : 'none';
        if (btnSpam) btnSpam.style.display = data.status === 'open' ? 'inline-block' : 'none';

        const repModal = document.getElementById('report-detail-modal');
        if (repModal) repModal.style.display = 'flex';
    }

    // --- HILFSFUNKTION: JSON zu formatiertem HTML-Baum ---
    function renderJsonToHtml(obj) {
        if (typeof obj !== 'object' || obj === null) {
            const val = obj === null ? 'null' : obj;
            if (typeof val === 'string' && val.startsWith('http')) {
                return `<a href="${val}" target="_blank" style="text-decoration: underline; color: var(--link-color);">${val}</a>`;
            }
            return `<span style="color: var(--text-color);">${val}</span>`;
        }

        let html =
            '<ul style="list-style: none; padding-left: 20px; margin: 5px 0; border-left: 2px solid var(--border-medium);">';
        for (const [key, value] of Object.entries(obj)) {
            html += `<li style="margin-bottom: 6px;">`;
            html += `<strong style="color: var(--text-color-faded);">${key}:</strong> `;
            html += renderJsonToHtml(value);
            html += `</li>`;
        }
        html += '</ul>';
        return html;
    }

    // --- KAPITEL / ARCHIV LOGIK ---
    window.openChapterModal = (data = null) => {
        const form = document.getElementById('chapter-form');
        form?.reset();

        const idInput = document.getElementById('chap_id');
        const titleInput = document.getElementById('chap_title');

        if (data) {
            document.getElementById('modal-title-chapter').textContent =
                `Kapitel bearbeiten: ${data.id}`;
            if (idInput) idInput.value = data.id;
            if (titleInput) titleInput.value = data.title;
            if (typeof $.fn.trumbowyg !== 'undefined') {
                $('#chap_description').trumbowyg('html', data.description);
            }
        } else {
            document.getElementById('modal-title-chapter').textContent = 'Neues Kapitel anlegen';
            if (typeof $.fn.trumbowyg !== 'undefined') {
                $('#chap_description').trumbowyg('empty');
            }
        }

        const modal = document.getElementById('chapter-modal');
        if (modal) modal.style.display = 'flex';
    };

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
                const skipFile = false;

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
                                alert('Zu viele Unterseiten! Datei wurde übersprungen.');
                                continue;
                            }
                        } else {
                            showMsg(`Datei "${file.name}" wurde übersprungen.`, 'orange');
                            continue;
                        }
                    }
                }

                // 3. Prüfen, ob das Bild auf dem SERVER bereits existiert
                if (!skipFile) {
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
                        if (decision === 'skip') {
                            continue;
                        } else if (decision === 'variant') {
                            targetId = await findFreeVariantId(baseId, isHires);
                            if (!targetId) {
                                alert('Zu viele Unterseiten! Datei wurde übersprungen.');
                                continue;
                            }
                        }
                        // bei 'overwrite' bleibt targetId wie sie ist
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

                const btnSkip = document.getElementById('btn-overwrite-skip');
                const btnVariant = document.getElementById('btn-overwrite-variant');
                const btnOverwrite = document.getElementById('btn-overwrite-confirm');

                const cleanup = () => {
                    btnSkip.removeEventListener('click', onSkip);
                    btnVariant.removeEventListener('click', onVariant);
                    btnOverwrite.removeEventListener('click', onOverwrite);
                    modal.style.display = 'none';
                    URL.revokeObjectURL(localUrl); // Speicher wieder freigeben
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

                btnSkip.addEventListener('click', onSkip);
                btnVariant.addEventListener('click', onVariant);
                btnOverwrite.addEventListener('click', onOverwrite);

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
                    if ((isHires && testEntry.hires) || (!isHires && testEntry.lowres)) {
                        continue; // Ist lokal schon belegt -> nächster Buchstabe
                    }
                }

                // 2. Server prüfen
                const serverUrl = `${baseUrl}/assets/images/comic/${folder}/${testId}.webp`;
                const serverExists = await new Promise((resolve) => {
                    const img = new Image();
                    img.onload = () => resolve(true);
                    img.onerror = () => resolve(false);
                    img.src = serverUrl;
                });

                if (!serverExists) {
                    return testId; // Super, der Platz ist komplett frei!
                }
            }
            return null; // Alle 26 Buchstaben belegt
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
                                `Comicseite für die ID "${id}" existiert noch nicht.\n\nTrotzdem hochladen?\n(Die Bilder werden auf dem Server generiert, aber erst sichtbar, wenn die Seite später angelegt wird)`
                            )
                        ) {
                            return await processUpload(true); // Rekursiv nochmal mit force=1 ausführen
                        } else {
                            return { success: false, error: 'Übersprungen' };
                        }
                    }
                    return json;
                };

                try {
                    const json = await processUpload(false);
                    if (json.success) {
                        data.status = 'Fertig';
                    } else {
                        data.status = `Fehler: ${json.error}`;
                    }
                } catch {
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

                    // data-search="${f.filename.toLowerCase()}" hinzugefügt
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

                    // data-search="${f.id.toLowerCase()}" hinzugefügt
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
            } catch {
                // silent
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
                    `ACHTUNG: Dies löscht physisch ALLE Varianten (Hires, Lowres, Thumb, Social) der Comicseite ${id}.\n\nTippe "${id}" zum Bestätigen:`
                );
                if (check === id) {
                    const fd = new FormData();
                    fd.append('comic_id', id);
                    fd.append('csrf_token', csrfToken);
                    await fetch(`${baseUrl}/api/delete_comic_media`, { method: 'POST', body: fd });
                } else if (check !== null) {
                    alert('Abgebrochen.');
                    return;
                } else {
                    return;
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
                    mediaUploadInput.dispatchEvent(new Event('change')); // Triggert den Upload
                }
            });

            mediaUploadInput.addEventListener('change', async () => {
                if (mediaUploadInput.files.length === 0) return;
                showMsg(
                    '<i class="fa-solid fa-spinner fa-spin"></i> Lade Bilder hoch...',
                    'orange'
                );

                const fd = new FormData();
                for (const file of mediaUploadInput.files) {
                    fd.append('files[]', file);
                }
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
                } catch {
                    showMsg('<i class="fa-solid fa-bomb"></i> Fehler beim Upload', 'red');
                }
                mediaUploadInput.value = ''; // WICHTIG: Setzt das Input-Feld zurück
            });
        }

        window.loadMedia();
    }

    // --- ZENTRALE EVENT DELEGATION ---
    document.addEventListener('click', (e) => {
        // Comic Aktionen
        if (e.target.closest('#btn-add-comic')) window.openComicModal();
        if (e.target.closest('#btn-save-comic')) {
            e.preventDefault();
            const form = document.getElementById('comic-form');
            if (!form?.reportValidity()) return;

            const btn = e.target.closest('#btn-save-comic');
            const origText = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Speichere...';
            btn.style.pointerEvents = 'none';

            const idInput = document.getElementById('comic_id');
            if (idInput) sessionStorage.setItem('highlightEntityId', idInput.value.trim());
            sendApiRequest('save_single_comic', new FormData(form), btn, origText);
        }
        if (e.target.closest('.btn-close-comic-modal')) window.closeComicModal();

        const undoComicBtn = e.target.closest('.btn-undo-comic');
        if (undoComicBtn) {
            const id = undoComicBtn.dataset.id;
            if (confirm(`Soll der Comic ${id} auf die VORHERIGE Version zurückgesetzt werden?`)) {
                const fd = new FormData();
                fd.append('comic_id', id);
                sessionStorage.setItem('highlightEntityId', id);
                sendApiRequest('undo_comic', fd);
            }
        }

        const editComicBtn = e.target.closest('.btn-edit-comic');
        if (editComicBtn) window.openComicModal(JSON.parse(editComicBtn.dataset.payload));

        const deleteComicBtn = e.target.closest('.btn-delete-comic');
        if (deleteComicBtn) {
            const id = deleteComicBtn.dataset.id;
            // prompt statt confirm
            const check = prompt(
                `ACHTUNG: Willst du Comic ${id} unwiderruflich löschen?\n\nUm den Löschvorgang zu bestätigen, tippe bitte die ID "${id}" in das Feld ein:`
            );
            if (check === id) {
                const fd = new FormData();
                fd.append('comic_id', id);
                sendApiRequest('delete_comic', fd);
            } else if (check !== null) {
                alert('Eingabe war fehlerhaft. Der Comic wurde NICHT gelöscht.');
            }
        }

        // Avatar Selektion im Comic-Modal
        const charItem = e.target.closest('.char-selection-item:not(.gallery-item)');
        if (charItem) {
            isDirty = true;
            const charId = charItem.dataset.charId;
            const opt = hiddenSelect?.querySelector(`option[value="${charId}"]`);
            if (opt) {
                const newState = !opt.selected;
                opt.selected = newState;
                document
                    .querySelectorAll(`.char-selection-item[data-char-id="${charId}"]`)
                    .forEach((item) => {
                        item.classList.toggle('selected', newState);
                    });
            }
        }

        // Charakter Aktionen
        if (e.target.closest('#btn-add-char')) window.openCharModal();
        if (e.target.closest('#btn-save-char')) {
            e.preventDefault();
            const form = document.getElementById('char-form');
            if (!form?.reportValidity()) return;

            const btn = e.target.closest('#btn-save-char');
            const origText = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Speichere...';
            btn.style.pointerEvents = 'none';

            const idInput = document.getElementById('character_id');
            if (idInput) sessionStorage.setItem('highlightEntityId', idInput.value.trim());
            sendApiRequest('save_single_character', new FormData(form), btn, origText);
        }
        if (e.target.closest('.btn-close-char-modal')) window.closeCharModal();

        const editCharBtn = e.target.closest('.btn-edit-char');
        if (editCharBtn) window.openCharModal(JSON.parse(editCharBtn.dataset.payload));

        const deleteCharBtn = e.target.closest('.btn-delete-char');
        if (deleteCharBtn) {
            const id = deleteCharBtn.dataset.id;
            const name = deleteCharBtn.dataset.name;
            // prompt statt confirm (Mit dem Namen als Bestätigung)
            const check = prompt(
                `ACHTUNG: Möchtest du den Charakter "${name}" (${id}) wirklich löschen?\n\nUm den Löschvorgang zu bestätigen, tippe bitte den Namen "${name}" in das Feld ein:`
            );
            if (check === name) {
                const fd = new FormData();
                fd.append('character_id', id);
                sendApiRequest('delete_character', fd);
            } else if (check !== null) {
                alert('Eingabe war fehlerhaft. Der Charakter wurde NICHT gelöscht.');
            }
        }

        // Gruppe Löschen
        const deleteGroupBtn = e.target.closest('.btn-delete-group');
        if (deleteGroupBtn) {
            isDirty = true;
            const groupEl = deleteGroupBtn.closest('.character-group');
            if (groupEl) groupEl.remove();
        }

        if (e.target.classList.contains('remove-char-from-group')) {
            isDirty = true;
            const entry = e.target.closest('.character-entry');
            if (entry) entry.remove();
        }

        // --- REPORT EVENTS ---
        const viewReportBtn = e.target.closest('.btn-view-report');
        if (viewReportBtn) {
            openReportModal(JSON.parse(viewReportBtn.dataset.payload));
        }

        if (e.target.closest('.btn-close-report-modal')) {
            const repModal = document.getElementById('report-detail-modal');
            if (repModal) repModal.style.display = 'none';
        }

        const resolveRepBtn = e.target.closest('#btn-rep-resolve');
        if (resolveRepBtn && currentReportPayload) {
            const fd = new FormData();
            fd.append('report_id', currentReportPayload.id);
            fd.append('status', 'closed');
            const origText = resolveRepBtn.innerHTML;
            resolveRepBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Lade...';
            resolveRepBtn.style.pointerEvents = 'none';
            sendApiRequest('update_report_status', fd, resolveRepBtn, origText);
        }

        const spamRepBtn = e.target.closest('#btn-rep-spam');
        if (spamRepBtn && currentReportPayload) {
            const fd = new FormData();
            fd.append('report_id', currentReportPayload.id);
            fd.append('status', 'spam');
            const origText = spamRepBtn.innerHTML;
            spamRepBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Lade...';
            spamRepBtn.style.pointerEvents = 'none';
            sendApiRequest('update_report_status', fd, spamRepBtn, origText);
        }

        // Telemetrie Ansicht umschalten (Report-Modal)
        if (e.target.closest('#btn-toggle-debug-view')) {
            const debugRaw = document.getElementById('rep-modal-debug');
            const debugRendered = document.getElementById('rep-modal-debug-rendered');
            const btn = e.target.closest('#btn-toggle-debug-view');

            if (debugRaw.style.display === 'none') {
                // Zeige Rohtext
                debugRaw.style.display = 'block';
                debugRendered.style.display = 'none';
                btn.innerHTML = '<i class="fa-solid fa-list-tree"></i> Formatiert anzeigen';
            } else {
                // Zeige Renderview
                debugRaw.style.display = 'none';
                debugRendered.style.display = 'block';
                btn.innerHTML = '<i class="fa-solid fa-code"></i> Rohdaten anzeigen';
            }
        }

        // DAS KILLER-FEATURE: Transkript übernehmen
        if (e.target.closest('#btn-transfer-transcript') && currentReportPayload) {
            // Schutz-Check: Wenn es ein Charakter-Report ist (ComicID fehlt)
            if (!currentReportPayload.comicId) {
                alert(
                    'Automatisches Übernehmen ist aktuell nur für Comics verfügbar. Bitte kopiere den vorgeschlagenen Text und wechsle in die Charakter-Verwaltung.'
                );
                return;
            }

            // Finde den Comic in der Tabelle, um sein Payload zu klauen
            const comicBtn = document.querySelector(
                `.btn-edit-comic[data-id="${currentReportPayload.comicId}"]`
            );
            if (comicBtn) {
                const comicData = JSON.parse(comicBtn.dataset.payload);
                // Überschreibe das alte Transkript mit dem neuen Vorschlag!
                comicData.transcript = currentReportPayload.suggestion;

                // Report schließen, Comic Modal öffnen!
                const repModal = document.getElementById('report-detail-modal');
                if (repModal) repModal.style.display = 'none';
                window.openComicModal(comicData);
                showMsg(
                    'Transkript-Vorschlag in den Editor geladen. Bitte prüfen und speichern.',
                    'green'
                );
            } else {
                alert('Der Comic konnte in der aktuellen Liste nicht gefunden werden.');
            }
        }

        if (e.target.closest('#btn-add-chapter')) window.openChapterModal();
        if (e.target.closest('.btn-close-chapter-modal')) {
            const m = document.getElementById('chapter-modal');
            if (m) m.style.display = 'none';
        }

        if (e.target.closest('#btn-save-chapter')) {
            e.preventDefault();
            const form = document.getElementById('chapter-form');
            if (!form?.reportValidity()) return;

            const btn = e.target.closest('#btn-save-chapter');
            const origText = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Speichere...';
            btn.style.pointerEvents = 'none';

            sendApiRequest('save_chapter', new FormData(form), btn, origText);
        }

        const editChapPayload = e.target.closest('.btn-edit-chapter')?.dataset.payload;
        if (editChapPayload) window.openChapterModal(JSON.parse(editChapPayload));

        const deleteChapId = e.target.closest('.btn-delete-chapter')?.dataset.id;
        if (deleteChapId) {
            const check = prompt(
                `Willst du das Kapitel "${deleteChapId}" löschen?\nTippe "${deleteChapId}" zur Bestätigung:`
            );
            if (check === deleteChapId) {
                const fd = new FormData();
                fd.append('chapter_id', deleteChapId);
                sendApiRequest('delete_chapter', fd);
            }
        }
    });

    // --- NEWSLETTER MANUELL AUSLÖSEN ---
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.btn-trigger-newsletter');
        if (!btn) return;
        e.preventDefault();

        const type = btn.dataset.type;
        const pageNumber = btn.dataset.page;
        const comicName = 'TwoKinds'; // Falls wir später mehrere haben, könnte man das aus dem DOM lesen
        const pageUrl = btn.dataset.url;

        if (
            !confirm(
                `Bist du sicher, dass du den ${type}-Newsletter für Seite ${pageNumber} versenden möchtest?`
            )
        ) {
            return;
        }

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

            if (json.success) {
                showMsg(`<i class="fa-solid fa-check"></i> ${json.message}`, 'green');
            } else {
                showMsg(
                    `<i class="fa-solid fa-triangle-exclamation"></i> Fehler: ${json.error}`,
                    'red'
                );
            }
        } catch {
            showMsg('<i class="fa-solid fa-bomb"></i> Netzwerkfehler.', 'red');
        }
        btn.innerHTML = origText;
        btn.disabled = false;
    });
    // --- NEWSLETTER MANUELL AUSLÖSEN ENDE ---

    // --- LOGOUT ---
    document.getElementById('admin-logout-btn')?.addEventListener('click', (e) => {
        e.preventDefault();
        sendApiRequest('admin_logout', new FormData());
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
                alert(
                    'Bitte zuerst eine gültige 8-stellige Comic-ID eingeben oder Comic speichern!'
                );
                return;
            }
            const imgUrl = `${baseUrl}/assets/images/comic/hires/${comicId}.webp?t=${Date.now()}`;
            const testImg = new Image();
            testImg.onload = () => window.openCropperModal(comicId, imgUrl);
            testImg.onerror = () =>
                alert(
                    'Es existiert noch kein Hires-Bild für diesen Comic auf dem Server. Bitte lade die Bilder zuerst hoch.'
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

                let json;
                try {
                    // Versuche das JSON zu lesen, selbst wenn res.ok false (400/500) ist!
                    json = await res.json();
                } catch {
                    throw new Error(`Kritischer Server-Absturz (HTTP Code ${res.status})`);
                }

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
                    if (prevSocial) {
                        prevSocial.src = `${baseUrl}/assets/images/comic/socialmedia/${comicId}.jpg?t=${timestamp}`;
                    }

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
                // console.error('Cropper Fetch Error:', err);
                showMsg(`<i class="fa-solid fa-bomb"></i> Fehler: ${err.message}`, 'red');
            }

            btnSaveCrop.innerHTML = origText;
            btnSaveCrop.style.pointerEvents = 'auto';
        }
    });

    // ==========================================
    // BENUTZER & ROLLEN VERWALTUNG (KGA SYSTEM)
    // ==========================================
    const sectionUsers = document.getElementById('section-users');
    if (sectionUsers) {
        // --- 1. Tab-Steuerung (Benutzer vs. Rollen) ---
        const tabBtns = sectionUsers.querySelectorAll('.media-tab-btn');
        const views = sectionUsers.querySelectorAll('.media-view');

        tabBtns.forEach((btn) => {
            btn.addEventListener('click', () => {
                tabBtns.forEach((b) => b.classList.remove('active'));
                views.forEach((v) => (v.style.display = 'none'));
                btn.classList.add('active');
                document.getElementById('media-view-' + btn.dataset.type).style.display = 'block';
            });
        });

        // CSRF Token aus dem Header holen
        const getCsrf = () =>
            document.querySelector('meta[name="csrf-token"]')?.content || window.csrfToken || '';

        // --- 2. USER MODAL LOGIK ---
        const userModal = document.getElementById('user-modal');
        const userForm = document.getElementById('user-form');

        const openUserModal = (payload = null) => {
            userForm.reset();
            document.getElementById('user_id').value = payload ? payload.id : '';
            document.getElementById('user_username').value = payload ? payload.username : '';
            document.getElementById('user_email').value = payload ? payload.email : '';

            if (payload) {
                document.getElementById('user_role').value = payload.role_id;
                document.getElementById('modal-title-user').textContent = 'Benutzer bearbeiten';
                document.getElementById('user_password').required = false;
            } else {
                document.getElementById('modal-title-user').textContent = 'Neuen Benutzer anlegen';
                document.getElementById('user_password').required = true;
            }
            userModal.style.display = 'flex';
        };

        document.getElementById('btn-add-user')?.addEventListener('click', () => openUserModal());
        document
            .querySelectorAll('.btn-edit-user')
            .forEach((btn) =>
                btn.addEventListener('click', () => openUserModal(JSON.parse(btn.dataset.payload)))
            );
        document
            .querySelectorAll('.btn-close-user-modal')
            .forEach((btn) =>
                btn.addEventListener('click', () => (userModal.style.display = 'none'))
            );

        document.getElementById('btn-save-user')?.addEventListener('click', async () => {
            const fd = new FormData(userForm);
            fd.append('csrf_token', getCsrf());
            try {
                const res = await fetch('/api/save_user', { method: 'POST', body: fd });
                const json = await res.json();
                if (json.success) location.reload();
                else alert(json.error);
            } catch (e) {
                alert('Fehler beim Speichern des Benutzers.');
            }
        });

        document.querySelectorAll('.btn-delete-user').forEach((btn) => {
            btn.addEventListener('click', async () => {
                if (confirm('Möchtest du diesen Benutzer wirklich unwiderruflich löschen?')) {
                    const fd = new FormData();
                    fd.append('user_id', btn.dataset.id);
                    fd.append('csrf_token', getCsrf());
                    const res = await fetch('/api/delete_user', { method: 'POST', body: fd });
                    const json = await res.json();
                    if (json.success) location.reload();
                    else alert(json.error);
                }
            });
        });

        // --- 3. ROLE MODAL & KGA PERMISSION TREE ---
        const roleModal = document.getElementById('role-modal');
        const roleForm = document.getElementById('role-form');
        const allPermsCb = document.getElementById('role_all_perms');
        const parentCbs = roleForm.querySelectorAll('.perm-parent');
        const childCbs = roleForm.querySelectorAll('.perm-child');

        // Hilfsfunktion: Prüft, ob der Gott-Modus visuell angehakt werden muss
        const checkGodMode = () => {
            if (!allPermsCb) return;
            const allChecked = Array.from(roleForm.querySelectorAll('.perm-checkbox')).every(
                (cb) => cb.checked
            );
            allPermsCb.checked = allChecked;
        };

        // Klick auf Gott-Modus (*)
        if (allPermsCb) {
            allPermsCb.addEventListener('change', (e) => {
                parentCbs.forEach((cb) => (cb.checked = e.target.checked));
                childCbs.forEach((cb) => (cb.checked = e.target.checked));
            });
        }

        // Klick auf ein Hauptrecht (Parent)
        parentCbs.forEach((parent) => {
            parent.addEventListener('change', (e) => {
                const children = roleForm.querySelectorAll(
                    `.perm-child[data-parent="${parent.value}"]`
                );
                children.forEach((child) => (child.checked = e.target.checked));
                checkGodMode();
            });
        });

        // Klick auf ein Unterrecht (Child)
        childCbs.forEach((child) => {
            child.addEventListener('change', (e) => {
                const parentCb = roleForm.querySelector(
                    `.perm-parent[value="${child.dataset.parent}"]`
                );
                if (!e.target.checked) {
                    if (parentCb) parentCb.checked = false;
                    if (allPermsCb) allPermsCb.checked = false;
                } else {
                    const siblings = roleForm.querySelectorAll(
                        `.perm-child[data-parent="${child.dataset.parent}"]`
                    );
                    const allSiblingsChecked = Array.from(siblings).every((s) => s.checked);
                    if (allSiblingsChecked && parentCb) parentCb.checked = true;
                    checkGodMode();
                }
            });
        });

        const openRoleModal = (payload = null) => {
            roleForm.reset();
            document.getElementById('role_id').value = payload ? payload.id : '';
            document.getElementById('role_id').readOnly = !!payload; // ID bei bestehenden Rollen sperren!
            document.getElementById('role_name').value = payload ? payload.name : '';

            if (payload && payload.permissions) {
                document.getElementById('modal-title-role').textContent = 'Rolle bearbeiten';

                if (payload.permissions.includes('*')) {
                    if (allPermsCb) allPermsCb.checked = true;
                    parentCbs.forEach((cb) => (cb.checked = true));
                    childCbs.forEach((cb) => (cb.checked = true));
                } else {
                    // Rechte anhaken
                    payload.permissions.forEach((perm) => {
                        const cb = roleForm.querySelector(`.perm-checkbox[value="${perm}"]`);
                        if (cb) cb.checked = true;
                    });

                    // Visuelles Update: Wenn alle Kinder eines Parents an sind, Parent anhaken
                    childCbs.forEach((child) => {
                        if (child.checked) {
                            const siblings = roleForm.querySelectorAll(
                                `.perm-child[data-parent="${child.dataset.parent}"]`
                            );
                            const allChecked = Array.from(siblings).every((s) => s.checked);
                            const pCb = roleForm.querySelector(
                                `.perm-parent[value="${child.dataset.parent}"]`
                            );
                            if (allChecked && pCb) pCb.checked = true;
                        }
                    });
                    checkGodMode();
                }
            } else {
                document.getElementById('modal-title-role').textContent = 'Neue Rolle erstellen';
            }
            roleModal.style.display = 'flex';
        };

        document.getElementById('btn-add-role')?.addEventListener('click', () => openRoleModal());
        document
            .querySelectorAll('.btn-edit-role')
            .forEach((btn) =>
                btn.addEventListener('click', () => openRoleModal(JSON.parse(btn.dataset.payload)))
            );
        document
            .querySelectorAll('.btn-close-role-modal')
            .forEach((btn) =>
                btn.addEventListener('click', () => (roleModal.style.display = 'none'))
            );

        document.getElementById('btn-save-role')?.addEventListener('click', async () => {
            const perms = [];
            if (allPermsCb && allPermsCb.checked) {
                perms.push('*');
            } else {
                roleForm
                    .querySelectorAll('.perm-checkbox:checked')
                    .forEach((cb) => perms.push(cb.value));
            }

            const fd = new FormData();
            fd.append('role_id', document.getElementById('role_id').value);
            fd.append('name', document.getElementById('role_name').value);
            fd.append('permissions', JSON.stringify(perms));
            fd.append('csrf_token', getCsrf());

            try {
                const res = await fetch('/api/save_role', { method: 'POST', body: fd });
                const json = await res.json();
                if (json.success) location.reload();
                else alert(json.error);
            } catch (e) {
                alert('Fehler beim Speichern der Rolle.');
            }
        });

        document.querySelectorAll('.btn-delete-role').forEach((btn) => {
            btn.addEventListener('click', async () => {
                if (
                    confirm(
                        'ACHTUNG: Möchtest du diese Rechte-Gruppe wirklich löschen?\n\nNutzer, die in dieser Gruppe sind, verlieren sofort alle Zugriffsrechte!'
                    )
                ) {
                    const fd = new FormData();
                    fd.append('role_id', btn.dataset.id);
                    fd.append('csrf_token', getCsrf());
                    const res = await fetch('/api/delete_role', { method: 'POST', body: fd });
                    const json = await res.json();
                    if (json.success) location.reload();
                    else alert(json.error);
                }
            });
        });
    }
});
