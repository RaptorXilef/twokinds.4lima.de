export class SystemManager {
    constructor(api, modalManager) {
        this.api = api;
        this.modalManager = modalManager;
        this.section = document.getElementById('section-users');

        if (this.section) {
            this.bindEvents();
        }
    }

    bindEvents() {
        // --- BENUTZER ---
        const btnAddUser = document.getElementById('btn-add-user');
        if (btnAddUser) btnAddUser.addEventListener('click', () => this.openUserModal());

        const btnSaveUser = document.getElementById('btn-save-user');
        if (btnSaveUser) btnSaveUser.addEventListener('click', () => this.saveUser(btnSaveUser));

        // --- ROLLEN ---
        const btnAddRole = document.getElementById('btn-add-role');
        if (btnAddRole) btnAddRole.addEventListener('click', () => this.openRoleModal());

        const btnSaveRole = document.getElementById('btn-save-role');
        if (btnSaveRole) btnSaveRole.addEventListener('click', () => this.saveRole(btnSaveRole));

        // --- TABELLEN KLICKS (Delegation) ---
        this.section.addEventListener('click', (e) => {
            // User-Tabelle
            const btnEditUser = e.target.closest('.btn-edit-user');
            const btnDelUser = e.target.closest('.btn-delete-user');

            // Rollen-Tabelle
            const btnEditRole = e.target.closest('.btn-edit-role');
            const btnDelRole = e.target.closest('.btn-delete-role');

            if (btnEditUser) {
                e.preventDefault();
                this.openUserModal(JSON.parse(btnEditUser.dataset.payload));
            }
            if (btnDelUser) {
                e.preventDefault();
                this.deleteUser(btnDelUser.dataset.id, btnDelUser.dataset.name);
            }

            if (btnEditRole) {
                e.preventDefault();
                this.openRoleModal(JSON.parse(btnEditRole.dataset.payload));
            }
            if (btnDelRole) {
                e.preventDefault();
                this.deleteRole(btnDelRole.dataset.id);
            }
        });
    }

    // ==========================================
    // USER LOGIK
    // ==========================================
    openUserModal(payload = null) {
        const form = document.getElementById('user-form');
        if (form) form.reset();

        if (payload) {
            document.getElementById('modal-title-user').textContent = 'Benutzer bearbeiten';
            document.getElementById('user_id').value = payload.id;
            document.getElementById('user_name').value = payload.name;
            document.getElementById('user_email').value = payload.email;
            document.getElementById('user_role').value = payload.roleId;
            document.getElementById('user_pass').placeholder = 'Leer lassen, um nicht zu ändern';
            document.getElementById('user_pass').required = false;
        } else {
            document.getElementById('modal-title-user').textContent = 'Neuen Benutzer anlegen';
            document.getElementById('user_id').value = '';
            document.getElementById('user_pass').placeholder = 'Passwort';
            document.getElementById('user_pass').required = true;
        }
        this.modalManager.open('user-modal');
    }

    async saveUser(btnElement) {
        const form = document.getElementById('user-form');
        if (!form || !form.reportValidity()) return;

        const originalText = btnElement.innerHTML;
        btnElement.disabled = true;
        btnElement.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Speichere...';

        const formData = new window.FormData(form);
        const result = await this.api.post('save_user', formData);

        if (result.success) {
            this.api.showStatus(result.message, 'success');
            this.modalManager.close('user-modal');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            this.api.showStatus(result.error, 'error');
            btnElement.disabled = false;
            btnElement.innerHTML = originalText;
        }
    }

    async deleteUser(id, name) {
        if (!confirm(`Möchtest du den Benutzer '${name}' wirklich löschen?`)) return;

        const formData = new window.FormData();
        formData.append('user_id', id);

        const result = await this.api.post('delete_user', formData);
        if (result.success) {
            this.api.showStatus(result.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            this.api.showStatus(result.error, 'error');
        }
    }

    // ==========================================
    // ROLLEN LOGIK
    // ==========================================
    openRoleModal(payload = null) {
        const form = document.getElementById('role-form');
        if (form) form.reset();

        if (payload) {
            document.getElementById('modal-title-role').textContent = 'Rolle bearbeiten';
            document.getElementById('role_id').value = payload.id;
            document.getElementById('role_id').readOnly = true;
            document.getElementById('role_name').value = payload.name;

            // Checkboxen anhaken
            if (payload.permissions) {
                payload.permissions.forEach((perm) => {
                    const cb = document.getElementById(`perm_${perm}`);
                    if (cb) cb.checked = true;
                });
            }
        } else {
            document.getElementById('modal-title-role').textContent = 'Neue Rolle erstellen';
            document.getElementById('role_id').value = '';
            document.getElementById('role_id').readOnly = false;
        }
        this.modalManager.open('role-modal');
    }

    async saveRole(btnElement) {
        const form = document.getElementById('role-form');
        if (!form || !form.reportValidity()) return;

        const originalText = btnElement.innerHTML;
        btnElement.disabled = true;
        btnElement.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Speichere...';

        const formData = new window.FormData(form);
        const result = await this.api.post('save_role', formData);

        if (result.success) {
            this.api.showStatus(result.message, 'success');
            this.modalManager.close('role-modal');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            this.api.showStatus(result.error, 'error');
            btnElement.disabled = false;
            btnElement.innerHTML = originalText;
        }
    }

    async deleteRole(id) {
        if (
            !confirm(
                `Möchtest du die Rolle '${id}' wirklich löschen? Benutzer mit dieser Rolle erhalten Fallback-Rechte.`
            )
        )
            return;

        const formData = new window.FormData();
        formData.append('role_id', id);

        const result = await this.api.post('delete_role', formData);
        if (result.success) {
            this.api.showStatus(result.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            this.api.showStatus(result.error, 'error');
        }
    }
}
