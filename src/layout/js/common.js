/**
 * Enables common features throughout the website
 */
(() => {
  var themes = [
    { id: 0, name: "Default", class: null },
    { id: 1, name: "Lights On", class: null },
    { id: 2, name: "Lights Off", class: "theme-night" },
  ];
  var systemThemeId = 0;
  var systemLightThemeId = 1;
  var systemDarkThemeId = 2;
  var currentTheme = systemThemeId;

  document.addEventListener("DOMContentLoaded", () => {
    var body = document.getElementsByTagName("body")[0];

    // Show elements that are hidden by default due to requiring JS.
    document
      .querySelectorAll(".jsdep")
      .forEach((el) => el.classList.remove("jsdep"));

    document
      .querySelector("#toggle_lights")
      .addEventListener("click", toggleTheme);

    if (typeof window.localStorage != "undefined") {
      if (typeof window.localStorage.themePref == "undefined") {
        setTheme(systemThemeId, false, false);
      } else {
        setTheme(window.localStorage.themePref, false, false);
      }
    } else {
      body.classList.remove("preload");
    }

    // Theme also toggles on 'i' keypress, but only if not an admin page.
    // window.isAdminPage is set in header.php
    if (typeof window.isAdminPage === "undefined" || !window.isAdminPage) {
      body.addEventListener("keyup", (e) => {
        if (e.which == 73) {
          toggleTheme(e);
        }
      });
    }

    // Watch the system theme change event and automatically change with it.
    if (window.matchMedia) {
      window
        .matchMedia("(prefers-color-scheme: dark)")
        .addEventListener("change", () => {
          // Ignore the event if the system theme is not currently selected.
          if (currentTheme == systemThemeId) {
            setTheme(systemThemeId, false, true);
          }
        });
    }
  });

  /**
   * Switches between light and dark theme.
   * @param {event} e The event that triggered the change.
   * @returns {undefined}
   */
  function toggleTheme(e) {
    if (typeof e !== "undefined") {
      e.preventDefault();
    }

    var themeToSelect = (currentTheme + 1) % themes.length;
    setTheme(themeToSelect, true, true);
  }

  /**
   * Sets the display theme.
   * @param {number} themeId The id of the theme to set.
   * @param {bool} storePref Determines whether a preference is stored in the user's localstorage.
   * @param {bool} doTransition Determines whether to animate the theme transition.
   * @returns {undefined}
   */
  function setTheme(themeId, storePref, doTransition) {
    var body = document.getElementsByTagName("body")[0];
    var theme = themes[themeId];
    var isSystemTheme = themeId == systemThemeId;

    // Remove any lingering theme.
    body.classList.forEach((cls) => {
      if (cls.startsWith("theme-")) {
        body.classList.remove(cls);
      }
    });

    // Enable transition effects if specified.
    if (doTransition) {
      body.classList.add("transitioning");
    }

    // Perform theme selection logic if system theme is chosen.
    if (isSystemTheme) {
      setSystemTheme(doTransition);
    }

    // Apply a class if the theme specifies it.
    if (theme.class != null) {
      body.classList.add(theme.class);
    }

    // Update the toggle button.
    document.querySelector("#toggle_lights .themename").innerHTML = theme.name;

    // Store the theme selection in localstorage if enabled.
    if (storePref && typeof window.localStorage != "undefined") {
      if (!isSystemTheme) {
        window.localStorage.setItem("themePref", themeId);
      } else {
        window.localStorage.removeItem("themePref");
      }
    }

    currentTheme = themeId;
    resetTransitionState();
  }

  /**
   * Sets theme to the system default provided by the browser.
   * @param {bool} doTransition Determines whether to animate the theme transition.
   * @returns {undefined}
   */
  function setSystemTheme(doTransition) {
    if (
      window.matchMedia &&
      window.matchMedia("(prefers-color-scheme: dark)").matches
    ) {
      setTheme(systemDarkThemeId, false, doTransition);
    } else {
      setTheme(systemLightThemeId, false, doTransition);
    }
  }

  /**
   * Disables transition flags.
   * @returns {undefined}
   */
  function resetTransitionState() {
    var body = document.getElementsByTagName("body")[0];
    window.setTimeout(() => {
      body.classList.remove("transitioning");
      body.classList.remove("preload");
    }, 300);
  }
})();
