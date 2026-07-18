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
    document.querySelectorAll('.tab-link').forEach((link) => {
        link.addEventListener('click', function (e) {
            if (this.dataset.target) {
                document
                    .querySelectorAll('.content-section')
                    .forEach((sec) => sec.classList.remove('active'));
                document.querySelectorAll('.tab-link').forEach((l) => l.classList.remove('active'));
                document.getElementById(this.dataset.target).classList.add('active');
                this.classList.add('active');
            }
        });
    });

    // --- COMIC MODAL & VISUAL CHAR SELECTION ---
    function openComicModal(data = null) {
        const form = document.getElementById('comic-form');
        form.reset();

        // Alle Charakter-Avatare zurücksetzen
        document.querySelectorAll('.char-selection-item').forEach((item) => {
            item.classList.remove('selected');
            item.querySelector('input').disabled = true;
        });

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
                    const item = document.querySelector(
                        `.char-selection-item[data-char-id="${charId}"]`
                    );
                    if (item) {
                        item.classList.add('selected');
                        item.querySelector('input').disabled = false;
                    }
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

    // Avatar Klick-Logik im Comic Modal
    document.addEventListener('click', (e) => {
        const charItem = e.target.closest('.char-selection-item');
        if (charItem) {
            charItem.classList.toggle('selected');
            const hiddenInput = charItem.querySelector('input');
            hiddenInput.disabled = !charItem.classList.contains('selected');
        }
    });

    // --- CHARAKTER MODAL ---
    function openCharModal(data = null) {
        const form = document.getElementById('char-form');
        form.reset();
        if (data) {
            document.getElementById('modal-title-char').textContent = 'Charakter bearbeiten';
            document.getElementById('character_id').value = data.id;
            document.getElementById('char_name').value = data.name;
            document.getElementById('pic_url').value = data.picUrl;
            document.getElementById('char_description').value = data.description;
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

        // Initialisiere alle Listen (Pool + Gruppen)
        const lists = document.querySelectorAll('.sortable-list');
        lists.forEach((list) => {
            new Sortable(list, {
                group: 'shared', // Alle Listen teilen sich die selbe Gruppe -> D&D untereinander möglich
                animation: 150,
                ghostClass: 'sortable-ghost',
            });
        });
    }

    // Starte Sortable sofort
    initSortable();

    document.getElementById('btn-add-group')?.addEventListener('click', () => {
        const wrapper = document.getElementById('groups-wrapper');
        const defaultName = 'Neue Gruppe';

        const html = `
            <div class="character-group">
                <div class="character-group-header">
                    <h3 contenteditable="true" spellcheck="false" class="group-title-edit" style="outline: none; border-bottom: 1px dashed var(--border-medium);">${defaultName}</h3>
                    <div class="group-actions">
                        <button type="button" class="button delete btn-delete-group" title="Gruppe löschen"><i class="fa-solid fa-times"></i></button>
                    </div>
                </div>
                <div class="character-list-container sortable-list" style="min-height: 50px; padding: 10px;"></div>
            </div>
        `;
        wrapper.insertAdjacentHTML('beforeend', html);

        // Fokus direkt in den Titel setzen
        const newTitle = wrapper.lastElementChild.querySelector('h3');
        newTitle.focus();
        document.execCommand('selectAll', false, null);

        // Sortable für die neue Box neu initialisieren
        initSortable();
    });

    document.getElementById('btn-save-groups')?.addEventListener('click', () => {
        const groupElements = document.querySelectorAll('#groups-wrapper .character-group');
        const groupsData = [];

        groupElements.forEach((groupEl) => {
            const title = groupEl.querySelector('.group-title-edit').textContent.trim();
            if (!title) return; // Leere Gruppen ignorieren

            const charElements = groupEl.querySelectorAll('.character-entry');
            const charIds = Array.from(charElements).map((el) => el.dataset.id);

            groupsData.push({
                name: title,
                characters: charIds,
            });
        });

        const fd = new FormData();
        fd.append('groups_data', JSON.stringify(groupsData));
        sendApiRequest('save_character_groups', fd);
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
            const groupEl = deleteGroupBtn.closest('.character-group');
            const chars = groupEl.querySelectorAll('.character-entry');
            if (chars.length > 0) {
                // Verschiebe alle Charaktere zurück in den Pool bevor die Gruppe gelöscht wird
                const pool = document.getElementById('char-pool');
                chars.forEach((char) => pool.appendChild(char));
            }
            groupEl.remove();
        }
    });

    // --- LOGOUT ---
    document.getElementById('admin-logout-btn')?.addEventListener('click', (e) => {
        e.preventDefault();
        sendApiRequest('admin_logout', new FormData());
    });
});
