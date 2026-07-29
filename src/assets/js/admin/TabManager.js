export class TabManager {
    constructor() {
        this.bindEvents();
        this.restoreActiveTab();
    }

    bindEvents() {
        document.querySelectorAll('#menu .tab-link').forEach((link) => {
            link.addEventListener('click', (e) => {
                const target = e.currentTarget.dataset.target;
                if (!target) return;

                sessionStorage.setItem('activeAdminTab', target);

                document
                    .querySelectorAll('.content-section')
                    .forEach((sec) => sec.classList.remove('active'));
                document
                    .querySelectorAll('#menu .tab-link')
                    .forEach((l) => l.classList.remove('active'));

                document.getElementById(target)?.classList.add('active');
                e.currentTarget.classList.add('active');
            });
        });
    }

    restoreActiveTab() {
        const activeTab = sessionStorage.getItem('activeAdminTab') ?? 'section-comics';

        document
            .querySelectorAll('.content-section')
            .forEach((sec) => sec.classList.remove('active'));
        document.querySelectorAll('#menu .tab-link').forEach((l) => l.classList.remove('active'));

        const targetSection = document.getElementById(activeTab);
        const targetLink = document.querySelector(`#menu .tab-link[data-target="${activeTab}"]`);

        if (targetSection) targetSection.classList.add('active');
        if (targetLink) targetLink.classList.add('active');
    }
}
