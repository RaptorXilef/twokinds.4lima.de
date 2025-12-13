/**
 * Kümmert sich um die clientseitige Logik für die Session-Timeout-Warnung und den sichtbaren Countdown.
 * Sendet Keep-Alive-Signale an den Server und verarbeitet Logout-Antworten (401).
 *
 * @file      ROOT/public/assets/js/session_timeout.min.js
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-Share-Alike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 *
 * @since     3.0.0 - 4.0.0
 * - Pfadanpassung der keep_alive.php
 * @since 5.0.0
 * - Integration der 401-Redirect-Logik für abgelaufene Sessions.
 * - Wiederherstellung des visuellen Countdowns und der Activity-Handler.
 * - Konfiguration auf "Zeit vor Ablauf" umgestellt (statt "Zeit nach Start").
 */

document.addEventListener('DOMContentLoaded', () => {
    // Zeit in Sekunden (Muss mit PHP SESSION_TIMEOUT_SECONDS übereinstimmen)
    const sessionTimeoutInSeconds = 600; // 10 Minuten

    // NEU: Warnung X Sekunden BEVOR die Session abläuft (z.B. 60 Sekunden Restzeit)
    const warningBeforeTimeoutInSeconds = 60;

    let warningTimer;
    let logoutTimer;
    let countdownInterval; // Timer im Modal
    let displayCountdownInterval; // Sichtbarer Timer im Header (optional)
    let isPinging = false;

    const modal = document.getElementById('sessionTimeoutModal');
    const countdownElement = document.getElementById('sessionTimeoutCountdown');
    const stayLoggedInBtn = document.getElementById('stayLoggedInBtn');
    const logoutBtn = document.getElementById('logoutBtn');
    const timerDisplayCountdown = document.getElementById(
        'session-timer-countdown'
    );

    // CSRF Token holen (global oder aus Meta)
    const getCsrfToken = () => {
        if (typeof window.csrfToken !== 'undefined' && window.csrfToken !== '')
            return window.csrfToken;
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    };

    const csrfToken = getCsrfToken();

    function startTimers() {
        clearTimeout(warningTimer);
        clearTimeout(logoutTimer);
        clearInterval(countdownInterval);
        clearInterval(displayCountdownInterval);

        // Berechne den Zeitpunkt für die Warnung: Gesamtlaufzeit MINUS Vorwarnzeit
        // Beispiel: 600 - 60 = 540 Sekunden nach Start
        const timeUntilWarning =
            (sessionTimeoutInSeconds - warningBeforeTimeoutInSeconds) * 1000;

        // Timer für das Warn-Modal
        warningTimer = setTimeout(showWarningModal, timeUntilWarning);

        // Harter Logout Timer (Client-seitig als Fallback)
        logoutTimer = setTimeout(forceLogout, sessionTimeoutInSeconds * 1000);

        // Sichtbaren Countdown starten (falls Element vorhanden)
        if (timerDisplayCountdown) {
            startDisplayCountdown();
        }
    }

    function startDisplayCountdown() {
        let remainingSeconds = sessionTimeoutInSeconds;

        const updateDisplay = () => {
            if (remainingSeconds < 0) {
                clearInterval(displayCountdownInterval);
                return;
            }
            const minutes = Math.floor(remainingSeconds / 60)
                .toString()
                .padStart(2, '0');
            const seconds = (remainingSeconds % 60).toString().padStart(2, '0');

            if (timerDisplayCountdown) {
                timerDisplayCountdown.textContent = `${minutes}:${seconds}`;
            }
            remainingSeconds--;
        };

        updateDisplay(); // Sofort einmal ausführen
        displayCountdownInterval = setInterval(updateDisplay, 1000);
    }

    function showWarningModal() {
        if (modal) modal.style.display = 'flex';

        // Der Countdown beginnt jetzt mit der konfigurierten Vorwarnzeit (z.B. 60)
        let secondsLeft = warningBeforeTimeoutInSeconds;
        if (countdownElement) countdownElement.textContent = secondsLeft;

        countdownInterval = setInterval(() => {
            secondsLeft--;
            if (countdownElement) countdownElement.textContent = secondsLeft;

            if (secondsLeft <= 0) {
                clearInterval(countdownInterval);
                forceLogout();
            }
        }, 1000);
    }

    function hideWarningModal() {
        if (modal) modal.style.display = 'none';
        clearInterval(countdownInterval);
    }

    /**
     * Ping den Server, um die PHP-Session am Leben zu erhalten.
     * Prüft auf 401 (Session abgelaufen) und leitet ggf. um.
     */
    function resetTimersAndPingServer() {
        if (isPinging) return;
        isPinging = true;

        const formData = new FormData();
        if (csrfToken) formData.append('csrf_token', csrfToken);

        // URL ermitteln
        const keepAliveUrl =
            typeof window.keepAliveUrl !== 'undefined' && window.keepAliveUrl
                ? window.keepAliveUrl
                : 'keep_alive.php';

        fetch(keepAliveUrl, {
            method: 'POST',
            body: formData,
        })
            .then((response) => {
                // Logik für abgelaufene Session (401)
                if (response.status === 401) {
                    return response
                        .json()
                        .then((data) => {
                            if (data.redirect) {
                                window.location.href = data.redirect;
                            } else {
                                window.location.href =
                                    'index.php?reason=session_expired';
                            }
                        })
                        .catch(() => {
                            // Fallback falls JSON kaputt
                            window.location.href =
                                'index.php?reason=session_expired';
                        });
                }

                if (!response.ok) {
                    console.warn('Keep-Alive Ping nicht OK:', response.status);
                } else {
                    // Alles gut -> Timer neu starten
                    startTimers();
                }
            })
            .catch((err) => {
                console.error('Keep-Alive Fehler:', err);
            })
            .finally(() => {
                // Drosselung
                setTimeout(() => {
                    isPinging = false;
                }, 5000);
            });
    }

    function forceLogout() {
        let url = '?action=logout';
        if (csrfToken) url += `&token=${csrfToken}`;
        window.location.href = url;
    }

    if (stayLoggedInBtn) {
        stayLoggedInBtn.addEventListener('click', () => {
            hideWarningModal();
            resetTimersAndPingServer();
        });
    }

    if (logoutBtn) {
        logoutBtn.addEventListener('click', forceLogout);
    }

    // Activity Listener (Debounced)
    let activityTimeout;
    const activityHandler = () => {
        // Wenn gerade gepingt wird, nichts tun (Drosselung passiert in resetTimersAndPingServer)
        clearTimeout(activityTimeout);
        activityTimeout = setTimeout(() => {
            // Bei Aktivität Timer zurücksetzen und Server pingen
            resetTimersAndPingServer();
        }, 500); // 500ms Debounce
    };

    // Events für Aktivität
    window.addEventListener('mousemove', activityHandler, { passive: true });
    window.addEventListener('keydown', activityHandler, { passive: true });
    window.addEventListener('click', activityHandler, { passive: true });
    window.addEventListener('scroll', activityHandler, { passive: true });

    // Initialer Start
    startTimers();
});
