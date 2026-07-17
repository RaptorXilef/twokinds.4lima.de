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

    // --- MODAL FUNKTIONEN (INTERN) ---
    function openComicModal(data = null) {
        const form = document.getElementById('comic-form');
        form.reset();
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
        } else {
            document.getElementById('modal-title-comic').textContent = 'Neuen Comic anlegen';
            document.getElementById('comic_id').readOnly = false;
        }
        document.getElementById('comic-modal').style.display = 'flex';
    }

    function closeComicModal() {
        document.getElementById('comic-modal').style.display = 'none';
    }

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

    // --- DIREKTE BUTTON BINDINGS (SPEICHERN & MODAL TRIGGERS) ---
    document.getElementById('btn-add-comic')?.addEventListener('click', () => openComicModal());
    document.getElementById('btn-save-comic')?.addEventListener('click', () => {
        const form = document.getElementById('comic-form');
        if (!form.reportValidity()) return;
        sendApiRequest('save_single_comic', new FormData(form));
    });
    document
        .querySelectorAll('.btn-close-comic-modal')
        .forEach((btn) => btn.addEventListener('click', closeComicModal));

    document.getElementById('btn-add-char')?.addEventListener('click', () => openCharModal());
    document.getElementById('btn-save-char')?.addEventListener('click', () => {
        const form = document.getElementById('char-form');
        if (!form.reportValidity()) return;
        sendApiRequest('save_single_character', new FormData(form));
    });
    document
        .querySelectorAll('.btn-close-char-modal')
        .forEach((btn) => btn.addEventListener('click', closeCharModal));

    // --- EVENT DELEGATION FÜR DYNAMISCHE TABELLEN-BUTTONS ---
    document.addEventListener('click', (e) => {
        // Comic Aktionen
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
            return;
        }

        const editComicBtn = e.target.closest('.btn-edit-comic');
        if (editComicBtn) {
            openComicModal(JSON.parse(editComicBtn.dataset.payload));
            return;
        }

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
            return;
        }

        // Charakter Aktionen
        const editCharBtn = e.target.closest('.btn-edit-char');
        if (editCharBtn) {
            openCharModal(JSON.parse(editCharBtn.dataset.payload));
            return;
        }

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
            return;
        }
    });

    // --- LOGOUT ---
    document.getElementById('admin-logout-btn')?.addEventListener('click', (e) => {
        e.preventDefault();
        sendApiRequest('admin_logout', new FormData());
    });
});
