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
        // --- 1. Tab-Steuerung (Benutzer vs. Rollen) ---
        const tabBtns = this.section.querySelectorAll('.media-tab-btn');
        const views = this.section.querySelectorAll('.media-view');

        tabBtns.forEach((btn) => {
            btn.addEventListener('click', () => {
                tabBtns.forEach((b) => {
                    b.classList.remove('active');
                    b.classList.add('edit');
                });
                views.forEach((v) => {
                    v.style.display = 'none';
                });
                btn.classList.remove('edit');
                btn.classList.add('active');

                const targetId = `media-view-${btn.dataset.type}`;
                const targetView = document.getElementById(targetId);
                if (targetView) targetView.style.display = 'block';
            });
        });

        // --- BENUTZER ---
        const btnAddUser = document.getElementById('btn-add-user');
        if (btnAddUser) btnAddUser.addEventListener('click', () => this.openUserModal());

        // --- ROLLEN ---
        const btnAddRole = document.getElementById('btn-add-role');
        if (btnAddRole) btnAddRole.addEventListener('click', () => this.openRoleModal());

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

        // Event Delegation für alle Modal-Buttons in Systemverwaltung
        document.addEventListener('click', (e) => {
            const btnSaveUser = e.target.closest('#btn-save-user');
            const btnSaveRole = e.target.closest('#btn-save-role');
            const btnCloseUser = e.target.closest('.btn-close-user-modal');
            const btnCloseRole = e.target.closest('.btn-close-role-modal');

            if (btnSaveUser) {
                e.preventDefault();
                this.saveUser(btnSaveUser);
            }
            if (btnSaveRole) {
                e.preventDefault();
                this.saveRole(btnSaveRole);
            }
            if (btnCloseUser) {
                e.preventDefault();
                this.modalManager.close('user-modal');
            }
            if (btnCloseRole) {
                e.preventDefault();
                this.modalManager.close('role-modal');
            }

            // Gott-Modus Logik für Rechte
            if (e.target.id === 'role_all_perms') {
                const checked = e.target.checked;
                document
                    .getElementById('role-form')
                    ?.querySelectorAll('.perm-parent, .perm-child')
                    .forEach((cb) => {
                        cb.checked = checked;
                    });
            }

            if (e.target.classList.contains('perm-parent')) {
                const children = document
                    .getElementById('role-form')
                    ?.querySelectorAll(`.perm-child[data-parent="${e.target.value}"]`);
                children?.forEach((child) => {
                    child.checked = e.target.checked;
                });
                this.checkGodMode();
            }

            if (e.target.classList.contains('perm-child')) {
                const parentCb = document
                    .getElementById('role-form')
                    ?.querySelector(`.perm-parent[value="${e.target.dataset.parent}"]`);
                if (!e.target.checked) {
                    if (parentCb) parentCb.checked = false;
                    const allPermsCb = document.getElementById('role_all_perms');
                    if (allPermsCb) allPermsCb.checked = false;
                } else {
                    const siblings = document
                        .getElementById('role-form')
                        ?.querySelectorAll(`.perm-child[data-parent="${e.target.dataset.parent}"]`);
                    const allSiblingsChecked = Array.from(siblings || []).every((s) => s.checked);
                    if (allSiblingsChecked && parentCb) parentCb.checked = true;
                    this.checkGodMode();
                }
            }
        });
    }

    checkGodMode() {
        const allPermsCb = document.getElementById('role_all_perms');
        if (!allPermsCb) return;
        const allChecked = Array.from(
            document.getElementById('role-form')?.querySelectorAll('.perm-checkbox') || []
        ).every((cb) => cb.checked);
        allPermsCb.checked = allChecked;
    }

    // ==========================================
    // USER LOGIK
    // ==========================================
    openUserModal(payload = null) {
        const form = document.getElementById('user-form');
        if (form) form.reset();

        // Crash-Sichere Zuweisung
        const setVal = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.value = val || '';
        };
        const setProp = (id, prop, val) => {
            const el = document.getElementById(id);
            if (el) el[prop] = val;
        };

        const titleEl = document.getElementById('modal-title-user');

        if (payload) {
            if (titleEl) titleEl.textContent = 'Benutzer bearbeiten';
            setVal('user_id', payload.id);
            setVal('user_name', payload.name);
            setVal('user_username', payload.username || payload.name);
            setVal('user_email', payload.email);
            setVal('user_role', payload.role_id || payload.roleId);

            setProp('user_pass', 'placeholder', 'Leer lassen, um nicht zu ändern');
            setProp('user_password', 'placeholder', 'Leer lassen, um nicht zu ändern');
            setProp('user_pass', 'required', false);
            setProp('user_password', 'required', false);
        } else {
            if (titleEl) titleEl.textContent = 'Neuen Benutzer anlegen';
            setVal('user_id', '');
            setProp('user_pass', 'placeholder', 'Passwort');
            setProp('user_password', 'placeholder', 'Passwort');
            setProp('user_pass', 'required', true);
            setProp('user_password', 'required', true);
        }
        this.modalManager.open('user-modal');
    }

    async saveUser(btnElement) {
        const form = document.getElementById('user-form');
        if (!form?.reportValidity()) return;

        const originalText = btnElement.innerHTML;
        btnElement.disabled = true;
        btnElement.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Speichere...';

        try {
            const formData = new window.FormData(form);
            const result = await this.api.post('save_user', formData);

            if (result.success) {
                window.isDirty = false;
                this.api.showStatus(result.message, 'success');
                this.modalManager.close('user-modal');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                this.api.showStatus(result.error, 'error');
            }
        } finally {
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
            window.isDirty = false;
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

        const setVal = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.value = val || '';
        };
        const setProp = (id, prop, val) => {
            const el = document.getElementById(id);
            if (el) el[prop] = val;
        };
        const titleEl = document.getElementById('modal-title-role');

        if (payload) {
            if (titleEl) titleEl.textContent = 'Rolle bearbeiten';
            setVal('role_id', payload.id);
            setProp('role_id', 'readOnly', true);
            setVal('role_name', payload.name);

            const allPermsCb = document.getElementById('role_all_perms');
            if (payload.permissions?.includes('*')) {
                if (allPermsCb) allPermsCb.checked = true;
                form?.querySelectorAll('.perm-checkbox').forEach((cb) => {
                    cb.checked = true;
                });
            } else if (payload.permissions) {
                payload.permissions.forEach((perm) => {
                    const cb = form?.querySelector(`.perm-checkbox[value="${perm}"]`);
                    if (cb) cb.checked = true;
                });

                form?.querySelectorAll('.perm-child:checked').forEach((child) => {
                    const siblings = form.querySelectorAll(
                        `.perm-child[data-parent="${child.dataset.parent}"]`
                    );
                    const allChecked = Array.from(siblings).every((s) => s.checked);
                    const pCb = form.querySelector(`.perm-parent[value="${child.dataset.parent}"]`);
                    if (allChecked && pCb) pCb.checked = true;
                });
                this.checkGodMode();
            }
        } else {
            if (titleEl) titleEl.textContent = 'Neue Rolle erstellen';
            setVal('role_id', '');
            setProp('role_id', 'readOnly', false);
        }
        this.modalManager.open('role-modal');
    }

    async saveRole(btnElement) {
        const form = document.getElementById('role-form');
        if (!form?.reportValidity()) return;

        const originalText = btnElement.innerHTML;
        btnElement.disabled = true;
        btnElement.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Speichere...';

        try {
            const perms = [];
            if (document.getElementById('role_all_perms')?.checked) {
                perms.push('*');
            } else {
                form.querySelectorAll('.perm-checkbox:checked').forEach((cb) => {
                    perms.push(cb.value);
                });
            }

            const formData = new window.FormData(form);
            formData.set('permissions', JSON.stringify(perms));

            const result = await this.api.post('save_role', formData);

            if (result.success) {
                window.isDirty = false;
                this.api.showStatus(result.message, 'success');
                this.modalManager.close('role-modal');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                this.api.showStatus(result.error, 'error');
            }
        } finally {
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
            window.isDirty = false;
            this.api.showStatus(result.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            this.api.showStatus(result.error, 'error');
        }
    }
}
