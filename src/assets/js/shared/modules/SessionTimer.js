export class SessionTimer {
    constructor(api, elementId, options = {}) {
        this.api = api;
        this.notifications = options.notifications || null; // Wird nur im Admin-Bereich übergeben
        this.maxIdleSeconds = options.maxIdleSeconds || 1800; // 30 Minuten
        this.warningThreshold = options.warningThreshold || 120; // 2 Minuten vor Ablauf
        this.isFrontend = options.isFrontend || false;

        this.idleSeconds = 0;
        this.isWarningVisible = false;
        this.modalElement = null;

        this.timerElement = document.getElementById(elementId);

        if (this.timerElement) {
            this.init();
        }
    }

    init() {
        this.createModal();
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

    createModal() {
        this.modalElement = document.createElement('div');
        this.modalElement.className = 'modal z-1070'; // Hoher z-index, damit es über allem liegt
        this.modalElement.style.zIndex = '10000';
        this.modalElement.innerHTML = `
            <div class="modal-overlay"></div>
            <div class="modal-content modal-sm text-center">
                <div class="modal-header-wrapper" style="background-color: var(--status-orange-bg); border-bottom: 1px solid var(--status-orange-border);">
                    <h2 style="color: var(--status-orange-text); margin: 0;">
                        <i class="fa-solid fa-clock"></i> Sitzung läuft ab
                    </h2>
                </div>
                <div class="modal-scroll-content">
                    <p class="mb-20">Deine Sitzung wird aufgrund von Inaktivität in Kürze beendet, um dein Konto zu schützen.</p>
                    <p class="font-2x font-bold text-danger mb-20" id="session-countdown-display">02:00</p>
                </div>
                <div class="modal-footer-actions d-flex justify-between gap-10 flex-wrap">
                    <button class="button delete flex-1" id="btn-session-logout">
                        <i class="fa-solid fa-sign-out-alt"></i> Ausloggen
                    </button>
                    <button class="button add flex-1" id="btn-session-extend">
                        <i class="fa-solid fa-check"></i> Eingeloggt bleiben
                    </button>
                </div>
            </div>
        `;
        document.body.appendChild(this.modalElement);

        const btnExtend = this.modalElement.querySelector('#btn-session-extend');
        const btnLogout = this.modalElement.querySelector('#btn-session-logout');

        btnExtend.addEventListener('click', (e) => {
            e.preventDefault();
            this.extendSession();
        });

        btnLogout.addEventListener('click', (e) => {
            e.preventDefault();
            this.forceLogout();
        });
    }

    tick() {
        this.idleSeconds++;
        const remaining = this.maxIdleSeconds - this.idleSeconds;

        if (remaining === this.warningThreshold && !this.isWarningVisible) {
            this.showWarning();
        }

        if (this.isWarningVisible && remaining >= 0) {
            const mins = Math.floor(remaining / 60)
                .toString()
                .padStart(2, '0');
            const secs = (remaining % 60).toString().padStart(2, '0');
            const display = this.modalElement.querySelector('#session-countdown-display');
            if (display) display.textContent = `${mins}:${secs}`;
        }

        if (remaining <= 0) {
            this.forceLogout();
        }

        this.updateUI();
    }

    updateUI() {
        const remaining = Math.max(0, this.maxIdleSeconds - this.idleSeconds);
        const minutes = Math.floor(remaining / 60);
        const seconds = remaining % 60;
        const formatted = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

        let colorClass = '';
        if (remaining <= this.warningThreshold) {
            colorClass = 'color: var(--status-red-text); font-weight: bold;';
        } else if (remaining <= 300) {
            // Unter 5 Minuten -> Orange
            colorClass = 'color: var(--status-orange-text); font-weight: bold;';
        }

        this.timerElement.innerHTML = `<i class="fa-solid fa-clock"></i> <span style="${colorClass}">${formatted}</span>`;
    }

    async resetTimer() {
        // Wenn die Zeit bereits abgelaufen ist, nicht mehr anfunken
        if (this.idleSeconds >= this.maxIdleSeconds) return;

        // Wenn das Warn-Modal offen ist, wird die Mausbewegung ignoriert (erzwingt Button-Klick)
        if (this.isWarningVisible) return;

        this.idleSeconds = 0;
        this.updateUI();

        try {
            await this.api.get('keep_alive');
        } catch (e) {
            console.warn('[SessionTimer] Konnte Sitzung nicht verlängern.', e);
        }
    }

    async extendSession() {
        this.modalElement.style.display = 'none';
        this.isWarningVisible = false;
        this.idleSeconds = 0;
        this.updateUI();

        try {
            await this.api.get('keep_alive');
            if (this.notifications) {
                this.notifications.show('Sitzung erfolgreich verlängert.', 'success');
            }
        } catch (e) {
            console.warn('[SessionTimer] Konnte Sitzung nicht verlängern.', e);
        }
    }

    showWarning() {
        this.isWarningVisible = true;
        this.modalElement.style.display = 'flex';
    }

    async forceLogout() {
        clearInterval(this.intervalId);
        this.modalElement.style.display = 'none';

        const endpoint = this.isFrontend ? 'frontend_logout' : 'admin_logout';
        // Wir warten den API Aufruf ab, ignorieren aber bewusst evtl. Fehler (z.B. wenn schon abgelaufen)
        await this.api.post(endpoint);

        if (this.isFrontend) {
            window.location.reload();
        } else {
            window.location.href = `${this.api.baseUrl}/admin/login`;
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
