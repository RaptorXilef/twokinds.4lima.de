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

        this.allChars = [];
        this.modalState = {
            inThis: [],
            inAny: [],
            viewOnlyUnassigned: false,
            selected: new Set(),
            targetGroupList: null,
        };

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
            const btnAddCharToGroup = e.target.closest('.btn-add-char-to-group');

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
                this.updatePoolAssignedState();
            }
            if (btnRemoveChar) {
                e.preventDefault();
                this.tracker.markDirty();
                const entry = btnRemoveChar.closest('.character-entry');
                if (entry) entry.remove();
                this.updatePoolAssignedState();
            }
            if (btnTogglePool) {
                e.preventDefault();
                this.togglePool(btnTogglePool);
            }
            if (btnAddCharToGroup) {
                e.preventDefault();
                this.openAddCharModal(btnAddCharToGroup);
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

        // Modal Events
        const modal = document.getElementById('group-add-char-modal');
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target.closest('.btn-close-group-char-modal')) {
                    modal.style.display = 'none';
                }
                if (e.target.closest('#btn-group-modal-toggle-view')) {
                    this.modalState.viewOnlyUnassigned = !this.modalState.viewOnlyUnassigned;
                    this.renderModalGrid();
                }
                if (e.target.closest('#btn-group-modal-confirm')) {
                    this.confirmAddChars();
                }
            });
        }
    }

    togglePool(btn) {
        this.poolViewAll = !this.poolViewAll;
        btn.textContent = this.poolViewAll ? 'Nur Unzugeordnete zeigen' : 'Alle anzeigen';
        this.updatePoolAssignedState();
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
                    this.updatePoolAssignedState();
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
                        this.updatePoolAssignedState();
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
            <div class="character-group-header d-flex align-center gap-10">
                <i class="fa-solid fa-grip-vertical group-drag-handle" title="Gruppe verschieben"></i>
                <div class="flex-1">
                    <h3 contenteditable="true" spellcheck="false" class="group-title-edit mb-0 border-bottom border-dashed outline-none">Neue Gruppe</h3>
                    <label class="font-small text-light font-normal mt-5 d-flex align-center gap-5">
                        <input type="checkbox" class="manual-sort-cb"> Manuell sortieren
                    </label>
                </div>
                <button type="button" class="button add btn-add-char-to-group" title="Charaktere hinzufügen">
                    <i class="fa-solid fa-user-plus"></i>
                </button>
                <button type="button" class="button delete btn-delete-group" title="Gruppe löschen">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="character-list-container sortable-group p-10" data-manual="false">
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

    // --- Modal Logik ---

    openAddCharModal(btn) {
        this.modalState.targetGroupList = btn
            .closest('.character-group')
            .querySelector('.character-list-container');

        // Füllt das Character Array vom Pool einmal initial ab
        const poolEntries = Array.from(document.querySelectorAll('#char-pool .character-entry'));
        this.allChars = poolEntries.map((entry) => ({
            id: entry.dataset.id,
            name: entry.querySelector('strong').textContent.trim(),
            pic: entry.querySelector('img').src,
        }));

        const charsInThisGroup = Array.from(
            this.modalState.targetGroupList.querySelectorAll('.character-entry')
        ).map((el) => el.dataset.id);
        const charsInAnyGroup = Array.from(
            document.querySelectorAll('#groups-wrapper .character-entry')
        ).map((el) => el.dataset.id);

        this.modalState.inThis = charsInThisGroup;
        this.modalState.inAny = charsInAnyGroup;
        this.modalState.selected.clear();
        this.modalState.viewOnlyUnassigned = false;

        this.renderModalGrid();

        const modal = document.getElementById('group-add-char-modal');
        if (modal) modal.style.display = 'flex';
    }

    renderModalGrid() {
        const grid = document.getElementById('group-modal-char-grid');
        if (!grid) return;

        grid.innerHTML = '';

        // Filtere alle, die bereits in DIESER speziellen Gruppe sind heraus
        let charsToShow = this.allChars.filter((c) => !this.modalState.inThis.includes(c.id));

        // Alternativer Modus: Nur zeigen, was noch in GAR KEINER Gruppe ist
        if (this.modalState.viewOnlyUnassigned) {
            charsToShow = charsToShow.filter((c) => !this.modalState.inAny.includes(c.id));
        }

        charsToShow.sort((a, b) => a.name.localeCompare(b.name));

        if (charsToShow.length === 0) {
            grid.innerHTML =
                '<p class="text-faded w-100 text-center" style="grid-column: 1/-1;">Keine passenden Charaktere gefunden.</p>';
        } else {
            charsToShow.forEach((c) => {
                const div = document.createElement('div');
                div.className = `char-selection-item ${this.modalState.selected.has(c.id) ? 'selected' : ''}`;
                div.dataset.id = c.id;
                div.innerHTML = `
                    <img src="${c.pic}" loading="lazy" alt="Char">
                    <span>${c.name}</span>
                `;
                div.addEventListener('click', () => {
                    if (this.modalState.selected.has(c.id)) {
                        this.modalState.selected.delete(c.id);
                        div.classList.remove('selected');
                    } else {
                        this.modalState.selected.add(c.id);
                        div.classList.add('selected');
                    }
                });
                grid.appendChild(div);
            });
        }

        const toggleBtnText = document.getElementById('group-modal-view-text');
        if (toggleBtnText) {
            toggleBtnText.textContent = this.modalState.viewOnlyUnassigned
                ? 'Zeige: Nur komplett Unzugeordnete'
                : 'Zeige: Alle (außer bereits in Gruppe)';
        }
    }

    confirmAddChars() {
        if (!this.modalState.targetGroupList) return;

        this.modalState.selected.forEach((id) => {
            const charData = this.allChars.find((c) => c.id === id);
            if (!charData) return;

            const entry = document.createElement('div');
            entry.className = 'character-entry d-flex justify-between align-center';
            entry.dataset.id = charData.id;
            entry.innerHTML = `
                <div class="d-flex align-center gap-10">
                    <img src="${charData.pic}">
                    <div class="character-info">
                        <strong>${charData.name}</strong>
                    </div>
                </div>
                <i class="fa-solid fa-times remove-char-from-group" title="Aus Gruppe entfernen"></i>
            `;
            this.modalState.targetGroupList.appendChild(entry);
        });

        this.tracker.markDirty();
        this.updatePoolAssignedState();
        document.getElementById('group-add-char-modal').style.display = 'none';
    }

    updatePoolAssignedState() {
        const charsInAnyGroup = Array.from(
            document.querySelectorAll('#groups-wrapper .character-entry')
        ).map((el) => el.dataset.id);
        document.querySelectorAll('#char-pool .character-entry').forEach((entry) => {
            const isAssigned = charsInAnyGroup.includes(entry.dataset.id);
            if (isAssigned) {
                entry.classList.add('is-assigned');
            } else {
                entry.classList.remove('is-assigned');
            }

            // Toggle Logic anwenden (Nur unzugeordnete zeigen Button)
            if (isAssigned && !this.poolViewAll) {
                entry.style.display = 'none';
            } else {
                entry.style.display = 'flex';
            }
        });
    }
}
