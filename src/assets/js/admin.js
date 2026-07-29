document.addEventListener('DOMContentLoaded', () => {
    const statusBox = document.getElementById('global-status-message');
    const baseUrlMatch = window.location.pathname.match(/^(.*)\/admin/);
    const baseUrl = baseUrlMatch ? baseUrlMatch[1] : '';

    // --- UNSAVED CHANGES WARNING ---
    window.isDirty = false;
    window.addEventListener('beforeunload', (e) => {
        if (window.isDirty) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    // Registriere Änderungen an Textfeldern
    document.addEventListener('input', (e) => {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
            window.isDirty = true;
        }
    });

    // --- GLOBAL ERROR HANDLER FOR IMAGES ---
    document.addEventListener(
        'error',
        (e) => {
            if (
                e.target &&
                e.target.tagName === 'IMG' &&
                e.target.classList.contains('hide-on-error')
            ) {
                e.target.style.display = 'none';
            }
        },
        true
    ); // "true" fängt das Event frühzeitig ab!

    // --- WYSIWYG TRUMBOWYG INITIALISIERUNG ---
    if (typeof $.fn.trumbowyg !== 'undefined') {
        $.trumbowyg.svgPath =
            'https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/ui/icons.svg';
        $('.wysiwyg-editor')
            .trumbowyg({
                lang: 'de',
                btns: [
                    ['viewHTML'],
                    ['undo', 'redo'],
                    ['formatting'],
                    ['strong', 'em', 'del'],
                    ['link'],
                    ['insertImage'],
                    ['unorderedList', 'orderedList'],
                    ['removeformat'],
                ],
            })
            .on('tbwchange', () => {
                window.isDirty = true;
            });
    }

    // --- ROW HIGHLIGHT & SCROLL LOGIC ---
    function highlightAndScroll(id) {
        if (!id) return;
        // Wir suchen den Delete-Button, da dieser die reine ID im data-id Attribut hat
        const targetBtn =
            document.querySelector(`.btn-delete-comic[data-id="${id}"]`) ??
            document.querySelector(`.btn-delete-char[data-id="${id}"]`);
        const tr = targetBtn?.closest('tr');
        if (tr) {
            tr.scrollIntoView({ behavior: 'smooth', block: 'center' });
            tr.classList.add('row-highlight');
            setTimeout(() => {
                tr.classList.remove('row-highlight');
            }, 3000);
        }
    }

    // Beim Laden der Seite prüfen, ob wir gerade gespeichert haben
    const highlightId = sessionStorage.getItem('highlightEntityId');
    if (highlightId) {
        setTimeout(() => {
            highlightAndScroll(highlightId);
        }, 300); // 300ms warten, bis Tabs initialisiert sind
        sessionStorage.removeItem('highlightEntityId');
    }

    // --- TAB LOGIK (GLOBAL) ---
    const activeTab = sessionStorage.getItem('activeAdminTab') ?? 'section-comics';
    document.querySelectorAll('.content-section').forEach((sec) => {
        sec.classList.remove('active');
    });
    document.querySelectorAll('#menu .tab-link').forEach((l) => {
        l.classList.remove('active');
    });

    const targetSection = document.getElementById(activeTab);
    const targetLink = document.querySelector(`#menu .tab-link[data-target="${activeTab}"]`);
    if (targetSection) targetSection.classList.add('active');
    if (targetLink) targetLink.classList.add('active');

    document.querySelectorAll('#menu .tab-link').forEach((link) => {
        link.addEventListener('click', function () {
            if (this.dataset.target) {
                sessionStorage.setItem('activeAdminTab', this.dataset.target);
                document.querySelectorAll('.content-section').forEach((sec) => {
                    sec.classList.remove('active');
                });
                document.querySelectorAll('#menu .tab-link').forEach((l) => {
                    l.classList.remove('active');
                });
                document.getElementById(this.dataset.target)?.classList.add('active');
                this.classList.add('active');
            }
        });
    });

    // --- PAGINIERUNG & INTELLIGENTE SUCHE (COMICS) ---
    const comicSearchInput = document.getElementById('comic-search');
    const comicPerPageSelect = document.getElementById('comic-per-page');
    const comicTableBody = document.querySelector('.comic-editor-table tbody');
    const comicPaginationContainer = document.getElementById('comic-pagination');

    if (comicTableBody && comicSearchInput && comicPerPageSelect && comicPaginationContainer) {
        const allComicRows = Array.from(comicTableBody.querySelectorAll('tr')).filter(
            (row) => !row.classList.contains('empty-table-message')
        );
        let currentPage = 1;
        let itemsPerPage = '15';
        let currentSearchQuery = '';

        function renderPaginationButtons(totalPages) {
            comicPaginationContainer.innerHTML = '';
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

            // "Zurück" Button
            comicPaginationContainer.appendChild(
                createBtn('&laquo;', currentPage === 1, false, () => {
                    currentPage--;
                    renderComicTable();
                })
            );

            // Dynamische Seitenzahlen (max 5 Buttons in der Mitte anzeigen, damit es nicht ausufert)
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(totalPages, currentPage + 2);

            if (startPage > 1) {
                comicPaginationContainer.appendChild(
                    createBtn('1', false, false, () => {
                        currentPage = 1;
                        renderComicTable();
                    })
                );
                if (startPage > 2)
                    comicPaginationContainer.appendChild(createBtn('...', true, false, null));
            }

            for (let i = startPage; i <= endPage; i++) {
                comicPaginationContainer.appendChild(
                    createBtn(i.toString(), false, i === currentPage, () => {
                        currentPage = i;
                        renderComicTable();
                    })
                );
            }

            if (endPage < totalPages) {
                if (endPage < totalPages - 1)
                    comicPaginationContainer.appendChild(createBtn('...', true, false, null));
                comicPaginationContainer.appendChild(
                    createBtn(totalPages.toString(), false, false, () => {
                        currentPage = totalPages;
                        renderComicTable();
                    })
                );
            }

            // "Vor" Button
            comicPaginationContainer.appendChild(
                createBtn('&raquo;', currentPage === totalPages, false, () => {
                    currentPage++;
                    renderComicTable();
                })
            );
        }

        function renderComicTable() {
            // 1. Filtern (Intelligente Suche über den gesamten Text in der Zeile)
            const filteredRows = allComicRows.filter((row) =>
                row.textContent.toLowerCase().includes(currentSearchQuery.toLowerCase())
            );

            // 2. Limits & Seiten berechnen
            const totalItems = filteredRows.length;
            const limit = itemsPerPage === 'all' ? totalItems : parseInt(itemsPerPage, 10);
            const totalPages = limit > 0 ? Math.ceil(totalItems / limit) : 1;

            if (currentPage > totalPages) currentPage = totalPages || 1;
            const startIndex = limit === totalItems ? 0 : (currentPage - 1) * limit;
            const endIndex = startIndex + limit;

            allComicRows.forEach((row) => {
                row.style.display = 'none';
            });
            filteredRows.slice(startIndex, endIndex).forEach((row) => {
                row.style.display = '';
            });

            // Info, wenn Suche keine Treffer liefert
            let emptyMsg = comicTableBody.querySelector('.dyn-empty-msg');
            if (filteredRows.length === 0) {
                if (!emptyMsg) {
                    emptyMsg = document.createElement('tr');
                    emptyMsg.className = 'dyn-empty-msg empty-table-message';
                    emptyMsg.innerHTML =
                        '<td colspan="6">Keine Comics für diesen Suchbegriff gefunden.</td>';
                    comicTableBody.appendChild(emptyMsg);
                }
                emptyMsg.style.display = '';
            } else if (emptyMsg) {
                emptyMsg.style.display = 'none';
            }
            renderPaginationButtons(totalPages);
        }

        comicSearchInput.addEventListener('input', (e) => {
            currentSearchQuery = e.target.value;
            currentPage = 1;
            renderComicTable();
        });
        comicPerPageSelect.addEventListener('change', (e) => {
            itemsPerPage = e.target.value;
            currentPage = 1;
            renderComicTable();
        });

        // Initiale Paginierung auslösen
        renderComicTable();
    }

    // --- KLICK-ZOOM OVERLAY (LIGHTBOX) ---
    const hoverOverlay = document.getElementById('image-hover-overlay');
    const hoverOverlayImg = document.getElementById('hover-overlay-img');

    document.addEventListener('click', (e) => {
        const img = e.target.closest('.hover-zoom-trigger');
        if (img) {
            if (img.src && !img.src.includes('placehold.co')) {
                if (hoverOverlayImg && hoverOverlay) {
                    hoverOverlayImg.src = img.src;
                    hoverOverlay.style.display = 'flex';
                }
            }
        }
    });

    // Klick ins Schwarze schließt die Vorschau wieder
    hoverOverlay?.addEventListener('click', () => {
        hoverOverlay.style.display = 'none';
        if (hoverOverlayImg) hoverOverlayImg.src = '';
    });

    // --- LOGOUT ---
    document.getElementById('admin-logout-btn')?.addEventListener('click', async (e) => {
        e.preventDefault();
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const fd = new window.FormData();
        fd.append('csrf_token', csrfToken);
        await fetch(`${baseUrl}/api/admin_logout`, { method: 'POST', body: fd });
        window.location.reload();
    });
});
