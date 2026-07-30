/**
 * @typedef {import('./NotificationService.js').NotificationService} NotificationService
 */

export class ErrorHandlerService {
    /**
     * @param {NotificationService} notifications
     */
    constructor(notifications) {
        this.notifications = notifications;
        this.init();
    }

    init() {
        // Fängt asynchrone Fehler ab (z.B. wenn ein fetch() komplett ins Leere läuft)
        window.addEventListener('unhandledrejection', (event) => {
            console.error('[Global Error] Unhandled Promise Rejection:', event.reason);
            this.notifications.show(
                'Ein unerwarteter Netzwerk- oder Serverfehler ist aufgetreten.',
                'error'
            );

            // Verhindert, dass der Fehler die Konsole "zumüllt" (optional)
            // event.preventDefault();
        });

        // Fängt klassische synchrone JS-Abstürze ab
        window.addEventListener('error', (event) => {
            // Ignoriere Bild-Ladefehler, darum kümmert sich bereits die GlobalUI
            if (event.target && event.target.tagName === 'IMG') return;

            console.error('[Global Error] JavaScript Exception:', event.message);
            this.notifications.show('Ein kritischer Skript-Fehler ist aufgetreten.', 'error');
        });
    }
}
