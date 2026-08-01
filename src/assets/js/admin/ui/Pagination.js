export function renderPagination(container, currentPage, totalPages, onPageChange) {
    container.innerHTML = '';
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
    container.appendChild(createBtn('&laquo;', currentPage === 1, false, () => onPageChange(1)));

    // Vorherige Seite (<)
    container.appendChild(
        createBtn('&lsaquo;', currentPage === 1, false, () => onPageChange(currentPage - 1))
    );

    // Pagination mit Abkürzungen (...)
    const startPage = Math.max(1, currentPage - 6);
    const endPage = Math.min(totalPages, currentPage + 6);

    if (startPage > 1) {
        container.appendChild(createBtn('1', false, false, () => onPageChange(1)));
        if (startPage > 2) {
            container.appendChild(createBtn('...', true, false, null));
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        container.appendChild(
            createBtn(i.toString(), false, i === currentPage, () => onPageChange(i))
        );
    }

    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            container.appendChild(createBtn('...', true, false, null));
        }
        container.appendChild(
            createBtn(totalPages.toString(), false, false, () => onPageChange(totalPages))
        );
    }

    // Nächste Seite (>)
    container.appendChild(
        createBtn('&rsaquo;', currentPage === totalPages, false, () =>
            onPageChange(currentPage + 1)
        )
    );

    // Letzte Seite (>>)
    container.appendChild(
        createBtn('&raquo;', currentPage === totalPages, false, () => onPageChange(totalPages))
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
    jumpInput.value = currentPage;
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
        onPageChange(page);
    });

    jumpWrapper.appendChild(jumpLabel);
    jumpWrapper.appendChild(jumpInput);
    container.appendChild(jumpWrapper);
}
