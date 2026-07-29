/**
 * @typedef {import('./Api.js').Api} Api
 * @typedef {import('./NotificationService.js').NotificationService} NotificationService
 */

export class NewsletterManager {
    /**
     * @param {Api} api
     * @param {NotificationService} notifications
     */
    constructor(api, notifications) {
        this.api = api;
        this.notifications = notifications;
        this.bindEvents();
    }

    bindEvents() {
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-trigger-newsletter');
            if (btn) {
                e.preventDefault();
                this.triggerNewsletter(btn);
            }
        });
    }

    async triggerNewsletter(btn) {
        const type = btn.dataset.type;
        const pageNumber = btn.dataset.page;
        const comicName = 'TwoKinds';
        const pageUrl = btn.dataset.url;

        if (
            !confirm(
                `Bist du sicher, dass du den ${type}-Newsletter für Seite ${pageNumber} versenden möchtest?`
            )
        ) {
            return;
        }

        const formData = new window.FormData();
        formData.append('type', type);
        formData.append('comic_name', comicName);
        formData.append('page_number', pageNumber);
        formData.append('page_url', pageUrl);

        const origText = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sende...';
        btn.disabled = true;

        try {
            const result = await this.api.post('admin_trigger_newsletter', formData);
            if (result.success) {
                this.notifications.show(result.message, 'success');
            } else {
                this.notifications.show(result.error, 'error');
            }
        } finally {
            btn.innerHTML = origText;
            btn.disabled = false;
        }
    }
}
