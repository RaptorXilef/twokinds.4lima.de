/**
 * Handles the client-side logic for the session timeout warning and visible countdown.
 */
document.addEventListener("DOMContentLoaded", () => {
  const sessionTimeoutInSeconds = 600; // 10 minutes (must match PHP)
  const warningTimeInSeconds = 540; // 9 minutes (1 minute before timeout)

  let warningTimer;
  let logoutTimer;
  let countdownInterval;
  let displayCountdownInterval;

  const modal = document.getElementById("sessionTimeoutModal");
  const countdownElement = document.getElementById("sessionTimeoutCountdown");
  const stayLoggedInBtn = document.getElementById("stayLoggedInBtn");
  const logoutBtn = document.getElementById("logoutBtn");

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
    clearInterval(displayCountdownInterval);

    warningTimer = setTimeout(showWarningModal, warningTimeInSeconds * 1000);
    logoutTimer = setTimeout(forceLogout, sessionTimeoutInSeconds * 1000);

    startDisplayCountdown();
  }

  function resetTimers() {
    // Hide modal if it's visible when activity is detected
    if (modal.style.display === "flex") {
      hideWarningModal();
    }
    startTimers();
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
      if (timerDisplayCountdown) {
        timerDisplayCountdown.textContent = `${minutes}:${seconds}`;
      }
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

  // Throttled activity handler to prevent resetting too often
  let activityTimeout;
  const activityHandler = () => {
    clearTimeout(activityTimeout);
    activityTimeout = setTimeout(resetTimers, 500); // Reset only after 500ms of inactivity
  };

  window.addEventListener("mousemove", activityHandler, { passive: true });
  window.addEventListener("keydown", activityHandler, { passive: true });
  window.addEventListener("click", activityHandler, { passive: true });
  window.addEventListener("scroll", activityHandler, { passive: true });

  startTimers();
});
