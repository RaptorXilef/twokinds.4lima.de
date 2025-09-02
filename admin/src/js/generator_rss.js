// admin/js/rss_generator.js
(() => {
  document.addEventListener("DOMContentLoaded", () => {
    const generateButton = document.getElementById("generateRss");
    const generationMessage = document.getElementById("generationMessage");

    if (generateButton) {
      generateButton.addEventListener("click", async () => {
        // Deaktiviere den Button während der Generierung
        generateButton.disabled = true;
        generateButton.classList.add("opacity-50", "cursor-not-allowed");
        generationMessage.textContent = "Generiere RSS-Feed... Bitte warten.";
        generationMessage.classList.remove("text-green-700", "text-red-700");
        generationMessage.classList.add("text-blue-700"); // Blauer Text für Ladezustand

        try {
          const response = await fetch(window.location.href, {
            method: "POST",
            headers: {
              "Content-Type": "application/x-www-form-urlencoded",
            },
            body: "action=generate_rss", // Sende eine Aktion, um die PHP-Logik auszulösen
          });

          const result = await response.json();

          if (result.success) {
            let displayMessage = result.message;
            // Wenn eine RSS-URL zurückgegeben wurde, füge sie als anklickbaren Link hinzu
            if (result.rssUrl) {
              displayMessage +=
                ' Der Feed ist verfügbar unter: <a href="' +
                result.rssUrl +
                '" target="_blank" class="text-blue-500 hover:underline">' +
                result.rssUrl +
                "</a>";
            }
            generationMessage.innerHTML = displayMessage; // Verwende innerHTML, um den Link zu rendern
            generationMessage.classList.remove("text-blue-700", "text-red-700");
            generationMessage.classList.add("text-green-700");
          } else {
            generationMessage.textContent = "Fehler: " + result.message;
            generationMessage.classList.remove(
              "text-blue-700",
              "text-green-700"
            );
            generationMessage.classList.add("text-red-700");
          }
        } catch (error) {
          console.error("Fehler beim Generieren des RSS-Feeds:", error);
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
          }
        }
      });
    }
  });
})();
