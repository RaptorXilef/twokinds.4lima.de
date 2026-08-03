export class SessionTimer {
    constructor(api, maxIdleSeconds = 1800) {
        this.api = api;
        this.maxIdleSeconds = maxIdleSeconds;
        this.idleSeconds = 0;
        this.timerElement = document.getElementById('frontend-session-timer');

        if (this.timerElement) {
            this.init();
        }
    }

    init() {
        this.intervalId = setInterval(() => this.tick(), 1000);
        this.throttledReset = this.throttle(() => this.resetTimer(), 10000);

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

            alert(
                'Deine Sitzung ist aus Sicherheitsgründen abgelaufen. Bitte logge dich erneut ein.'
            );
            window.location.reload();
        }
    }

    updateUI() {
        const remaining = Math.max(0, this.maxIdleSeconds - this.idleSeconds);
        const minutes = Math.floor(remaining / 60);
        const seconds = remaining % 60;
        const formatted = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

        let colorClass = '';
        if (remaining <= 300) {
            colorClass = 'color: var(--status-orange-text); font-weight: bold;';
        }
        if (remaining <= 60) {
            colorClass = 'color: var(--status-red-text); font-weight: bold;';
        }

        this.timerElement.innerHTML = `<i class="fa-solid fa-clock"></i> <span style="${colorClass}">${formatted}</span>`;
    }

    async resetTimer() {
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
