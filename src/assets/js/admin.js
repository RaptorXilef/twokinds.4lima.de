document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const statusBox = document.getElementById('global-status-message');
    const baseUrlMatch = window.location.pathname.match(/^(.*)\/admin/);
    const baseUrl = baseUrlMatch ? baseUrlMatch[1] : '';

    function showMsg(text, type) {
        if (!statusBox) return;
        statusBox.className = 'status-message visible status-' + type;
        statusBox.innerHTML = text;
        setTimeout(() => { statusBox.className = 'status-message'; }, 5000);
    }

    async function sendApiRequest(endpoint, formData) {
        formData.append('csrf_token', csrfToken);
        try {
            const response = await fetch(baseUrl + '/api/' + endpoint, {
                method: 'POST',
                body: formData
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

    // --- COMIC LOGIC ---
    window.openComicModal = function(data = null) {
        const form = document.getElementById('comic-form');
        form.reset();

        if (data) {
            document.getElementById('modal-title-comic').textContent = 'Comic bearbeiten: ' + data.id;
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
    };

    window.closeComicModal = function() {
        document.getElementById('comic-modal').style.display = 'none';
    };

    window.saveComic = function() {
        const form = document.getElementById('comic-form');
        if(!form.reportValidity()) return;
        sendApiRequest('save_single_comic', new FormData(form));
    };

    window.deleteComic = function(id) {
        if(confirm('ACHTUNG: Willst du Comic ' + id + ' wirklich unwiderruflich löschen?')) {
            const fd = new FormData();
            fd.append('comic_id', id);
            sendApiRequest('delete_comic', fd);
        }
    };

    window.undoComic = function(id) {
        if(confirm('Soll der Comic ' + id + ' auf die VORHERIGE Version zurückgesetzt werden?')) {
            const fd = new FormData();
            fd.append('comic_id', id);
            sendApiRequest('undo_comic', fd);
        }
    };

    // --- CHARACTER LOGIC ---
    window.openCharModal = function(data = null) {
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
    };

    window.closeCharModal = function() {
        document.getElementById('char-modal').style.display = 'none';
    };

    window.saveCharacter = function() {
        const form = document.getElementById('char-form');
        if(!form.reportValidity()) return;
        sendApiRequest('save_single_character', new FormData(form));
    };

    window.deleteCharacter = function(id, name) {
        if(confirm('Möchtest du den Charakter "' + name + '" (' + id + ') wirklich löschen?')) {
            const fd = new FormData();
            fd.append('character_id', id);
            sendApiRequest('delete_character', fd);
        }
    };

    // --- LOGOUT ---
    document.getElementById('admin-logout-btn')?.addEventListener('click', (e) => {
        e.preventDefault();
        sendApiRequest('admin_logout', new FormData());
    });
});
