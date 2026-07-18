document.addEventListener('DOMContentLoaded', () => {
    const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfTokenMeta ? csrfTokenMeta.content : '';
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
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') isDirty = true;
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
            .on('tbwchange', () => (isDirty = true));
    }

    function showMsg(text, type) {
        if (!statusBox) return;
        statusBox.className = 'status-message visible status-' + type;
        statusBox.innerHTML = text;
        setTimeout(() => {
            statusBox.className = 'status-message';
        }, 5000);
    }

    async function sendApiRequest(endpoint, formData) {
        formData.append('csrf_token', csrfToken);
        try {
            const response = await fetch(baseUrl + '/api/' + endpoint, {
                method: 'POST',
                body: formData,
            });
            const data = await response.json();
            if (data.success) {
                isDirty = false; // Zurücksetzen!
                window.location.reload();
            } else {
                showMsg('<i class="fa-solid fa-triangle-exclamation"></i> ' + data.error, 'red');
            }
        } catch (e) {
            showMsg('<i class="fa-solid fa-bomb"></i> Netzwerkfehler.', 'red');
        }
    }

    // --- TAB LOGIK ---
    const activeTab = sessionStorage.getItem('activeAdminTab') || 'section-comics';

    // Beim Laden der Seite den gespeicherten Tab aktivieren
    document.querySelectorAll('.content-section').forEach((sec) => sec.classList.remove('active'));
    document.querySelectorAll('.tab-link').forEach((l) => l.classList.remove('active'));

    const targetSection = document.getElementById(activeTab);
    const targetLink = document.querySelector(`.tab-link[data-target="${activeTab}"]`);

    if (targetSection) targetSection.classList.add('active');
    if (targetLink) targetLink.classList.add('active');

    document.querySelectorAll('.tab-link').forEach((link) => {
        link.addEventListener('click', function (e) {
            if (this.dataset.target) {
                // Klick merken!
                sessionStorage.setItem('activeAdminTab', this.dataset.target);

                document
                    .querySelectorAll('.content-section')
                    .forEach((sec) => sec.classList.remove('active'));
                document.querySelectorAll('.tab-link').forEach((l) => l.classList.remove('active'));
                document.getElementById(this.dataset.target).classList.add('active');
                this.classList.add('active');
            }
        });
    });

    // --- COMIC MODAL: LIVE PREVIEWS & AUTO-FILL ---
    const hiddenSelect = document.getElementById('hidden-comic-chars');
    const comicIdInput = document.getElementById('comic_id');
    const origUrlInput = document.getElementById('url_originalbild');
    const origSketchInput = document.getElementById('url_originalsketch');

    const prevLocal = document.getElementById('prev-comic-local');
    const prevOrig = document.getElementById('prev-comic-orig');
    const prevSketch = document.getElementById('prev-comic-sketch');

    function updateComicPreviews() {
        const idVal = comicIdInput.value.trim();
        const origVal = origUrlInput.value.trim();
        const sketchVal = origSketchInput.value.trim();

        // Lokal Thumbnail
        if (idVal.length === 8) {
            prevLocal.src = baseUrl + '/assets/images/comic/thumbnails/' + idVal + '.webp';
        } else {
            prevLocal.src = 'https://placehold.co/100x140?text=Fehlt';
        }

        // Keenspot Original
        if (origVal !== '') {
            if (origVal.startsWith('http')) {
                prevOrig.src = origVal;
            } else {
                // Wenn Endung fehlt, raten wir .jpg für die Vorschau (Backend macht cURL check)
                const file = origVal.includes('.') ? origVal : origVal + '.jpg';
                prevOrig.src = 'https://cdn.twokinds.keenspot.com/comics/' + file;
            }
        } else {
            prevOrig.src = 'https://placehold.co/100x140?text=Fehlt';
        }

        // Keenspot Sketch
        if (sketchVal !== '') {
            if (sketchVal.startsWith('http')) {
                prevSketch.src = sketchVal;
            } else {
                const file = sketchVal.includes('.') ? sketchVal : sketchVal + '.png';
                prevSketch.src = 'https://twokindscomic.com/images/' + file;
            }
        } else {
            prevSketch.src = 'https://placehold.co/100x140?text=Fehlt';
        }
    }

    // Event Listener für die 3 Felder
    comicIdInput?.addEventListener('input', updateComicPreviews);
    origUrlInput?.addEventListener('input', updateComicPreviews);
    origSketchInput?.addEventListener('input', updateComicPreviews);

    // Auto-Fill nach Verlassen der Comic-ID
    comicIdInput?.addEventListener('blur', () => {
        const val = comicIdInput.value.trim();
        if (val.length === 8) {
            // YYYYMMDD
            if (origUrlInput.value === '') origUrlInput.value = val;
            if (origSketchInput.value === '') origSketchInput.value = val;
            updateComicPreviews();
        }
    });

    // Hover Zoom Overlay
    const hoverOverlay = document.getElementById('image-hover-overlay');
    const hoverOverlayImg = document.getElementById('hover-overlay-img');
    document.querySelectorAll('.hover-zoom-trigger').forEach((img) => {
        img.addEventListener('mouseenter', () => {
            if (img.src && !img.src.includes('placehold.co')) {
                hoverOverlayImg.src = img.src;
                hoverOverlay.style.display = 'flex';
            }
        });
        img.addEventListener('mouseleave', () => {
            hoverOverlay.style.display = 'none';
        });
    });

    function openComicModal(data = null) {
        const form = document.getElementById('comic-form');
        form.reset();

        // Alle Selections zurücksetzen (Charakter-Avatare)
        if (hiddenSelect) Array.from(hiddenSelect.options).forEach((opt) => (opt.selected = false));
        document
            .querySelectorAll('.char-selection-item')
            .forEach((item) => item.classList.remove('selected'));

        if (data) {
            document.getElementById('modal-title-comic').textContent =
                'Comic bearbeiten: ' + data.id;
            comicIdInput.value = data.id;
            comicIdInput.readOnly = true;
            document.getElementById('type').value = data.type;
            document.getElementById('name').value = data.name;
            document.getElementById('chapter_id').value = data.chapterId;
            origUrlInput.value = data.originalUrl;
            origSketchInput.value = data.sketchUrl;
            $('#transcript').trumbowyg('html', data.transcript);

            // Markiere die im Array enthaltenen Charaktere als ausgewählt
            if (data.characters && Array.isArray(data.characters)) {
                data.characters.forEach((charId) => {
                    const opt = hiddenSelect.querySelector(`option[value="${charId}"]`);
                    if (opt) opt.selected = true;
                    document
                        .querySelectorAll(`.char-selection-item[data-char-id="${charId}"]`)
                        .forEach((item) => {
                            item.classList.add('selected');
                        });
                });
            }
        } else {
            document.getElementById('modal-title-comic').textContent = 'Neuen Comic anlegen';
            comicIdInput.readOnly = false;
            $('#transcript').trumbowyg('empty');
        }
        updateComicPreviews(); // Vorschauen direkt triggern
        document.getElementById('comic-modal').style.display = 'flex';
    }

    function closeComicModal() {
        document.getElementById('comic-modal').style.display = 'none';
    }

    // Toggle Alphabetical / Grouped View
    let charViewAlpha = true;
    document.getElementById('btn-toggle-char-view')?.addEventListener('click', (e) => {
        charViewAlpha = !charViewAlpha;
        e.target.innerHTML = charViewAlpha
            ? '<i class="fa-solid fa-layer-group"></i> Gruppiert anzeigen'
            : '<i class="fa-solid fa-font"></i> Alphabetisch anzeigen';
        document.getElementById('view-chars-alpha').style.display = charViewAlpha ? 'flex' : 'none';
        document.getElementById('view-chars-grouped').style.display = charViewAlpha
            ? 'none'
            : 'block';
    });

    // Avatar Klick-Logik synchronisiert Hidden-Select und BEIDE Ansichten
    document.addEventListener('click', (e) => {
        const charItem = e.target.closest('.char-selection-item:not(.gallery-item)');
        if (charItem) {
            isDirty = true;
            const charId = charItem.dataset.charId;
            const opt = hiddenSelect.querySelector(`option[value="${charId}"]`);
            const newState = !opt.selected;
            opt.selected = newState;

            document
                .querySelectorAll(`.char-selection-item[data-char-id="${charId}"]`)
                .forEach((item) => {
                    item.classList.toggle('selected', newState);
                });
        }
    });

    // --- CHARAKTER MODAL ---
    const charPreviewImg = document.getElementById('char-preview-img');
    const charDisplayId = document.getElementById('char-display-id');
    const picUrlInput = document.getElementById('pic_url');

    // Live update preview on text input
    picUrlInput?.addEventListener('input', (e) => {
        const val = e.target.value.trim();
        if (val) {
            charPreviewImg.src = baseUrl + '/assets/images/characters/profiles/' + val;
        } else {
            charPreviewImg.src = 'https://placehold.co/120x120?text=Kein+Bild';
        }
    });

    function openCharModal(data = null) {
        const form = document.getElementById('char-form');
        form.reset();
        document.getElementById('upload-preview-name').textContent = '';

        if (data) {
            document.getElementById('modal-title-char').textContent = 'Charakter bearbeiten';
            document.getElementById('character_id').value = data.id;
            charDisplayId.textContent = 'ID: ' + data.id;
            document.getElementById('char_name').value = data.name;
            picUrlInput.value = data.picUrl;
            $('#char_description').trumbowyg('html', data.description);
            document.getElementById('alt_names').value = data.altNames || '';
            document.getElementById('char_rank').value = data.rank || '';

            charPreviewImg.src = data.picUrl
                ? baseUrl + '/assets/images/characters/profiles/' + data.picUrl
                : 'https://placehold.co/120x120?text=Kein+Bild';
        } else {
            document.getElementById('modal-title-char').textContent = 'Neuen Charakter anlegen';
            document.getElementById('character_id').value = 'new';
            charDisplayId.textContent = 'ID: NEW';
            $('#char_description').trumbowyg('empty');
            charPreviewImg.src = 'https://placehold.co/120x120?text=Kein+Bild';
        }
        document.getElementById('char-modal').style.display = 'flex';
    }

    function closeCharModal() {
        document.getElementById('char-modal').style.display = 'none';
    }

    // --- BILD UPLOAD DRAG & DROP ---
    const dropZone = document.getElementById('char-drop-zone');
    const fileInput = document.getElementById('profile_image');
    const previewName = document.getElementById('upload-preview-name');

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

    function handleFileUploadPreview() {
        if (fileInput.files && fileInput.files[0]) {
            isDirty = true;
            previewName.textContent = 'Bereit zum Upload: ' + fileInput.files[0].name;
            picUrlInput.value = ''; // Textfeld leeren
            // Live Browser-Vorschau des lokalen Bildes
            const reader = new FileReader();
            reader.onload = (e) => {
                charPreviewImg.src = e.target.result;
            };
            reader.readAsDataURL(fileInput.files[0]);
        }
    }

    // --- BILDER GALERIE MODAL ---
    document
        .getElementById('btn-open-gallery')
        ?.addEventListener(
            'click',
            () => (document.getElementById('gallery-modal').style.display = 'flex')
        );

    document
        .querySelectorAll('.btn-close-gallery-modal')
        .forEach((btn) =>
            btn.addEventListener(
                'click',
                () => (document.getElementById('gallery-modal').style.display = 'none')
            )
        );

    document.querySelectorAll('.gallery-item').forEach((item) => {
        item.addEventListener('click', function () {
            isDirty = true;
            picUrlInput.value = this.dataset.filename;
            charPreviewImg.src = this.dataset.url;
            document.getElementById('gallery-modal').style.display = 'none';
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
                onEnd: () => (isDirty = true),
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
                    onEnd: () => (isDirty = true),
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
                onEnd: () => (isDirty = true),
            });
            wrapper.dataset.sortableInitialized = 'true';
        }
    }

    initSortable();

    document.getElementById('btn-add-group')?.addEventListener('click', () => {
        isDirty = true;
        const wrapper = document.getElementById('groups-wrapper');
        const defaultName = 'Neue Gruppe';
        const html = `
        <div class="character-group">
            <div class="character-group-header" style="display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-grip-vertical group-drag-handle" title="Gruppe verschieben"></i>
                    <div style="flex: 1;">
                        <h3 contenteditable="true" spellcheck="false" class="group-title-edit" style="outline: none; border-bottom: 1px dashed var(--border-medium); margin: 0;">${defaultName}</h3>
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
        wrapper.lastElementChild.querySelector('.group-title-edit').focus();
        document.execCommand('selectAll', false, null);

        // Sortable für die neue Box neu initialisieren
        initSortable(); // Neu einbinden
    });

    document.getElementById('btn-save-groups')?.addEventListener('click', () => {
        const groupElements = document.querySelectorAll('#groups-wrapper .character-group');
        const groupsData = [];

        groupElements.forEach((groupEl) => {
            const title = groupEl.querySelector('.group-title-edit').textContent.trim();
            if (!title) return; // Leere Gruppen ignorieren

            const manualSort = groupEl.querySelector('.manual-sort-cb')?.checked || false;
            const charElements = groupEl.querySelectorAll('.character-entry');
            const charIds = Array.from(charElements).map((el) => el.dataset.id);
            groupsData.push({ name: title, manual_sort: manualSort, characters: charIds });
        });

        const fd = new FormData();
        fd.append('groups_data', JSON.stringify(groupsData));
        sendApiRequest('save_character_groups', fd);
    });

    // Checkbox für manuelles Sortieren -> LIVE UPDATE IN SORTABLE!
    document.addEventListener('change', (e) => {
        if (e.target.classList.contains('manual-sort-cb')) {
            isDirty = true;
            const container = e.target.closest('.character-group').querySelector('.sortable-group');
            const isManual = e.target.checked;
            container.dataset.manual = isManual ? 'true' : 'false';

            const sortableInstance = Sortable.get(container);
            if (sortableInstance) sortableInstance.option('sort', isManual);

            // Hinweis für den User
            showMsg('Sortier-Modus geändert. Nicht vergessen zu speichern.', 'orange');
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

    // --- EVENT DELEGATION FÜR BUTTONS ---
    document.addEventListener('click', (e) => {
        // Comic Aktionen
        if (e.target.closest('#btn-add-comic')) openComicModal();
        if (e.target.closest('#btn-save-comic')) {
            const form = document.getElementById('comic-form');
            if (!form.reportValidity()) return;
            sendApiRequest('save_single_comic', new FormData(form));
        }
        if (e.target.closest('.btn-close-comic-modal')) closeComicModal();

        const undoComicBtn = e.target.closest('.btn-undo-comic');
        if (
            undoComicBtn &&
            confirm(
                'Soll der Comic ' +
                    undoComicBtn.dataset.id +
                    ' auf die VORHERIGE Version zurückgesetzt werden?'
            )
        ) {
            const fd = new FormData();
            fd.append('comic_id', undoComicBtn.dataset.id);
            sendApiRequest('undo_comic', fd);
        }

        const editComicBtn = e.target.closest('.btn-edit-comic');
        if (editComicBtn) openComicModal(JSON.parse(editComicBtn.dataset.payload));

        const deleteComicBtn = e.target.closest('.btn-delete-comic');
        if (
            deleteComicBtn &&
            confirm(
                'ACHTUNG: Willst du Comic ' + deleteComicBtn.dataset.id + ' unwiderruflich löschen?'
            )
        ) {
            const fd = new FormData();
            fd.append('comic_id', deleteComicBtn.dataset.id);
            sendApiRequest('delete_comic', fd);
        }

        // Charakter Aktionen
        if (e.target.closest('#btn-add-char')) openCharModal();
        if (e.target.closest('#btn-save-char')) {
            const form = document.getElementById('char-form');
            if (!form.reportValidity()) return;
            sendApiRequest('save_single_character', new FormData(form));
        }
        if (e.target.closest('.btn-close-char-modal')) closeCharModal();

        const editCharBtn = e.target.closest('.btn-edit-char');
        if (editCharBtn) openCharModal(JSON.parse(editCharBtn.dataset.payload));

        const deleteCharBtn = e.target.closest('.btn-delete-char');
        if (
            deleteCharBtn &&
            confirm(
                'Möchtest du den Charakter "' +
                    deleteCharBtn.dataset.name +
                    '" (' +
                    deleteCharBtn.dataset.id +
                    ') wirklich löschen?'
            )
        ) {
            const fd = new FormData();
            fd.append('character_id', deleteCharBtn.dataset.id);
            sendApiRequest('delete_character', fd);
        }

        // Gruppe Löschen
        const deleteGroupBtn = e.target.closest('.btn-delete-group');
        if (deleteGroupBtn) {
            isDirty = true;
            deleteGroupBtn.closest('.character-group').remove();
        }

        if (e.target.classList.contains('remove-char-from-group')) {
            isDirty = true;
            e.target.closest('.character-entry').remove();
        }
    });

    // --- LOGOUT ---
    document.getElementById('admin-logout-btn')?.addEventListener('click', (e) => {
        e.preventDefault();
        sendApiRequest('admin_logout', new FormData());
    });
});
