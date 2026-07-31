export class AccordionManager {
    constructor() {
        this.triggers = document.querySelectorAll('.accordion-trigger');
        if (this.triggers.length > 0) {
            this.init();
        }
    }

    init() {
        this.triggers.forEach((trigger) => {
            trigger.addEventListener('click', () => {
                const content = trigger.nextElementSibling;
                const icon = trigger.querySelector('.transition-icon');

                if (!content) return;

                if (content.style.display === 'none' || content.style.display === '') {
                    content.style.display = 'block';
                    trigger.style.backgroundColor = 'var(--button-hover-bg)';
                    if (icon) icon.style.transform = 'rotate(180deg)';
                } else {
                    content.style.display = 'none';
                    trigger.style.backgroundColor = 'var(--table-row-even)';
                    if (icon) icon.style.transform = 'rotate(0deg)';
                }
            });
        });
    }
}
