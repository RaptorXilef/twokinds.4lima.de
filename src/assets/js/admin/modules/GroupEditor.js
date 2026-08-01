/**
 * @typedef {import('../core/Api.js').Api} Api
 * @typedef {import('../core/NotificationService.js').NotificationService} NotificationService
 */

export class GroupEditor {
    /**
     * @param {Api} api
     * @param {NotificationService} notifications
     */
    constructor(api, notifications, tracker) {
        this.api = api;
        this.notifications = notifications;
        this.tracker = tracker;

        /** @type {HTMLElement|null} */
        this.section = document.getElementById('section-groups');
        /** @type {boolean} */
        this.poolViewAll = true;

        if (this.section) {
            this.bindEvents();
            this.initSortable();
        }
    }

    bindEvents() {
        if (!this.section) return;

        // Alle Klicks (Buttons, Icons) zentral über die Section delegieren
        this.section.addEventListener('click', (e) => {
            const btnAddGroup = e.target.closest('#btn-add-group');
            const btnSaveGroups = e.target.closest('#btn-save-groups');
            const btnDelete = e.target.closest('.btn-delete-group');
            const btnRemoveChar = e.target.closest('.remove-char-from-group');
            const btnTogglePool = e.target.closest('#btn-toggle-pool');

            if (btnAddGroup) {
                e.preventDefault();
                this.addGroupHTML();
            }
            if (btnSaveGroups) {
                e.preventDefault();
                this.saveGroups(btnSaveGroups);
            }
            if (btnDelete) {
                e.preventDefault();
                this.tracker.markDirty();
                const groupEl = btnDelete.closest('.character-group');
                if (groupEl) groupEl.remove();
            }
            if (btnRemoveChar) {
                e.preventDefault();
                this.tracker.markDirty();
                const entry = btnRemoveChar.closest('.character-entry');
                if (entry) entry.remove();
            }
            if (btnTogglePool) {
                e.preventDefault();
                this.togglePool(btnTogglePool);
            }
        });

        // Checkbox-Änderungen delegieren
        this.section.addEventListener('change', (e) => {
            if (e.target.classList.contains('manual-sort-cb')) {
                this.tracker.markDirty();
                const container = e.target
                    .closest('.character-group')
                    ?.querySelector('.sortable-group');
                if (container) {
                    const isManual = e.target.checked;
                    container.dataset.manual = isManual ? 'true' : 'false';
                    if (typeof window.Sortable !== 'undefined') {
                        const sortableInstance = window.Sortable.get(container);
                        if (sortableInstance) sortableInstance.option('sort', isManual);
                    }
                    this.notifications.show(
                        'Sortier-Modus geändert. Nicht vergessen zu speichern.',
                        'info'
                    );
                }
            }
        });
    }

    togglePool(btn) {
        this.poolViewAll = !this.poolViewAll;
        btn.textContent = this.poolViewAll ? 'Nur Unzugeordnete zeigen' : 'Alle anzeigen';
        document.querySelectorAll('#char-pool .character-entry.is-assigned').forEach((el) => {
            el.style.display = this.poolViewAll ? 'flex' : 'none';
        });
    }

    initSortable() {
        if (typeof window.Sortable === 'undefined') return;

        // 1. Der Pool: Erzeugt Klone beim Ziehen!
        const poolEl = document.getElementById('char-pool');
        if (poolEl && !poolEl.dataset.sortableInitialized) {
            new window.Sortable(poolEl, {
                group: { name: 'shared', pull: 'clone', put: false },
                sort: false,
                animation: 150,
                onEnd: () => {
                    this.tracker.markDirty();
                },
            });
            poolEl.dataset.sortableInitialized = 'true';
        }

        // 2. Die Gruppen: Nehmen Charaktere auf
        document.querySelectorAll('.sortable-group').forEach((groupEl) => {
            if (!groupEl.dataset.sortableInitialized) {
                const manual = groupEl.dataset.manual === 'true';
                new window.Sortable(groupEl, {
                    group: 'shared',
                    animation: 150,
                    sort: manual,
                    onEnd: () => {
                        this.tracker.markDirty();
                    },
                });
                groupEl.dataset.sortableInitialized = 'true';
            }
        });

        // 3. Die Gruppen-Container selbst (Reihenfolge der Gruppen)
        const wrapper = document.getElementById('groups-wrapper');
        if (wrapper && !wrapper.dataset.sortableInitialized) {
            new window.Sortable(wrapper, {
                animation: 150,
                handle: '.group-drag-handle',
                onEnd: () => {
                    this.tracker.markDirty();
                },
            });
            wrapper.dataset.sortableInitialized = 'true';
        }
    }

    addGroupHTML() {
        this.tracker.markDirty();
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

        const titleEdit = wrapper.lastElementChild.querySelector('.group-title-edit');
        if (titleEdit) {
            titleEdit.focus();
            document.execCommand('selectAll', false, null);
        }

        this.initSortable();
    }

    async saveGroups(btnElement) {
        const originalText = btnElement.innerHTML;
        btnElement.disabled = true;
        btnElement.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Speichere...';

        try {
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

            const formData = new window.FormData();
            formData.append('groups_data', JSON.stringify(groupsData));

            // Exakt der Endpunkt, den das Backend für das alte Array erwartet!
            const result = await this.api.post('save_character_groups', formData);

            if (result.success) {
                this.tracker.markClean();
                this.notifications.show('Gruppen gespeichert!', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                this.notifications.show(result.error, 'error');
            }
        } finally {
            btnElement.disabled = false;
            btnElement.innerHTML = originalText;
        }
    }
}
