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
        } catch (error) {
            console.error('API Error:', error); // HIER WIRD DER FEHLER GELOGGT
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
        const origVal = origUrlInput?.value.trim() ?? '';
        const sketchVal = origSketchInput?.value.trim() ?? '';

        const remoteExts = ['png', 'jpg', 'gif', 'jpeg', 'webp'];
        const localExts = ['webp', 'png', 'jpg', 'jpeg', 'gif'];
        const fallback = 'https://placehold.co/100x140?text=Fehlt';

        // 1. Lokal Lowres
        if (prevLocal) {
            if (idVal.length >= 8) {
                loadPreviewWithProbe(
                    prevLocal,
                    `${baseUrl}/assets/images/comic/lowres/${idVal}`,
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

        if (data) {
            const titleEl = document.getElementById('modal-title-comic');
            if (titleEl) titleEl.textContent = `Comic bearbeiten: ${data.id}`;

            if (comicIdInput) {
                comicIdInput.value = data.id;
                comicIdInput.readOnly = true;
            }
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

            // Markiere die im Array enthaltenen Charaktere als ausgewählt
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
        } else {
            const titleEl = document.getElementById('modal-title-comic');
            if (titleEl) titleEl.textContent = 'Neuen Comic anlegen';
            if (comicIdInput) comicIdInput.readOnly = false;

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
        }

        // Merken fürs spätere Hervorheben bei "Abbrechen"
        if (comicIdInput) {
            sessionStorage.setItem('highlightEntityIdCancel', comicIdInput.value.trim());
        }

        updateComicPreviews(); // Vorschauen direkt triggern
        const modal = document.getElementById('comic-modal');
        if (modal) modal.style.display = 'flex';
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
        const altInput = document.getElementById('alt_names');
        const rankInput = document.getElementById('char_rank');

        if (data) {
            const titleEl = document.getElementById('modal-title-char');
            if (titleEl) titleEl.textContent = 'Charakter bearbeiten';

            if (idInput) idInput.value = data.id;
            if (charDisplayId) charDisplayId.textContent = `ID: ${data.id}`;
            if (nameInput) nameInput.value = data.name;
            if (picUrlInput) picUrlInput.value = data.picUrl;

            if (typeof $.fn.trumbowyg !== 'undefined') {
                $('#char_description').trumbowyg('html', data.description);
            }
            if (altInput) altInput.value = data.altNames ?? '';
            if (rankInput) rankInput.value = data.rank ?? '';

            if (charPreviewImg) {
                charPreviewImg.src = data.picUrl
                    ? `${baseUrl}/assets/images/characters/profiles/${data.picUrl}`
                    : 'https://placehold.co/120x120?text=Kein+Bild';
            }
        } else {
            const titleEl = document.getElementById('modal-title-char');
            if (titleEl) titleEl.textContent = 'Neuen Charakter anlegen';
            if (idInput) idInput.value = 'new';
            if (charDisplayId) charDisplayId.textContent = 'ID: NEW';

            if (typeof $.fn.trumbowyg !== 'undefined') {
                $('#char_description').trumbowyg('empty');
            }
            if (charPreviewImg) charPreviewImg.src = 'https://placehold.co/120x120?text=Kein+Bild';
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
    const dropZone = document.getElementById('char-drop-zone');
    const fileInput = document.getElementById('profile_image');
    const previewName = document.getElementById('upload-preview-name');

    function handleFileUploadPreview() {
        if (fileInput?.files?.[0]) {
            isDirty = true;
            if (previewName) {
                previewName.textContent = `Bereit zum Upload: ${fileInput.files[0].name}`;
            }
            if (picUrlInput) picUrlInput.value = '';
            const reader = new FileReader();
            reader.onload = (e) => {
                if (charPreviewImg) charPreviewImg.src = e.target.result;
            };
            reader.readAsDataURL(fileInput.files[0]);
        }
    }

    if (dropZone && fileInput) {
        dropZone.addEventListener('click', () => fileInput.click());
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = 'var(--link-color)';
        });
        dropZone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = 'var(--border-medium)';
        });
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = 'var(--border-medium)';
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                handleFileUploadPreview();
            }
        });
        fileInput.addEventListener('change', handleFileUploadPreview);
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
                    const sortableInstance = Sortable.get(container);
                    sortableInstance?.option('sort', isManual);
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
        document.getElementById('rep-modal-comic-id').innerHTML =
            `<a href="${baseUrl}/comic/${data.comicId}" target="_blank">${data.comicId}</a>`;
        document.getElementById('rep-modal-submitter').textContent = data.submitter;
        document.getElementById('rep-modal-date').textContent = data.date;
        document.getElementById('rep-modal-desc').textContent =
            data.description || 'Keine Beschreibung angegeben.';
        document.getElementById('rep-modal-debug').value = data.debug;

        const transcriptSec = document.getElementById('rep-modal-transcript-section');
        const diffBox = document.getElementById('rep-modal-diff');

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

        // DAS KILLER-FEATURE: Transkript übernehmen
        if (e.target.closest('#btn-transfer-transcript') && currentReportPayload) {
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
    });

    // --- LOGOUT ---
    document.getElementById('admin-logout-btn')?.addEventListener('click', (e) => {
        e.preventDefault();
        sendApiRequest('admin_logout', new FormData());
    });
});
