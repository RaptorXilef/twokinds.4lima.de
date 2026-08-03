export class RssCopier {
    constructor() {
        this.buttons = document.querySelectorAll('.js-copy-rss');
        if (this.buttons.length > 0) {
            this.init();
        }
    }

    init() {
        this.buttons.forEach((btn) => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const url = btn.dataset.url;
                if (!url) return;

                try {
                    // Modernes Copy-API (Cross-Platform)
                    if (navigator.clipboard && window.isSecureContext) {
                        await navigator.clipboard.writeText(url);
                    } else {
                        // Fallback für ältere Browser/iOS
                        const textArea = document.createElement('textarea');
                        textArea.value = url;
                        textArea.style.position = 'fixed';
                        textArea.style.left = '-999999px';
                        textArea.style.top = '-999999px';
                        document.body.appendChild(textArea);
                        textArea.focus();
                        textArea.select();
                        document.execCommand('copy');
                        textArea.remove();
                    }
                    this.showFeedback(btn);
                } catch (err) {
                    console.error('[RssCopier] Konnte RSS-Link nicht kopieren:', err);
                    alert('Kopieren fehlgeschlagen: ' + url);
                }
            });
        });
    }

    showFeedback(btn) {
        // Sucht die Sprechblase im Button oder direkt daneben
        const feedback =
            btn.querySelector('.copy-feedback') ||
            btn.parentElement.querySelector('.copy-feedback');
        if (feedback) {
            feedback.classList.add('show');
            setTimeout(() => {
                feedback.classList.remove('show');
            }, 2500); // Blendet die Sprechblase nach 2.5 Sekunden wieder aus
        }
    }
}
