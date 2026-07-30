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
        this.currentPage = 1;
        this.itemsPerPage = this.perPageSelect.value;
        this.currentSearchQuery = '';

        this.bindEvents();
        this.renderTable();
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

        this.paginationContainer.appendChild(
            createBtn('&laquo;', this.currentPage === 1, false, () => {
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
            if (startPage > 2)
                this.paginationContainer.appendChild(createBtn('...', true, false, null));
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
            if (endPage < totalPages - 1)
                this.paginationContainer.appendChild(createBtn('...', true, false, null));
            this.paginationContainer.appendChild(
                createBtn(totalPages.toString(), false, false, () => {
                    this.currentPage = totalPages;
                    this.renderTable();
                })
            );
        }

        this.paginationContainer.appendChild(
            createBtn('&raquo;', this.currentPage === totalPages, false, () => {
                this.currentPage++;
                this.renderTable();
            })
        );
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
    }
}
