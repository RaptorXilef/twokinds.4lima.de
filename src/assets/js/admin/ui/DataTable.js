import { debounce } from '../utils/Utils.js';
import { renderPagination } from './Pagination.js';

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
        try {
            sessionStorage.setItem(
                this.stateKey,
                JSON.stringify({
                    page: this.currentPage,
                    limit: this.itemsPerPage,
                    query: this.currentSearchQuery,
                })
            );
        } catch (err) {
            console.error('[DataTable] Konnte Tabellen-Status nicht speichern:', err);
        }
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
        } catch (err) {
            console.warn(
                '[DataTable] Konnte Tabellen-Status nicht wiederherstellen (Defektes JSON oder Storage blockiert):',
                err
            );
        }
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

        renderPagination(this.paginationContainer, this.currentPage, totalPages, (newPage) => {
            this.currentPage = newPage;
            this.renderTable();
        });

        this.saveState(); // Speichere den Zustand bei jedem Rendern!
    }
}
