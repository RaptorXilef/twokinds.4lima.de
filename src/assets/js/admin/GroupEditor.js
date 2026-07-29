export class GroupEditor {
    constructor(api) {
        this.api = api; // Braucht keinen ModalManager, da Gruppen meist In-Page bearbeitet werden
        this.section = document.getElementById('section-groups');

        if (this.section) {
            this.bindEvents();
            this.initSortable();
        }
    }

    bindEvents() {
        // 1. Neue Gruppe speichern (Kleines Formular direkt auf der Seite)
        const btnSaveGroup = document.getElementById('btn-save-group');
        if (btnSaveGroup) {
            btnSaveGroup.addEventListener('click', () => this.saveGroup(btnSaveGroup));
        }

        // 2. Gruppe löschen (Event Delegation in der Gruppenliste)
        this.section.addEventListener('click', (e) => {
            const btnDelete = e.target.closest('.btn-delete-group');
            if (btnDelete) {
                e.preventDefault();
                this.deleteGroup(btnDelete.dataset.name);
            }
        });

        // 3. Sortierung speichern
        const btnSaveOrder = document.getElementById('btn-save-group-order');
        if (btnSaveOrder) {
            btnSaveOrder.addEventListener('click', () => this.saveGroupOrder(btnSaveOrder));
        }
    }

    initSortable() {
        // Prüfen ob die externe Bibliothek geladen ist
        if (typeof window.Sortable === 'undefined') {
            console.warn('[GroupEditor] Sortable.js ist nicht geladen.');
            return;
        }

        // Alle Listen initialisieren, sodass man Charaktere hin und her ziehen kann
        document.querySelectorAll('.character-group-list').forEach((list) => {
            new window.Sortable(list, {
                group: 'characters', // gleicher Gruppenname erlaubt Drag & Drop zwischen Listen
                animation: 150,
                ghostClass: 'sortable-ghost',
            });
        });
    }

    async saveGroup(btnElement) {
        const input = document.getElementById('new-group-name');
        if (!input || !input.reportValidity()) return;

        const originalText = btnElement.innerHTML;
        btnElement.disabled = true;
        btnElement.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

        const formData = new window.FormData();
        formData.append('group_name', input.value.trim());

        const result = await this.api.post('save_group', formData);

        if (result.success) {
            this.api.showStatus(result.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            this.api.showStatus(result.error, 'error');
            btnElement.disabled = false;
            btnElement.innerHTML = originalText;
        }
    }

    async deleteGroup(name) {
        if (
            !confirm(
                `Möchtest du die Gruppe '${name}' wirklich löschen? Die Charaktere bleiben erhalten.`
            )
        )
            return;

        const formData = new window.FormData();
        formData.append('group_name', name);

        const result = await this.api.post('delete_group', formData);
        if (result.success) {
            this.api.showStatus(result.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            this.api.showStatus(result.error, 'error');
        }
    }

    async saveGroupOrder(btnElement) {
        const originalText = btnElement.innerHTML;
        btnElement.disabled = true;
        btnElement.innerHTML =
            '<i class="fa-solid fa-spinner fa-spin"></i> Speichere Sortierung...';

        const groupData = {};

        // Wir sammeln alle Gruppen und deren Charaktere ein
        document.querySelectorAll('.character-group').forEach((groupContainer) => {
            const groupName = groupContainer.dataset.groupName;
            if (groupName) {
                const charElements = groupContainer.querySelectorAll('.sortable-item');
                const charIds = Array.from(charElements).map((item) => item.dataset.charId);
                groupData[groupName] = charIds;
            }
        });

        const formData = new window.FormData();
        formData.append('groups', JSON.stringify(groupData));

        const result = await this.api.post('save_group_order', formData);
        if (result.success) {
            this.api.showStatus(result.message, 'success');
            btnElement.disabled = false;
            btnElement.innerHTML = originalText;
        } else {
            this.api.showStatus(result.error, 'error');
            btnElement.disabled = false;
            btnElement.innerHTML = originalText;
        }
    }
}
