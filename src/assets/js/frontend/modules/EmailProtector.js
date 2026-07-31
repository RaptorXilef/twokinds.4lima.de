export class EmailProtector {
    constructor() {
        this.emailButton = document.getElementById('impressum-email-button');
        this.placeholder = document.getElementById('email-placeholder');

        if (this.emailButton && this.placeholder) {
            this.init();
        }
    }

    init() {
        this.emailButton.addEventListener('click', () => {
            // Wir lesen die vom PHP generierten data-Attribute aus!
            const m = this.emailButton.dataset.user;
            const d = this.emailButton.dataset.domain;

            if (m && d) {
                this.placeholder.innerHTML = `<a href="mailto:${m}@${d}" style="font-weight: bold; color: var(--link-color);">${m}@${d}</a>`;
                // Optional, aber gute UX: Button nach dem Klick ausblenden
                this.emailButton.style.display = 'none';
            }
        });
    }
}
