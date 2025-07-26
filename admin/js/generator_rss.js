// admin/js/rss_generator.js

// Die debugMode Variable wird von der PHP-Seite gesetzt.
// Standardwert ist false, falls die PHP-Variable nicht injiziert wird.
let debugMode =
  typeof window.phpDebugMode !== "undefined" ? window.phpDebugMode : false;

(() => {
  if (debugMode) console.log("DEBUG: generator_rss.js wird geladen.");

  document.addEventListener("DOMContentLoaded", () => {
    if (debugMode) console.log("DEBUG: DOMContentLoaded in generator_rss.js.");

    const generateButton = document.getElementById("generateRss");
    const generationMessage = document.getElementById("generationMessage");

    if (generateButton) {
      if (debugMode) console.log("DEBUG: Generierungs-Button gefunden.");
      generateButton.addEventListener("click", async () => {
        if (debugMode) console.log("DEBUG: Generierungs-Button geklickt.");
        // Deaktiviere den Button während der Generierung
        generateButton.disabled = true;
        generateButton.classList.add("opacity-50", "cursor-not-allowed");
        generationMessage.textContent = "Generiere RSS-Feed... Bitte warten.";
        generationMessage.classList.remove("text-green-700", "text-red-700");
        generationMessage.classList.add("text-blue-700"); // Blauer Text für Ladezustand
        if (debugMode)
          console.log("DEBUG: RSS-Generierung gestartet, Button deaktiviert.");

        try {
          const response = await fetch(window.location.href, {
            method: "POST",
            headers: {
              "Content-Type": "application/x-www-form-urlencoded",
            },
            body: "action=generate_rss", // Sende eine Aktion, um die PHP-Logik auszulösen
          });
          if (debugMode) console.log("DEBUG: Fetch-Anfrage gesendet.");

          const result = await response.json();
          if (debugMode) console.log("DEBUG: Server-Antwort erhalten:", result);

          if (result.success) {
            generationMessage.textContent = result.message;
            generationMessage.classList.remove("text-blue-700", "text-red-700");
            generationMessage.classList.add("text-green-700");
            if (debugMode)
              console.log("DEBUG: RSS-Feed erfolgreich generiert.");
          } else {
            generationMessage.textContent = "Fehler: " + result.message;
            generationMessage.classList.remove(
              "text-blue-700",
              "text-green-700"
            );
            generationMessage.classList.add("text-red-700");
            if (debugMode)
              console.error(
                "DEBUG: Fehler bei der RSS-Generierung:",
                result.message
              );
          }
        } catch (error) {
          if (debugMode)
            console.error(
              "DEBUG: Fehler beim Generieren des RSS-Feeds:",
              error
            );
          generationMessage.textContent =
            "Ein unerwarteter Fehler ist aufgetreten.";
          generationMessage.classList.remove("text-blue-700", "text-green-700");
          generationMessage.classList.add("text-red-700");
        } finally {
          // Aktiviere den Button wieder, es sei denn, die JSON-Dateien sind immer noch fehlerhaft
          // Der PHP-Code deaktiviert den Button initial, wenn JSON-Fehler vorliegen.
          // Hier aktivieren wir ihn nur, wenn keine initialen Fehler vorlagen.
          const comicJsonStatus = document.querySelector("li.text-red-700");
          if (!comicJsonStatus) {
            // Nur aktivieren, wenn keine Fehler angezeigt werden
            generateButton.disabled = false;
            generateButton.classList.remove("opacity-50", "cursor-not-allowed");
            if (debugMode)
              console.log("DEBUG: Generierungs-Button reaktiviert.");
          } else {
            if (debugMode)
              console.log(
                "DEBUG: Generierungs-Button bleibt deaktiviert aufgrund von JSON-Fehlern."
              );
          }
        }
      });
    } else {
      if (debugMode) console.log("DEBUG: Generierungs-Button nicht gefunden.");
    }
  });
})();
