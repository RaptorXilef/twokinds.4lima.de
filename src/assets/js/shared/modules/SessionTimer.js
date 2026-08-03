export class SessionTimer {
    constructor(api, elementId, notifications = null, maxIdleSeconds = 1800) {
        this.api = api;
        this.notifications = notifications; // Wird nur im Admin-Bereich übergeben
        this.maxIdleSeconds = maxIdleSeconds;
        this.idleSeconds = 0;
        this.timerElement = document.getElementById('admin-session-timer');

        if (this.timerElement) {
            this.init();
        }
    }

    init() {
        // UI jede Sekunde aktualisieren
        this.intervalId = setInterval(() => this.tick(), 1000);

        // Die Funktion zum Zurücksetzen wird gedrosselt (Throttle),
        // sodass sie bei Dauer-Aktivität maximal alle 10 Sekunden feuert.
        this.throttledReset = this.throttle(() => this.resetTimer(), 10000);

        // const activityEvents = ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart'];
        const activityEvents = ['click', 'keydown', 'touchstart', 'scroll'];
        activityEvents.forEach((event) => {
            document.addEventListener(event, this.throttledReset, { passive: true });
        });

        this.updateUI();
    }

    tick() {
        this.idleSeconds++;
        this.updateUI();

        if (this.idleSeconds >= this.maxIdleSeconds) {
            clearInterval(this.intervalId);
            this.timerElement.innerHTML =
                '<i class="fa-solid fa-hourglass-end" style="color: var(--status-red-text);"></i> <span style="color: var(--status-red-text);">Abgelaufen</span>';
            this.notifications.show(
                'Ihre Sitzung ist abgelaufen. Bitte loggen Sie sich neu ein.',
                'error'
            );

            setTimeout(() => {
                window.location.reload();
            }, 3000);
        }
    }

    updateUI() {
        const remaining = Math.max(0, this.maxIdleSeconds - this.idleSeconds);
        const minutes = Math.floor(remaining / 60);
        const seconds = remaining % 60;

        const formatted = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

        let colorClass = '';
        if (remaining <= 300) {
            // Unter 5 Minuten -> Orange
            colorClass = 'color: var(--status-orange-text); font-weight: bold;';
        }
        if (remaining <= 60) {
            // Unter 1 Minute -> Rot
            colorClass = 'color: var(--status-red-text); font-weight: bold;';
        }

        this.timerElement.innerHTML = `<i class="fa-solid fa-clock"></i> <span style="${colorClass}">${formatted}</span>`;
    }

    async resetTimer() {
        // Wenn die Zeit bereits abgelaufen ist, nicht mehr anfunken
        if (this.idleSeconds >= this.maxIdleSeconds) return;

        this.idleSeconds = 0;
        this.updateUI();

        try {
            await this.api.get('keep_alive');
        } catch (e) {
            console.warn('[SessionTimer] Konnte Sitzung nicht verlängern.', e);
        }
    }

    throttle(func, limit) {
        let inThrottle;
        return function (...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => (inThrottle = false), limit);
            }
        };
    }
}
