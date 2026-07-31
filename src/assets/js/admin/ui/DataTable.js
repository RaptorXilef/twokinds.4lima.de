import { debounce } from '../utils/Utils.js';

export class DataTable {
    constructor(config) {
        this.tableBody = document.querySelector(config.tableBodySelector);
        this.searchInput = document.getElementById(config.searchInputId);
        this.perPageSelect = document.getElementById(config.perPageSelectId);
        this.paginationContainer = document.getElementById(config.paginationContainerId);

        if (
            !this.tableBody ||
            !this.searchInput ||
            !this.perPageSelect ||
            !this.paginationContainer
        )
            return;

        this.allRows = Array.from(this.tableBody.querySelectorAll('tr')).filter(
            (row) => !row.classList.contains('empty-table-message')
        );

        // Eindeutiger Key für diesen Tab, um sich die Paginierung zu merken
        const safeSelector = config.tableBodySelector.replace(/[^a-zA-Z0-9]/g, '_');
        this.stateKey = `admin_dt_state_${safeSelector}`;

        this.currentPage = 1;
        this.itemsPerPage = this.perPageSelect.value;
        this.currentSearchQuery = '';

        this.restoreState(); // Lade alte Seite!
        this.bindEvents();
        this.renderTable();
    }

    saveState() {
        sessionStorage.setItem(
            this.stateKey,
            JSON.stringify({
                page: this.currentPage,
                limit: this.itemsPerPage,
                query: this.currentSearchQuery,
            })
        );
    }

    restoreState() {
        try {
            const s = JSON.parse(sessionStorage.getItem(this.stateKey));
            if (s) {
                this.currentPage = s.page || 1;
                if (s.limit && this.perPageSelect) {
                    this.itemsPerPage = s.limit;
                    this.perPageSelect.value = s.limit;
                }
                if (s.query !== undefined && this.searchInput) {
                    this.currentSearchQuery = s.query;
                    this.searchInput.value = s.query;
                }
            }
        } catch (_err) {} // LINTER FIX: Unused variable
    }

    bindEvents() {
        // PERFORMANCE BOOST: Tabelle wird nur gefiltert, wenn der Nutzer aufhört zu tippen
        this.searchInput.addEventListener(
            'input',
            debounce((e) => {
                this.currentSearchQuery = e.target.value;
                this.currentPage = 1;
                this.renderTable();
            }, 250)
        );

        this.perPageSelect.addEventListener('change', (e) => {
            this.itemsPerPage = e.target.value;
            this.currentPage = 1;
            this.renderTable();
        });
    }

    renderPaginationButtons(totalPages) {
        this.paginationContainer.innerHTML = '';
        if (totalPages <= 1) return;

        const createBtn = (text, isDisabled, isActive, clickHandler) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = `button ${isActive ? 'edit ' : ''}${isDisabled ? 'disabled' : ''}`;
            btn.innerHTML = text;
            if (isDisabled) btn.style.opacity = '0.5';
            if (!isDisabled && clickHandler) btn.onclick = clickHandler;
            return btn;
        };

        // Erste Seite (<<)
        this.paginationContainer.appendChild(
            createBtn('&laquo;', this.currentPage === 1, false, () => {
                this.currentPage = 1;
                this.renderTable();
            })
        );

        // Vorherige Seite (<)
        this.paginationContainer.appendChild(
            createBtn('&lsaquo;', this.currentPage === 1, false, () => {
                this.currentPage--;
                this.renderTable();
            })
        );

        const startPage = Math.max(1, this.currentPage - 2);
        const endPage = Math.min(totalPages, this.currentPage + 2);

        if (startPage > 1) {
            this.paginationContainer.appendChild(
                createBtn('1', false, false, () => {
                    this.currentPage = 1;
                    this.renderTable();
                })
            );
            if (startPage > 2) {
                this.paginationContainer.appendChild(createBtn('...', true, false, null));
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            this.paginationContainer.appendChild(
                createBtn(i.toString(), false, i === this.currentPage, () => {
                    this.currentPage = i;
                    this.renderTable();
                })
            );
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                this.paginationContainer.appendChild(createBtn('...', true, false, null));
            }
            this.paginationContainer.appendChild(
                createBtn(totalPages.toString(), false, false, () => {
                    this.currentPage = totalPages;
                    this.renderTable();
                })
            );
        }

        // Nächste Seite (>)
        this.paginationContainer.appendChild(
            createBtn('&rsaquo;', this.currentPage === totalPages, false, () => {
                this.currentPage++;
                this.renderTable();
            })
        );

        // Letzte Seite (>>)
        this.paginationContainer.appendChild(
            createBtn('&raquo;', this.currentPage === totalPages, false, () => {
                this.currentPage = totalPages;
                this.renderTable();
            })
        );

        // Direktauswahl per Eingabefeld
        const jumpWrapper = document.createElement('div');
        jumpWrapper.style.display = 'flex';
        jumpWrapper.style.alignItems = 'center';
        jumpWrapper.style.marginLeft = '15px';
        jumpWrapper.style.gap = '8px';

        const jumpLabel = document.createElement('span');
        jumpLabel.textContent = 'Seite:';
        jumpLabel.style.fontSize = '0.9em';
        jumpLabel.style.color = 'var(--text-color-faded)';

        const jumpInput = document.createElement('input');
        jumpInput.type = 'number';
        jumpInput.min = 1;
        jumpInput.max = totalPages;
        jumpInput.value = this.currentPage;
        jumpInput.style.width = '60px';
        jumpInput.style.padding = '4px 8px';
        jumpInput.style.border = '1px solid var(--border-medium)';
        jumpInput.style.borderRadius = '4px';
        jumpInput.style.background = 'var(--content-bg)';
        jumpInput.style.color = 'var(--text-color)';

        jumpInput.addEventListener('change', (e) => {
            let page = parseInt(e.target.value, 10);
            if (isNaN(page) || page < 1) page = 1;
            if (page > totalPages) page = totalPages;
            this.currentPage = page;
            this.renderTable();
        });

        jumpWrapper.appendChild(jumpLabel);
        jumpWrapper.appendChild(jumpInput);
        this.paginationContainer.appendChild(jumpWrapper);
    }

    renderTable() {
        const filteredRows = this.allRows.filter((row) =>
            row.textContent.toLowerCase().includes(this.currentSearchQuery.toLowerCase())
        );
        const totalItems = filteredRows.length;
        const limit = this.itemsPerPage === 'all' ? totalItems : parseInt(this.itemsPerPage, 10);
        const totalPages = limit > 0 ? Math.ceil(totalItems / limit) : 1;

        if (this.currentPage > totalPages) this.currentPage = totalPages || 1;
        const startIndex = limit === totalItems ? 0 : (this.currentPage - 1) * limit;
        const endIndex = startIndex + limit;

        this.allRows.forEach((row) => {
            row.style.display = 'none';
        });
        filteredRows.slice(startIndex, endIndex).forEach((row) => {
            row.style.display = '';
        });

        let emptyMsg = this.tableBody.querySelector('.dyn-empty-msg');
        if (filteredRows.length === 0) {
            if (!emptyMsg) {
                emptyMsg = document.createElement('tr');
                emptyMsg.className = 'dyn-empty-msg empty-table-message';
                emptyMsg.innerHTML = `<td colspan="10">Keine Ergebnisse für "${this.currentSearchQuery}" gefunden.</td>`;
                this.tableBody.appendChild(emptyMsg);
            }
            emptyMsg.style.display = '';
        } else if (emptyMsg) {
            emptyMsg.style.display = 'none';
        }

        this.renderPaginationButtons(totalPages);
        this.saveState(); // Speichere den Zustand bei jedem Rendern!
    }
}
