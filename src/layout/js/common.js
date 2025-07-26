/**
 * Enables common features throughout the website
 */
// Die debugMode Variable wird von der PHP-Seite gesetzt.
// Standardwert ist false, falls die PHP-Variable nicht injiziert wird.
let debugMode =
  typeof window.phpDebugMode !== "undefined" ? window.phpDebugMode : false;

(() => {
  if (debugMode) console.log("DEBUG: common.js wird geladen.");

  var themes = [
    { id: 0, name: "Default", class: null },
    { id: 1, name: "Lights On", class: null },
    { id: 2, name: "Lights Off", class: "theme-night" },
  ];
  var systemThemeId = 0;
  var systemLightThemeId = 1;
  var systemDarkThemeId = 2;
  var currentTheme = systemThemeId;

  if (debugMode) console.log("DEBUG: Themes definiert:", themes);

  document.addEventListener("DOMContentLoaded", () => {
    if (debugMode) console.log("DEBUG: DOMContentLoaded in common.js.");

    var body = document.getElementsByTagName("body")[0];
    if (!body) {
      if (debugMode) console.error("DEBUG: Body-Element nicht gefunden.");
      return; // Frühzeitiges Beenden, wenn der Body nicht verfügbar ist
    }

    // Show elements that are hidden by default due to requiring JS.
    document.querySelectorAll(".jsdep").forEach((el) => {
      el.classList.remove("jsdep");
      if (debugMode)
        console.log("DEBUG: .jsdep-Klasse von Element entfernt:", el);
    });
    if (debugMode)
      console.log("DEBUG: JS-abhängige Elemente sichtbar gemacht.");

    const toggleLightsButton = document.querySelector("#toggle_lights");
    if (toggleLightsButton) {
      toggleLightsButton.addEventListener("click", toggleTheme);
      if (debugMode)
        console.log("DEBUG: Event-Listener für #toggle_lights hinzugefügt.");
    } else {
      if (debugMode)
        console.log("DEBUG: #toggle_lights-Button nicht gefunden.");
    }

    if (typeof window.localStorage != "undefined") {
      if (debugMode) console.log("DEBUG: Local Storage verfügbar.");
      if (typeof window.localStorage.themePref == "undefined") {
        setTheme(systemThemeId, false, false);
        if (debugMode)
          console.log(
            "DEBUG: Keine Theme-Präferenz im Local Storage gefunden, System-Theme gesetzt."
          );
      } else {
        setTheme(window.localStorage.themePref, false, false);
        if (debugMode)
          console.log(
            "DEBUG: Theme-Präferenz aus Local Storage geladen und gesetzt:",
            window.localStorage.themePref
          );
      }
    } else {
      body.classList.remove("preload");
      if (debugMode)
        console.log(
          "DEBUG: Local Storage nicht verfügbar, 'preload'-Klasse entfernt."
        );
    }

    // Theme also toggles on 'i' keypress.
    body.addEventListener("keyup", (e) => {
      if (debugMode) console.log("DEBUG: Tastendruck erkannt: " + e.which);
      if (e.which == 73) {
        // ASCII for 'I'
        toggleTheme(e);
        if (debugMode)
          console.log("DEBUG: 'I'-Taste gedrückt, Theme umgeschaltet.");
      }
    });
    if (debugMode)
      console.log(
        "DEBUG: Tastatur-Event-Listener für Theme-Umschaltung hinzugefügt."
      );

    // Watch the system theme change event and automatically change with it.
    if (window.matchMedia) {
      if (debugMode) console.log("DEBUG: window.matchMedia verfügbar.");
      window
        .matchMedia("(prefers-color-scheme: dark)")
        .addEventListener("change", () => {
          if (debugMode) console.log("DEBUG: System-Theme-Änderung erkannt.");
          // Ignore the event if the system theme is not currently selected.
          if (currentTheme == systemThemeId) {
            setTheme(systemThemeId, false, true);
            if (debugMode)
              console.log("DEBUG: System-Theme wurde geändert und angewendet.");
          } else {
            if (debugMode)
              console.log(
                "DEBUG: System-Theme-Änderung ignoriert, da nicht System-Theme ausgewählt ist."
              );
          }
        });
      if (debugMode)
        console.log(
          "DEBUG: Event-Listener für System-Theme-Änderungen hinzugefügt."
        );
    } else {
      if (debugMode) console.log("DEBUG: window.matchMedia nicht verfügbar.");
    }
  });

  /**
   * Switches between light and dark theme.
   * Schaltet zwischen hellem und dunklem Theme um.
   * @param {event} e The event that triggered the change. Das Ereignis, das die Änderung ausgelöst hat.
   * @returns {undefined}
   */
  function toggleTheme(e) {
    if (debugMode) console.log("DEBUG: toggleTheme aufgerufen.");
    if (typeof e !== "undefined") {
      e.preventDefault();
      if (debugMode)
        console.log("DEBUG: Standardaktion des Events verhindert.");
    }

    var themeToSelect = (currentTheme + 1) % themes.length;
    if (debugMode)
      console.log("DEBUG: Nächstes Theme zur Auswahl: ID " + themeToSelect);
    setTheme(themeToSelect, true, true);
  }

  /**
   * Sets the display theme.
   * Setzt das Anzeigethema.
   * @param {number} themeId The id of the theme to set. Die ID des zu setzenden Themas.
   * @param {bool} storePref Determines whether a preference is stored in the user's localstorage. Bestimmt, ob eine Präferenz im lokalen Speicher des Benutzers gespeichert wird.
   * @param {bool} doTransition Determines whether to animate the theme transition. Bestimmt, ob der Theme-Übergang animiert werden soll.
   * @returns {undefined}
   */
  function setTheme(themeId, storePref, doTransition) {
    if (debugMode)
      console.log(
        "DEBUG: setTheme aufgerufen mit Theme ID: " +
          themeId +
          ", storePref: " +
          storePref +
          ", doTransition: " +
          doTransition
      );
    var body = document.getElementsByTagName("body")[0];
    if (!body) {
      if (debugMode)
        console.error("DEBUG: Body-Element in setTheme nicht gefunden.");
      return;
    }
    var theme = themes[themeId];
    var isSystemTheme = themeId == systemThemeId;

    if (debugMode) console.log("DEBUG: Aktuelles Theme-Objekt:", theme);

    // Remove any lingering theme.
    body.classList.forEach((cls) => {
      if (cls.startsWith("theme-")) {
        body.classList.remove(cls);
        if (debugMode) console.log("DEBUG: Entferne Theme-Klasse: " + cls);
      }
    });
    if (debugMode) console.log("DEBUG: Vorhandene Theme-Klassen entfernt.");

    // Enable transition effects if specified.
    if (doTransition) {
      body.classList.add("transitioning");
      if (debugMode) console.log("DEBUG: 'transitioning'-Klasse hinzugefügt.");
    }

    // Perform theme selection logic if system theme is chosen.
    if (isSystemTheme) {
      setSystemTheme(doTransition);
      if (debugMode) console.log("DEBUG: System-Theme-Logik angewendet.");
    }

    // Apply a class if the theme specifies it.
    if (theme.class != null) {
      body.classList.add(theme.class);
      if (debugMode)
        console.log(
          "DEBUG: Theme-spezifische Klasse hinzugefügt: " + theme.class
        );
    }

    // Update the toggle button.
    const toggleLightsThemename = document.querySelector(
      "#toggle_lights .themename"
    );
    if (toggleLightsThemename) {
      toggleLightsThemename.innerHTML = theme.name;
      if (debugMode)
        console.log("DEBUG: Theme-Name auf Button aktualisiert: " + theme.name);
    } else {
      if (debugMode)
        console.log(
          "DEBUG: Element für Theme-Namen auf Button nicht gefunden."
        );
    }

    // Store the theme selection in localstorage if enabled.
    if (storePref && typeof window.localStorage != "undefined") {
      if (debugMode)
        console.log(
          "DEBUG: Speichern der Präferenz im Local Storage aktiviert."
        );
      if (!isSystemTheme) {
        window.localStorage.setItem("themePref", themeId);
        if (debugMode)
          console.log(
            "DEBUG: Theme-Präferenz " +
              themeId +
              " im Local Storage gespeichert."
          );
      } else {
        window.localStorage.removeItem("themePref");
        if (debugMode)
          console.log(
            "DEBUG: System-Theme ausgewählt, Präferenz aus Local Storage entfernt."
          );
      }
    } else if (debugMode && !storePref) {
      console.log(
        "DEBUG: Speichern der Präferenz im Local Storage deaktiviert."
      );
    } else if (debugMode) {
      console.log(
        "DEBUG: Local Storage nicht verfügbar, Präferenz nicht gespeichert."
      );
    }

    currentTheme = themeId;
    resetTransitionState();
    if (debugMode)
      console.log(
        "DEBUG: currentTheme auf " +
          currentTheme +
          " gesetzt, Übergangszustand zurückgesetzt."
      );
  }

  /**
   * Sets theme to the system default provided by the browser.
   * Setzt das Theme auf den vom Browser bereitgestellten Systemstandard.
   * @param {bool} doTransition Determines whether to animate the theme transition. Bestimmt, ob der Theme-Übergang animiert werden soll.
   * @returns {undefined}
   */
  function setSystemTheme(doTransition) {
    if (debugMode)
      console.log(
        "DEBUG: setSystemTheme aufgerufen mit doTransition: " + doTransition
      );
    if (
      window.matchMedia &&
      window.matchMedia("(prefers-color-scheme: dark)").matches
    ) {
      setTheme(systemDarkThemeId, false, doTransition);
      if (debugMode)
        console.log(
          "DEBUG: System-Theme ist dunkel, dunkles Theme angewendet."
        );
    } else {
      setTheme(systemLightThemeId, false, doTransition);
      if (debugMode)
        console.log("DEBUG: System-Theme ist hell, helles Theme angewendet.");
    }
  }

  /**
   * Disables transition flags.
   * Deaktiviert Übergangs-Flags.
   * @returns {undefined}
   */
  function resetTransitionState() {
    if (debugMode) console.log("DEBUG: resetTransitionState aufgerufen.");
    var body = document.getElementsByTagName("body")[0];
    if (!body) {
      if (debugMode)
        console.error(
          "DEBUG: Body-Element in resetTransitionState nicht gefunden."
        );
      return;
    }
    window.setTimeout(() => {
      body.classList.remove("transitioning");
      body.classList.remove("preload");
      if (debugMode)
        console.log("DEBUG: 'transitioning' und 'preload'-Klassen entfernt.");
    }, 300);
  }
})();
