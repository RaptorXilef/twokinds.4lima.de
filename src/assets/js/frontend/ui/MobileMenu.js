export class MobileMenu {
    constructor() {
        this.toggleBtn = document.getElementById('mobile-menu-toggle');
        this.backdrop = document.getElementById('mobile-menu-backdrop');
        this.body = document.body;

        if (this.toggleBtn && this.backdrop) {
            this.init();
        }
    }

    init() {
        this.toggleBtn.addEventListener('click', () => this.toggle());
        this.backdrop.addEventListener('click', () => this.close());
    }

    toggle() {
        this.body.classList.toggle('menu-open');
        this.backdrop.classList.toggle('is-active');
    }

    close() {
        this.body.classList.remove('menu-open');
        this.backdrop.classList.remove('is-active');
    }
}
