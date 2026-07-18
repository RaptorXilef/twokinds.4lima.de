document.addEventListener('DOMContentLoaded', () => {
    const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfTokenMeta ? csrfTokenMeta.content : '';
    const statusBox = document.getElementById('global-status-message');
    const baseUrlMatch = window.location.pathname.match(/^(.*)\/admin/);
    const baseUrl = baseUrlMatch ? baseUrlMatch[1] : '';

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

    // --- COMIC MODAL & VISUAL CHAR SELECTION (DUAL VIEW) ---
    const hiddenSelect = document.getElementById('hidden-comic-chars');

    function openComicModal(data = null) {
        const form = document.getElementById('comic-form');
        form.reset();

        // Alle Selections zurücksetzen (Charakter-Avatare)
        if (hiddenSelect) {
            Array.from(hiddenSelect.options).forEach((opt) => (opt.selected = false));
        }
        document
            .querySelectorAll('.char-selection-item')
            .forEach((item) => item.classList.remove('selected'));

        if (data) {
            document.getElementById('modal-title-comic').textContent =
                'Comic bearbeiten: ' + data.id;
            document.getElementById('comic_id').value = data.id;
            document.getElementById('comic_id').readOnly = true;
            document.getElementById('type').value = data.type;
            document.getElementById('name').value = data.name;
            document.getElementById('chapter_id').value = data.chapterId;
            document.getElementById('url_originalbild').value = data.originalUrl;
            document.getElementById('url_originalsketch').value = data.sketchUrl;
            document.getElementById('transcript').value = data.transcript;

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
            document.getElementById('comic_id').readOnly = false;
        }
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
        const charItem = e.target.closest('.char-selection-item');
        if (charItem) {
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
    function openCharModal(data = null) {
        const form = document.getElementById('char-form');
        form.reset();
        document.getElementById('upload-preview-name').textContent = '';
        if (data) {
            document.getElementById('modal-title-char').textContent = 'Charakter bearbeiten';
            document.getElementById('character_id').value = data.id;
            document.getElementById('char_name').value = data.name;
            document.getElementById('pic_url').value = data.picUrl;
            document.getElementById('char_description').value = data.description;
            document.getElementById('alt_names').value = data.altNames || '';
            document.getElementById('char_rank').value = data.rank || '';
        } else {
            document.getElementById('modal-title-char').textContent = 'Neuen Charakter anlegen';
            document.getElementById('character_id').value = 'new';
        }
        document.getElementById('char-modal').style.display = 'flex';
    }

    function closeCharModal() {
        document.getElementById('char-modal').style.display = 'none';
    }

    // --- GRUPPEN DRAG & DROP LOGIK ---
    function initSortable() {
        if (typeof Sortable === 'undefined') return;

        // 1. Der Pool: Erzeugt Klone beim Ziehen!
        const poolEl = document.getElementById('char-pool');
        if (poolEl && !poolEl.dataset.sortableInitialized) {
            new Sortable(poolEl, {
                group: { name: 'shared', pull: 'clone', put: false },
                sort: false, // Pool selbst wird nicht manuell sortiert
                animation: 150,
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
            });
            wrapper.dataset.sortableInitialized = 'true';
        }
    }

    initSortable();

    // Pool Ansicht filtern ("Alle" vs "Nur Unzugeordnete")
    let poolViewAll = true;
    document.getElementById('btn-toggle-pool')?.addEventListener('click', (e) => {
        poolViewAll = !poolViewAll;
        e.target.textContent = poolViewAll ? 'Nur Unzugeordnete zeigen' : 'Alle anzeigen';
        document.querySelectorAll('#char-pool .character-entry.is-assigned').forEach((el) => {
            el.style.display = poolViewAll ? 'flex' : 'none';
        });
    });

    document.getElementById('btn-add-group')?.addEventListener('click', () => {
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
                    <button type="button" class="button delete btn-delete-group" title="Gruppe löschen"><i class="fa-solid fa-times"></i></button>
                </div>
                <div class="character-list-container sortable-group" data-manual="false" style="min-height: 50px; padding: 10px;"></div>
            </div>
        `;
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

            groupsData.push({
                name: title,
                manual_sort: manualSort,
                characters: charIds,
            });
        });

        const fd = new FormData();
        fd.append('groups_data', JSON.stringify(groupsData));
        sendApiRequest('save_character_groups', fd);
    });

    // Char aus Gruppe löschen (X Button)
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-char-from-group')) {
            e.target.closest('.character-entry').remove();
        }
    });

    // Checkbox für manuelles Sortieren umschalten -> State updaten
    document.addEventListener('change', (e) => {
        if (e.target.classList.contains('manual-sort-cb')) {
            const container = e.target.closest('.character-group').querySelector('.sortable-group');
            container.dataset.manual = e.target.checked ? 'true' : 'false';
            // Hinweis für den User
            showMsg('Einstellung geändert. Bitte "Sortierung Speichern" klicken.', 'orange');
        }
    });

    // --- EVENT DELEGATION FÜR BUTTONS (Bleibt wie vorher) ---
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
            deleteGroupBtn.closest('.character-group').remove();
        }
    });

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
                previewName.textContent = 'Datei ausgewählt: ' + fileInput.files[0].name;
                document.getElementById('pic_url').value = '';
            }
        });
        fileInput.addEventListener('change', () => {
            if (fileInput.files.length) {
                previewName.textContent = 'Datei ausgewählt: ' + fileInput.files[0].name;
                document.getElementById('pic_url').value = '';
            }
        });
    }

    // --- BILDER GALERIE MODAL ---
    document.getElementById('btn-open-gallery')?.addEventListener('click', () => {
        document.getElementById('gallery-modal').style.display = 'flex';
    });

    document.querySelectorAll('.btn-close-gallery-modal').forEach((btn) => {
        btn.addEventListener(
            'click',
            () => (document.getElementById('gallery-modal').style.display = 'none')
        );
    });

    document.querySelectorAll('.gallery-item').forEach((item) => {
        item.addEventListener('click', function () {
            document.getElementById('pic_url').value = this.dataset.filename;
            document.getElementById('gallery-modal').style.display = 'none';
            if (fileInput) fileInput.value = '';
            if (previewName) previewName.textContent = '';
        });
    });

    // --- LOGOUT ---
    document.getElementById('admin-logout-btn')?.addEventListener('click', (e) => {
        e.preventDefault();
        sendApiRequest('admin_logout', new FormData());
    });
});
