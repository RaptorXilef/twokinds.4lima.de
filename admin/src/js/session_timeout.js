/**
 * Kümmert sich um die clientseitige Logik für die Session-Timeout-Warnung und den sichtbaren Countdown.
 * V3 - Behebt den Fehler, bei dem clientseitige Aktivität die Server-Session nicht aktualisiert hat.
 */
document.addEventListener("DOMContentLoaded", () => {
  const sessionTimeoutInSeconds = 600; // 10 Minuten (muss mit PHP übereinstimmen)
  const warningTimeInSeconds = 540; // 9 Minuten (1 Minute vor dem Timeout)

  let warningTimer;
  let logoutTimer;
  let countdownInterval;
  let displayCountdownInterval;

  // Ein Flag, um zu verhindern, dass zu viele Pings an den Server gesendet werden
  let isPinging = false;

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
      "Elemente für die Session-Timer-Anzeige nicht gefunden. Sichtbarer Countdown deaktiviert."
    );
  }
  if (!modal || !countdownElement || !stayLoggedInBtn || !logoutBtn) {
    console.warn(
      "Elemente für das Session-Timeout-Modal nicht gefunden. Timeout-Funktion deaktiviert."
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

  /**
   * BUGFIX: Diese Funktion pingt nun den Server an, um die PHP-Sitzung am Leben zu erhalten.
   * Sie enthält einen Drosselungsmechanismus, um zu viele Anfragen zu vermeiden.
   */
  function resetTimersAndPingServer() {
    // Wenn wir uns bereits mitten in einer Ping-Anfrage befinden, nichts tun.
    if (isPinging) {
      return;
    }

    // Setze ein Flag, um anzuzeigen, dass ein Ping im Gange ist.
    isPinging = true;

    // Pingt den Server an, um den Session-Zeitstempel zu aktualisieren.
    // KORREKTUR: Der Pfad muss relativ zum aktuellen Verzeichnis sein.
    fetch("src/components/keep_alive.php")
      .then((response) => {
        if (response.ok) {
          // Wenn der Ping erfolgreich war, setze die clientseitigen Timer zurück.
          startTimers();
        } else {
          console.warn(
            "Keep-alive-Ping war nicht erfolgreich. Server-Session könnte ablaufen."
          );
        }
      })
      .catch((err) => console.warn("Keep-alive-Ping fehlgeschlagen.", err))
      .finally(() => {
        // Nachdem der Ping abgeschlossen ist (erfolgreich oder nicht), erlaube nach einer kurzen Verzögerung einen neuen Ping.
        // Dies verhindert aufeinanderfolgende Pings bei schneller Aktivität.
        setTimeout(() => {
          isPinging = false;
        }, 5000); // Erlaube einen neuen Ping höchstens alle 5 Sekunden.
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
    resetTimersAndPingServer(); // Benutze die korrigierte Funktion
  });

  logoutBtn.addEventListener("click", forceLogout);

  // Diese Logik drosselt den Aktivitäts-Handler. Sie stellt sicher, dass resetTimersAndPingServer
  // nur einmal aufgerufen wird, nachdem eine Welle von Aktivitäten beendet ist (Debouncing).
  let activityTimeout;
  const activityHandler = () => {
    clearTimeout(activityTimeout);
    activityTimeout = setTimeout(resetTimersAndPingServer, 500); // 500ms Verzögerung
  };

  window.addEventListener("mousemove", activityHandler, { passive: true });
  window.addEventListener("keydown", activityHandler, { passive: true });
  window.addEventListener("click", activityHandler, { passive: true });
  window.addEventListener("scroll", activityHandler, { passive: true });

  startTimers();
});
