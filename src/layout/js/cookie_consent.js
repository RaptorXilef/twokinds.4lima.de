/**
 * Handles cookie consent logic, displays a banner, and conditionally loads Google Analytics.
 */
// Google Analytics Mess-ID
const GA_MEASUREMENT_ID = "G-7VE3ZEWZQ7";

// Namen der Cookie-Kategorien
const COOKIE_CATEGORIES = {
  NECESSARY: "necessary", // Notwendige Cookies
  ANALYTICS: "analytics", // Analyse-Cookies (Google Analytics)
};

// Schlüssel für Local Storage
const CONSENT_STORAGE_KEY = "cookie_consent";

// Exponierte Funktion, um den Banner von außen aufrufen zu können (z.B. von der Datenschutzerklärung)
window.showCookieBanner = showCookieBanner;

// Globale Flag, um zu verfolgen, ob Google Analytics auf diesem Seitenaufruf bereits konfiguriert wurde.
// Dies ist entscheidend, um den "expires" Fehler zu vermeiden.
window.gaConfigured = false; // Initialisiere als false

document.addEventListener("DOMContentLoaded", () => {
  console.log("DEBUG: DOMContentLoaded fired in cookie_consent.js."); // DEBUG
  // Initialen Zustand des Banners überprüfen und ggf. anzeigen
  checkConsentAndDisplayBanner();

  // Event Listener für Buttons im Banner
  const acceptAllBtn = document.getElementById("acceptAllCookies");
  const rejectAllBtn = document.getElementById("rejectAllCookies");
  const savePreferencesBtn = document.getElementById("saveCookiePreferences");

  if (acceptAllBtn) {
    acceptAllBtn.addEventListener("click", () => {
      console.log("DEBUG: 'Alle akzeptieren' Button geklickt."); // DEBUG
      setConsent({
        [COOKIE_CATEGORIES.NECESSARY]: true,
        [COOKIE_CATEGORIES.ANALYTICS]: true,
      });
      hideCookieBanner();
    });
  }

  if (rejectAllBtn) {
    rejectAllBtn.addEventListener("click", () => {
      console.log("DEBUG: 'Alle ablehnen' Button geklickt."); // DEBUG
      setConsent({
        [COOKIE_CATEGORIES.NECESSARY]: true, // Notwendige immer akzeptieren
        [COOKIE_CATEGORIES.ANALYTICS]: false,
      });
      hideCookieBanner();
    });
  }

  if (savePreferencesBtn) {
    savePreferencesBtn.addEventListener("click", () => {
      console.log("DEBUG: 'Einstellungen speichern' Button geklickt."); // DEBUG
      const analyticsCheckbox = document.getElementById("cookieAnalytics");
      setConsent({
        [COOKIE_CATEGORIES.NECESSARY]: true,
        [COOKIE_CATEGORIES.ANALYTICS]: analyticsCheckbox
          ? analyticsCheckbox.checked
          : false,
      });
      hideCookieBanner();
    });
  }

  // === Logik für einklappbare Details im Cookie-Banner ===
  document.querySelectorAll(".toggle-details").forEach((toggle) => {
    toggle.addEventListener("click", () => {
      console.log("DEBUG: Toggle-Details geklickt."); // DEBUG
      const targetId = toggle.dataset.target;
      const content = document.getElementById(targetId);
      const icon = toggle.querySelector(".toggle-icon");

      if (content.style.display === "block" || content.style.display === "") {
        content.style.display = "none";
        if (icon) icon.classList.replace("fa-chevron-down", "fa-chevron-right");
      } else {
        content.style.display = "block";
        if (icon) icon.classList.replace("fa-chevron-right", "fa-chevron-down");
      }
    });
  });
  // === ENDE Einklapp-Logik ===

  // Überwache Theme-Wechsel, um den Banner-Stil anzupassen (falls der Banner sichtbar ist)
  const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      if (mutation.attributeName === "class") {
        const body = document.body;
        const banner = document.getElementById("cookieConsentBanner");
        if (banner && banner.style.display !== "none") {
          // Hier könnte man explizit Klassen togglen, wenn cookie_banner_dark.css nicht ausreicht
          // Aber da wir CSS-Regeln verwenden, die auf body.theme-night basieren,
          // ist hier keine direkte JS-Aktion notwendig, solange der Banner sichtbar ist.
        }
      }
    });
  });

  // Beobachte Änderungen der 'class'-Attribute am Body-Element
  if (document.body) {
    observer.observe(document.body, { attributes: true });
  } else {
    console.error("DEBUG: document.body nicht verfügbar für MutationObserver."); // DEBUG
  }
});

/**
 * Überprüft den gespeicherten Consent-Status und zeigt den Banner bei Bedarf an.
 */
function checkConsentAndDisplayBanner() {
  console.log("DEBUG: checkConsentAndDisplayBanner() aufgerufen."); // DEBUG
  const consent = getConsent();
  if (consent === null) {
    console.log("DEBUG: Kein Consent gefunden, zeige Cookie-Banner an."); // DEBUG
    // Banner anzeigen, wenn keine Entscheidung getroffen wurde
    showCookieBanner();
  } else {
    console.log("DEBUG: Consent gefunden:", consent); // DEBUG
    // Google Analytics laden, wenn Analytics-Consent gegeben wurde
    if (consent[COOKIE_CATEGORIES.ANALYTICS]) {
      console.log(
        "DEBUG: Analytics-Consent ist TRUE, versuche Google Analytics zu laden."
      ); // DEBUG
      loadGoogleAnalytics();
    } else {
      console.log(
        "DEBUG: Analytics-Consent ist FALSE, deaktiviere Google Analytics."
      ); // DEBUG
      disableGoogleAnalytics();
    }
    // Banner ausblenden, falls noch sichtbar (sollte es nicht sein, aber zur Sicherheit)
    hideCookieBanner();
  }
}

/**
 * Zeigt den Cookie-Banner an und setzt die Checkboxen entsprechend der gespeicherten Präferenzen.
 */
function showCookieBanner() {
  console.log("DEBUG: showCookieBanner() aufgerufen."); // DEBUG
  const banner = document.getElementById("cookieConsentBanner");
  if (banner) {
    banner.style.display = "block";
    // Setze die Checkboxen basierend auf dem aktuellen Consent (falls vorhanden)
    const consent = getConsent();
    const analyticsCheckbox = document.getElementById("cookieAnalytics");

    if (analyticsCheckbox) {
      // Notwendige Cookies sind immer aktiv und nicht änderbar
      document.getElementById("cookieNecessary").checked = true;
      document.getElementById("cookieNecessary").disabled = true;

      // Analytics-Cookies sind standardmäßig vorausgewählt, wenn keine Präferenz gespeichert ist
      // oder entsprechend der gespeicherten Präferenz
      analyticsCheckbox.checked = consent
        ? consent[COOKIE_CATEGORIES.ANALYTICS]
        : true;
      console.log(
        "DEBUG: Cookie-Banner Checkboxen gesetzt. Analytics-Checkbox:",
        analyticsCheckbox.checked
      ); // DEBUG
    }
  }
}

/**
 * Blendet den Cookie-Banner aus.
 */
function hideCookieBanner() {
  console.log("DEBUG: hideCookieBanner() aufgerufen."); // DEBUG
  const banner = document.getElementById("cookieConsentBanner");
  if (banner) {
    banner.style.display = "none";
    console.log("DEBUG: Cookie-Banner ausgeblendet."); // DEBUG
  }
}

/**
 * Speichert die Consent-Präferenzen im Local Storage.
 * @param {object} preferences Ein Objekt mit den Cookie-Kategorien und ihrem Consent-Status.
 */
function setConsent(preferences) {
  console.log("DEBUG: setConsent() aufgerufen mit Präferenzen:", preferences); // DEBUG
  try {
    localStorage.setItem(CONSENT_STORAGE_KEY, JSON.stringify(preferences));
    console.log("DEBUG: Consent im Local Storage gespeichert."); // DEBUG
    // Lade oder entlade Google Analytics basierend auf der neuen Präferenz
    if (preferences[COOKIE_CATEGORIES.ANALYTICS]) {
      console.log(
        "DEBUG: Analytics-Consent ist TRUE nach setConsent, versuche Google Analytics zu laden."
      ); // DEBUG
      loadGoogleAnalytics();
    } else {
      console.log(
        "DEBUG: Analytics-Consent ist FALSE nach setConsent, deaktiviere Google Analytics."
      ); // DEBUG
      disableGoogleAnalytics();
    }
  } catch (e) {
    console.error(
      "DEBUG: Fehler beim Speichern des Cookie-Consents im Local Storage:",
      e
    );
  }
}

/**
 * Ruft die gespeicherten Consent-Präferenzen aus dem Local Storage ab.
 * @returns {object|null} Die Präferenzen oder null, wenn keine gespeichert sind.
 */
function getConsent() {
  console.log("DEBUG: getConsent() aufgerufen."); // DEBUG
  try {
    const stored = localStorage.getItem(CONSENT_STORAGE_KEY);
    const consentData = stored ? JSON.parse(stored) : null;
    console.log("DEBUG: Consent aus Local Storage abgerufen:", consentData); // DEBUG
    return consentData;
  } catch (e) {
    console.error(
      "DEBUG: Fehler beim Abrufen des Cookie-Consents aus dem Local Storage:",
      e
    );
    return null;
  }
}

/**
 * Lädt das Google Analytics (gtag.js) Skript und konfiguriert es.
 * Stellt sicher, dass die Konfiguration nur einmal pro Seitenaufruf erfolgt.
 */
function loadGoogleAnalytics() {
  console.log(
    "DEBUG: loadGoogleAnalytics() aufgerufen. window.gaConfigured (vor Prüfung):",
    window.gaConfigured
  ); // DEBUG

  // Standard-gtag.js-Snippet-Initialisierung, falls noch nicht vorhanden
  if (!window.dataLayer) {
    window.dataLayer = [];
    console.log("DEBUG: window.dataLayer initialisiert."); // DEBUG
  }
  if (typeof window.gtag !== "function") {
    window.gtag = function () {
      window.dataLayer.push(arguments);
    };
    console.log("DEBUG: Temporäre window.gtag Funktion initialisiert."); // DEBUG
  }

  // Überprüfe, ob das gtag.js Skript bereits im DOM vorhanden ist.
  let gaScript = document.querySelector(
    `script[src*="gtag/js?id=${GA_MEASUREMENT_ID}"]`
  );
  console.log("DEBUG: GA Skript im DOM gefunden:", !!gaScript); // DEBUG

  if (!gaScript) {
    // Wenn Skript nicht gefunden, erstelle und füge es hinzu
    gaScript = document.createElement("script");
    gaScript.async = true;
    gaScript.src = `https://www.googletagmanager.com/gtag/js?id=${GA_MEASUREMENT_ID}`;
    document.head.appendChild(gaScript);
    console.log("DEBUG: Google Analytics Skript wird dem DOM hinzugefügt."); // DEBUG

    // Der onload-Handler wird nur für das neu hinzugefügte Skript benötigt.
    gaScript.onload = () => {
      console.log(
        "DEBUG: Google Analytics Skript ONLOAD Event gefeuert. (Nach Skript-Append)"
      ); // DEBUG
      if (!window.gaConfigured) {
        console.log("DEBUG: Rufe gtag('js', new Date()) auf (via onload)."); // DEBUG
        window.gtag("js", new Date()); // Initialisiert gtag.js
        console.log(
          "DEBUG: Rufe gtag('config', GA_MEASUREMENT_ID) auf (via onload). gaConfigured (vor Setzung):",
          window.gaConfigured
        ); // DEBUG
        window.gtag("config", GA_MEASUREMENT_ID); // Konfiguriert die Mess-ID
        window.gaConfigured = true; // Setze die Flag auf true
        console.log(
          "DEBUG: Google Analytics geladen und konfiguriert (via onload). gaConfigured jetzt:",
          window.gaConfigured
        ); // DEBUG
      } else {
        console.log(
          "DEBUG: Google Analytics Skript geladen, aber bereits konfiguriert (redundanter onload-Aufruf). gaConfigured Status:",
          window.gaConfigured
        ); // DEBUG
      }
    };

    gaScript.onerror = (e) => {
      console.error(
        "DEBUG: Fehler beim Laden des Google Analytics Skripts:",
        e
      ); // DEBUG
    };
  } else {
    // Das Skript ist bereits im DOM. Versuche zu konfigurieren, falls noch nicht geschehen.
    console.log(
      "DEBUG: GA Skript ist bereits im DOM. Prüfe Konfigurationsstatus."
    ); // DEBUG
    if (!window.gaConfigured) {
      console.log(
        "DEBUG: GA noch nicht konfiguriert. Rufe gtag('js', new Date()) auf (direkt)."
      ); // DEBUG
      window.gtag("js", new Date());
      console.log(
        "DEBUG: Rufe gtag('config', GA_MEASUREMENT_ID) auf (direkt). gaConfigured (vor Setzung):",
        window.gaConfigured
      ); // DEBUG
      window.gtag("config", GA_MEASUREMENT_ID);
      window.gaConfigured = true;
      console.log(
        "DEBUG: Google Analytics Skript war bereits im DOM, jetzt konfiguriert (direkt). gaConfigured jetzt:",
        window.gaConfigured
      ); // DEBUG
    } else {
      console.log(
        "DEBUG: Google Analytics Skript ist bereits im DOM und bereits konfiguriert (Flag ist TRUE)."
      ); // DEBUG
      // Füge hier einen Trace hinzu, um zu sehen, woher der redundante Aufruf kommt.
      console.trace(
        "WARN: gtag('config') wurde aufgerufen, obwohl gaConfigured bereits TRUE ist. Mögliche externe Initialisierung oder Timing-Problem."
      ); // DEBUG
    }
  }
}

/**
 * Deaktiviert Google Analytics und entfernt das Skript aus dem DOM.
 */
function disableGoogleAnalytics() {
  console.log(
    "DEBUG: disableGoogleAnalytics() aufgerufen. window.gaConfigured:",
    window.gaConfigured
  ); // DEBUG
  // Wenn GA konfiguriert war, versuchen wir es zu deaktivieren.
  if (window.gaConfigured) {
    if (typeof window.gtag === "function") {
      console.log("DEBUG: Deaktiviere GA-Seitenansichten und anonymisiere IP."); // DEBUG
      window.gtag("config", GA_MEASUREMENT_ID, { send_page_view: false }); // Deaktiviere Seitenansichten
      window.gtag("set", "anonymize_ip", true); // Anonymisiere IP-Adressen (falls nicht schon geschehen)
      console.log("DEBUG: Google Analytics deaktiviert."); // DEBUG
    }
    window.gaConfigured = false; // Setze die Konfigurations-Flag zurück
  }

  // Entferne das gtag.js Skript aus dem DOM, falls es vorhanden ist.
  const gaScript = document.querySelector(
    `script[src*="gtag/js?id=${GA_MEASUREMENT_ID}"]`
  );
  if (gaScript) {
    gaScript.remove();
    console.log("DEBUG: Google Analytics Skript aus DOM entfernt."); // DEBUG
  }

  // Setze den dataLayer zurück, um eine saubere Neuinitialisierung zu ermöglichen.
  if (window.dataLayer) {
    console.log("DEBUG: dataLayer bleibt bestehen, da Skript entfernt wurde."); // DEBUG
  }
}
