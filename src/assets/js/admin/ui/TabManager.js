export class TabManager {
    constructor(api) {
        this.api = api;
        this.bindEvents();
        this.restoreActiveTab();
    }

    async loadTab(section) {
        const tabName = section.dataset.ajaxTab;
        if (!tabName || section.dataset.loaded === 'true') return;

        // Zeige schönen Lade-Spinner
        section.innerHTML =
            '<div style="padding: 100px; text-align: center; color: var(--text-color-faded);"><i class="fa-solid fa-spinner fa-spin fa-3x"></i><br><br><span style="display:block; margin-top:15px; font-weight:bold;">Lade Daten aus der Datenbank...</span></div>';

        try {
            // FIX: Wir fetchen die aktuelle Seite (/admin) statt /api/admin_dashboard
            const response = await fetch(`${window.location.pathname}?ajax_tab=${tabName}`);
            const res = await response.json();

            if (res.success) {
                section.innerHTML = res.html;
                section.dataset.loaded = 'true';
                // WICHTIG: Event abfeuern, damit DataTable sich an die neuen Elemente bindet
                document.dispatchEvent(
                    new CustomEvent('tabLoaded', { detail: { tab: section.id } })
                );
            } else {
                section.innerHTML = `<div class="status-message status-red visible">${res.error || 'Ladefehler'}</div>`;
            }
        } catch (e) {
            console.error('[TabManager] Fehler beim Laden des Tabs:', e);
            section.innerHTML =
                '<div class="status-message status-red visible">Fehler beim Laden des Tabs. Server nicht erreichbar.</div>';
        }
    }

    bindEvents() {
        document.querySelectorAll('#menu .tab-link').forEach((link) => {
            link.addEventListener('click', (e) => {
                const target = e.currentTarget.dataset.target;
                if (!target) return;

                sessionStorage.setItem('activeAdminTab', target);
                window.history.replaceState(null, null, `#${target}`); // URL-Hash anpassen

                document.querySelectorAll('.content-section').forEach((sec) => {
                    sec.classList.remove('active');
                });
                document.querySelectorAll('#menu .tab-link').forEach((l) => {
                    l.classList.remove('active');
                });

                const targetSection = document.getElementById(target);
                if (targetSection) {
                    targetSection.classList.add('active');
                    this.loadTab(targetSection);
                }
                e.currentTarget.classList.add('active');
            });
        });
    }

    restoreActiveTab() {
        let activeTab = sessionStorage.getItem('activeAdminTab') ?? 'section-comics';

        // Hash prüfen. Wenn wir per URL direkt auf einen Tab zugreifen, gewinnt der Hash!
        if (window.location.hash) {
            const hashTab = window.location.hash.substring(1);
            if (
                document.getElementById(hashTab) &&
                document.querySelector(`#menu .tab-link[data-target="${hashTab}"]`)
            ) {
                activeTab = hashTab;
                sessionStorage.setItem('activeAdminTab', activeTab);
            }
        }

        document.querySelectorAll('.content-section').forEach((sec) => {
            sec.classList.remove('active');
        });
        document.querySelectorAll('#menu .tab-link').forEach((l) => {
            l.classList.remove('active');
        });

        const targetSection = document.getElementById(activeTab);
        const targetLink = document.querySelector(`#menu .tab-link[data-target="${activeTab}"]`);

        if (targetSection) {
            targetSection.classList.add('active');
            this.loadTab(targetSection); // Initiales Lazy-Load
        }
        if (targetLink) {
            targetLink.classList.add('active');
        }
    }
}
