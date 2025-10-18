/**
 * Kümmert sich um die clientseitige Logik für die Session-Timeout-Warnung und den sichtbaren Countdown.
 * V4 - Integriert CSRF-Token in AJAX-Anfragen und Logout-Link.
 *
 * @file      ROOT/public/assets/js/session_timeout.min.js
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-Share-Alike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   1.0.1
 * @since     1.0.1 Pfadanpassung der keep_alive.php
 */
document.addEventListener("DOMContentLoaded", () => {
  const sessionTimeoutInSeconds = 600; // 10 Minuten (muss mit PHP übereinstimmen)
  const warningTimeInSeconds = 540; // 9 Minuten (1 Minute vor dem Timeout)

  let warningTimer;
  let logoutTimer;
  let countdownInterval;
  let displayCountdownInterval;
  let isPinging = false;

  const modal = document.getElementById("sessionTimeoutModal");
  const countdownElement = document.getElementById("sessionTimeoutCountdown");
  const stayLoggedInBtn = document.getElementById("stayLoggedInBtn");
  const logoutBtn = document.getElementById("logoutBtn");
  const timerDisplayCountdown = document.getElementById(
    "session-timer-countdown"
  );

  if (!modal || !countdownElement || !stayLoggedInBtn || !logoutBtn) {
    console.warn(
      "Elemente für das Session-Timeout-Modal nicht gefunden. Timeout-Funktion deaktiviert."
    );
    return;
  }

  if (typeof window.csrfToken === "undefined" || window.csrfToken === "") {
    console.error(
      "CSRF-Token nicht gefunden. Die Session-Verlängerung wird fehlschlagen."
    );
    return;
  }

  function startTimers() {
    clearTimeout(warningTimer);
    clearTimeout(logoutTimer);
    clearInterval(countdownInterval);
    clearInterval(displayCountdownInterval);

    warningTimer = setTimeout(showWarningModal, warningTimeInSeconds * 1000);
    logoutTimer = setTimeout(forceLogout, sessionTimeoutInSeconds * 1000);

    if (timerDisplayCountdown) startDisplayCountdown();
  }

  /**
   * Ping den Server, um die PHP-Session am Leben zu erhalten, und sendet den CSRF-Token mit.
   */
  function resetTimersAndPingServer() {
    if (isPinging) return;
    isPinging = true;

    // Sende den CSRF-Token als POST-Daten.
    const formData = new FormData();
    formData.append("csrf_token", window.csrfToken);

    if (typeof window.keepAliveUrl === "undefined" || !window.keepAliveUrl) {
      console.error(
        "Die keep_alive.php URL wurde nicht von PHP bereitgestellt."
      );
      isPinging = false;
      return;
    }

    fetch(window.keepAliveUrl, {
      method: "POST",
      body: formData,
    })
      .then((response) => {
        if (response.ok) {
          startTimers();
        } else {
          console.error(
            "Keep-alive-Ping war nicht erfolgreich. Server-Session könnte ablaufen.",
            response.status,
            response.statusText
          );
        }
      })
      .catch((err) => console.error("Keep-alive-Ping fehlgeschlagen.", err))
      .finally(() => {
        setTimeout(() => {
          isPinging = false;
        }, 5000); // Drosselung
      });
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
        .padStart(2, "0");
      const seconds = (remainingSeconds % 60).toString().padStart(2, "0");
      timerDisplayCountdown.textContent = `${minutes}:${seconds}`;
      remainingSeconds--;
    };
    updateDisplay();
    displayCountdownInterval = setInterval(updateDisplay, 1000);
  }

  function showWarningModal() {
    modal.style.display = "flex";
    let countdown = sessionTimeoutInSeconds - warningTimeInSeconds;
    countdownElement.textContent = countdown;

    countdownInterval = setInterval(() => {
      countdown--;
      countdownElement.textContent = countdown;
      if (countdown <= 0) clearInterval(countdownInterval);
    }, 1000);
  }

  function hideWarningModal() {
    modal.style.display = "none";
    clearInterval(countdownInterval);
  }

  function forceLogout() {
    // KORREKTUR: Füge den CSRF-Token zum Logout-Link hinzu.
    // Wir nehmen an, dass die init_admin.php auf allen Seiten geladen wird und den Link verarbeitet.
    window.location.href = `?action=logout&token=${window.csrfToken}`;
  }

  stayLoggedInBtn.addEventListener("click", () => {
    hideWarningModal();
    resetTimersAndPingServer();
  });

  logoutBtn.addEventListener("click", forceLogout);

  // Debouncing für Aktivitäts-Handler
  let activityTimeout;
  const activityHandler = () => {
    clearTimeout(activityTimeout);
    activityTimeout = setTimeout(resetTimersAndPingServer, 500);
  };

  window.addEventListener("mousemove", activityHandler, { passive: true });
  window.addEventListener("keydown", activityHandler, { passive: true });
  window.addEventListener("click", activityHandler, { passive: true });
  window.addEventListener("scroll", activityHandler, { passive: true });

  startTimers();
});
