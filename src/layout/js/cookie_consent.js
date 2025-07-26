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

document.addEventListener("DOMContentLoaded", () => {
  // Initialen Zustand des Banners überprüfen und ggf. anzeigen
  checkConsentAndDisplayBanner();

  // Event Listener für Buttons im Banner
  const acceptAllBtn = document.getElementById("acceptAllCookies");
  const rejectAllBtn = document.getElementById("rejectAllCookies");
  const savePreferencesBtn = document.getElementById("saveCookiePreferences");

  if (acceptAllBtn) {
    acceptAllBtn.addEventListener("click", () => {
      setConsent({
        [COOKIE_CATEGORIES.NECESSARY]: true,
        [COOKIE_CATEGORIES.ANALYTICS]: true,
      });
      hideCookieBanner();
    });
  }

  if (rejectAllBtn) {
    rejectAllBtn.addEventListener("click", () => {
      setConsent({
        [COOKIE_CATEGORIES.NECESSARY]: true, // Notwendige immer akzeptieren
        [COOKIE_CATEGORIES.ANALYTICS]: false,
      });
      hideCookieBanner();
    });
  }

  if (savePreferencesBtn) {
    savePreferencesBtn.addEventListener("click", () => {
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
});

/**
 * Überprüft den gespeicherten Consent-Status und zeigt den Banner bei Bedarf an.
 */
function checkConsentAndDisplayBanner() {
  const consent = getConsent();
  if (consent === null) {
    // Banner anzeigen, wenn keine Entscheidung getroffen wurde
    showCookieBanner();
  } else {
    // Google Analytics laden, wenn Analytics-Consent gegeben wurde
    if (consent[COOKIE_CATEGORIES.ANALYTICS]) {
      loadGoogleAnalytics();
    }
    // Banner ausblenden, falls noch sichtbar (sollte es nicht sein, aber zur Sicherheit)
    hideCookieBanner();
  }
}

/**
 * Zeigt den Cookie-Banner an und setzt die Checkboxen entsprechend der gespeicherten Präferenzen.
 */
function showCookieBanner() {
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
    }
  }
}

/**
 * Blendet den Cookie-Banner aus.
 */
function hideCookieBanner() {
  const banner = document.getElementById("cookieConsentBanner");
  if (banner) {
    banner.style.display = "none";
  }
}

/**
 * Speichert die Consent-Präferenzen im Local Storage.
 * @param {object} preferences Ein Objekt mit den Cookie-Kategorien und ihrem Consent-Status.
 */
function setConsent(preferences) {
  try {
    localStorage.setItem(CONSENT_STORAGE_KEY, JSON.stringify(preferences));
    // Lade oder entlade Google Analytics basierend auf der neuen Präferenz
    if (preferences[COOKIE_CATEGORIES.ANALYTICS]) {
      loadGoogleAnalytics();
    } else {
      // Optional: Google Analytics deaktivieren/entladen, falls zuvor geladen
      // Dies ist komplexer und erfordert ggf. das Zurücksetzen von GA-Variablen
      // Für den Anfang reicht es, es nicht zu laden, wenn kein Consent da ist.
      disableGoogleAnalytics();
    }
  } catch (e) {
    console.error(
      "Fehler beim Speichern des Cookie-Consents im Local Storage:",
      e
    );
  }
}

/**
 * Ruft die gespeicherten Consent-Präferenzen aus dem Local Storage ab.
 * @returns {object|null} Die Präferenzen oder null, wenn keine gespeichert sind.
 */
function getConsent() {
  try {
    const stored = localStorage.getItem(CONSENT_STORAGE_KEY);
    return stored ? JSON.parse(stored) : null;
  } catch (e) {
    console.error(
      "Fehler beim Abrufen des Cookie-Consents aus dem Local Storage:",
      e
    );
    return null;
  }
}

/**
 * Lädt das Google Analytics (gtag.js) Skript.
 */
function loadGoogleAnalytics() {
  // Verhindere doppeltes Laden
  if (window.gaTrackingLoaded) {
    return;
  }

  // Füge gtag.js Skript hinzu
  const script = document.createElement("script");
  script.async = true;
  script.src = `https://www.googletagmanager.com/gtag/js?id=${GA_MEASUREMENT_ID}`;
  document.head.appendChild(script);

  script.onload = () => {
    window.dataLayer = window.dataLayer || [];
    function gtag() {
      dataLayer.push(arguments);
    }
    gtag("js", new Date());
    gtag("config", GA_MEASUREMENT_ID);
    window.gaTrackingLoaded = true; // Markiere, dass GA geladen wurde
    console.log("Google Analytics geladen und konfiguriert.");
  };

  script.onerror = (e) => {
    console.error("Fehler beim Laden des Google Analytics Skripts:", e);
  };
}

/**
 * Deaktiviert Google Analytics (setzt die Tracking-Variablen zurück).
 * Dies ist eine rudimentäre Deaktivierung. Für eine vollständige Deaktivierung
 * müssten ggf. weitere GA-spezifische Cookies gelöscht werden.
 */
function disableGoogleAnalytics() {
  if (window.gaTrackingLoaded) {
    // Setze die Tracking-ID auf eine leere Zeichenkette oder null,
    // um weitere Events zu verhindern.
    if (typeof window.gtag === "function") {
      window.gtag("config", GA_MEASUREMENT_ID, { send_page_view: false }); // Deaktiviere Seitenansichten
      window.gtag("set", "anonymize_ip", true); // Anonymisiere IP-Adressen (falls nicht schon geschehen)
      console.log("Google Analytics deaktiviert.");
    }
    window.gaTrackingLoaded = false;
    // Optional: Entferne das gtag.js Skript aus dem DOM, falls gewünscht
    const gaScript = document.querySelector(
      `script[src*="gtag/js?id=${GA_MEASUREMENT_ID}"]`
    );
    if (gaScript) {
      gaScript.remove();
    }
  }
}

// Überwache Theme-Wechsel, um den Banner-Stil anzupassen (falls der Banner sichtbar ist)
// Dies ist ein Beispiel, wie man auf Änderungen der body-Klasse reagieren könnte.
// Die CSS-Regeln in cookie_banner_dark.css erledigen den Hauptteil.
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
observer.observe(document.body, { attributes: true });
