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
            // Wir fetchen die aktuelle Seite (/admin) statt /api/admin_dashboard
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
                console.error('[TabManager] Server meldet Ladefehler:', res.error);
                section.innerHTML = `<div class="status-message status-red visible">${res.error || 'Ladefehler'}</div>`;
            }
        } catch (err) {
            console.error('[TabManager] Kritischer Fehler beim Laden des Tabs:', err);
            section.innerHTML =
                '<div class="status-message status-red visible"><i class="fa-solid fa-bomb"></i> Fehler beim Laden des Tabs. Server nicht erreichbar.</div>';
        }
    }

    bindEvents() {
        document.querySelectorAll('#menu .tab-link').forEach((link) => {
            link.addEventListener('click', (e) => {
                const target = e.currentTarget.dataset.target;
                if (!target) return;

                try {
                    sessionStorage.setItem('activeAdminTab', target);
                } catch (err) {
                    console.warn('[TabManager] Konnte aktiven Tab nicht speichern:', err);
                }

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
        let activeTab = 'section-comics';

        try {
            const saved = sessionStorage.getItem('activeAdminTab');
            if (saved) activeTab = saved;
        } catch (err) {
            console.warn('[TabManager] Konnte aktiven Tab nicht lesen:', err);
        }

        // Hash prüfen. Wenn wir per URL direkt auf einen Tab zugreifen, gewinnt der Hash!
        if (window.location.hash) {
            const hashTab = window.location.hash.substring(1);
            if (
                document.getElementById(hashTab) &&
                document.querySelector(`#menu .tab-link[data-target="${hashTab}"]`)
            ) {
                activeTab = hashTab;
                try {
                    sessionStorage.setItem('activeAdminTab', activeTab);
                } catch (_err) {
                    // Ignorieren
                }
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
