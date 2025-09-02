/**
 * Handles the client-side logic for the session timeout warning and visible countdown.
 */
document.addEventListener("DOMContentLoaded", () => {
  const sessionTimeoutInSeconds = 600; // 10 minutes (must match PHP)
  const warningTimeInSeconds = 540; // 9 minutes (1 minute before timeout)

  let warningTimer;
  let logoutTimer;
  let countdownInterval;
  let displayCountdownInterval; // NEU: Separater Intervall für die Anzeige

  const modal = document.getElementById("sessionTimeoutModal");
  const countdownElement = document.getElementById("sessionTimeoutCountdown");
  const stayLoggedInBtn = document.getElementById("stayLoggedInBtn");
  const logoutBtn = document.getElementById("logoutBtn");

  // NEU: Elemente für die permanente Timer-Anzeige
  const timerDisplayContainer = document.getElementById(
    "session-timer-display"
  );
  const timerDisplayCountdown = document.getElementById(
    "session-timer-countdown"
  );

  if (!timerDisplayContainer || !timerDisplayCountdown) {
    console.warn(
      "Session timer display elements not found. Visible countdown disabled."
    );
    // Don't return, as the modal might still work.
  }
  if (!modal || !countdownElement || !stayLoggedInBtn || !logoutBtn) {
    console.warn(
      "Session timeout modal elements not found. Timeout feature disabled."
    );
    return;
  }

  function startTimers() {
    clearTimeout(warningTimer);
    clearTimeout(logoutTimer);
    clearInterval(countdownInterval);
    clearInterval(displayCountdownInterval); // NEU

    warningTimer = setTimeout(showWarningModal, warningTimeInSeconds * 1000);
    logoutTimer = setTimeout(forceLogout, sessionTimeoutInSeconds * 1000);

    // NEU: Starte den sichtbaren Countdown
    startDisplayCountdown();
  }

  function resetTimers() {
    startTimers();
  }

  // NEU: Funktion zur Aktualisierung des sichtbaren Countdowns
  function startDisplayCountdown() {
    let remainingSeconds = sessionTimeoutInSeconds;

    const updateDisplay = () => {
      const minutes = Math.floor(remainingSeconds / 60)
        .toString()
        .padStart(2, "0");
      const seconds = (remainingSeconds % 60).toString().padStart(2, "0");
      if (timerDisplayCountdown) {
        timerDisplayCountdown.textContent = `${minutes}:${seconds}`;
      }
      remainingSeconds--;

      if (remainingSeconds < 0) {
        clearInterval(displayCountdownInterval);
      }
    };

    updateDisplay(); // Sofort aktualisieren
    displayCountdownInterval = setInterval(updateDisplay, 1000);
  }

  function showWarningModal() {
    modal.style.display = "flex";
    let countdown = sessionTimeoutInSeconds - warningTimeInSeconds;
    countdownElement.textContent = countdown;

    countdownInterval = setInterval(() => {
      countdown--;
      countdownElement.textContent = countdown;
      if (countdown <= 0) {
        clearInterval(countdownInterval);
      }
    }, 1000);
  }

  function hideWarningModal() {
    modal.style.display = "none";
    clearInterval(countdownInterval);
  }

  function forceLogout() {
    window.location.href = "index.php?action=logout";
  }

  stayLoggedInBtn.addEventListener("click", () => {
    hideWarningModal();
    resetTimers();
    fetch("keep_alive.php").catch((err) =>
      console.warn("Keep-alive ping failed.", err)
    );
  });

  logoutBtn.addEventListener("click", forceLogout);

  let activityTimeout;
  const activityHandler = () => {
    clearTimeout(activityTimeout);
    activityTimeout = setTimeout(resetTimers, 300);
  };

  window.addEventListener("mousemove", activityHandler, { passive: true });
  window.addEventListener("keydown", activityHandler, { passive: true });
  window.addEventListener("click", activityHandler, { passive: true });

  startTimers();
});
