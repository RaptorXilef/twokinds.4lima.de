/**
 * Enables dynamic loading of thumbnails for archive page.
 */
// Steuerung für Debug-Meldungen. Setze auf true, um Debug-Meldungen in der Konsole anzuzeigen.
const debugModeJsArchiv = false; // Kann auf false gesetzt werden, um Debug-Meldungen zu deaktivieren.

(function () {
  if (debugModeJsArchiv)
    console.log("DEBUG [archive.js/archive.min.js]: Skript wird geladen.");

  // Warte, bis das DOM vollständig geladen ist, bevor JavaScript ausgeführt wird.
  addEventListener("DOMContentLoaded", () => {
    if (debugModeJsArchiv)
      console.log(
        "DEBUG [archive.js/archive.min.js]: DOMContentLoaded Event gefeuert."
      );

    // Füge Event-Listener zu allen Kapitelüberschriften hinzu.
    document.querySelectorAll(".chapter h2").forEach((el, index) => {
      el.addEventListener("click", (ev) => {
        if (debugModeJsArchiv)
          console.log(
            `DEBUG [archive.js/archive.min.js]: Kapitel-Header (Index: ${index}) geklickt. Ziel-Element:`,
            ev.target
          );
        showThumbnails(ev.target.closest(".chapter"));
      });
      if (debugModeJsArchiv)
        console.log(
          `DEBUG [archive.js/archive.min.js]: Event-Listener zu Kapitel-H2 (Index: ${index}) hinzugefügt. Element:`,
          el
        );
    });

    // NEU: Initialisiere alle collapsible-content Container als ausgeblendet.
    // Dies ist entscheidend, da das CSS dafür zu fehlen scheint.
    document.querySelectorAll(".collapsible-content").forEach((el, index) => {
      el.style.display = "none";
      if (debugModeJsArchiv)
        console.log(
          `DEBUG [archive.js/archive.min.js]: Collapsible-Content Container (Index: ${index}) initial ausgeblendet. Element:`,
          el
        );
    });

    let storedExpansion = null;
    let expandedChapters = [];
    let expireTime = null;

    // Versuche, den gespeicherten Erweiterungsstatus aus dem Local Storage zu laden.
    if (
      typeof window.localStorage !== "undefined" &&
      typeof window.localStorage.archiveExpansion !== "undefined"
    ) {
      try {
        storedExpansion = JSON.parse(window.localStorage.archiveExpansion);
        expandedChapters = storedExpansion.expandedChapters;
        expireTime = storedExpansion.expireTime;
        if (debugModeJsArchiv)
          console.log(
            "DEBUG [archive.js/archive.min.js]: Gespeicherte Archiv-Erweiterung im Local Storage gefunden:",
            storedExpansion
          );
      } catch (e) {
        if (debugModeJsArchiv)
          console.error(
            "DEBUG [archive.js/archive.min.js]: Fehler beim Parsen des Local Storage Inhalts:",
            e
          );
        // Bei einem Fehler den Local Storage Eintrag entfernen, um zukünftige Fehler zu vermeiden.
        window.localStorage.removeItem("archiveExpansion");
      }
    }

    // Wenn der Benutzer zuvor Abschnitte dieser Seite erweitert hat,
    // erweitere diese Abschnitte beim erneuten Aufruf der Seite.
    const currentTime = new Date().getTime();
    if (!expandedChapters.length || expireTime <= currentTime) {
      // Wenn keine erweiterten Kapitel gespeichert sind oder die Zeit abgelaufen ist,
      // klappe das erste Kapitel auf.
      const firstChapter = document.querySelector(".chapter:first-of-type");
      if (firstChapter) {
        if (debugModeJsArchiv)
          console.log(
            "DEBUG [archive.js/archive.min.js]: Keine erweiterten Kapitel gefunden oder abgelaufen, klappe das erste Kapitel auf."
          );
        showThumbnails(firstChapter, true, true); // noAnimation = true, noStore = true
      }
    } else {
      // Stelle die zuvor erweiterten Kapitel wieder her.
      if (debugModeJsArchiv)
        console.log(
          "DEBUG [archive.js/archive.min.js]: Stelle zuvor erweiterte Kapitel aus dem Local Storage wieder her."
        );
      for (let idx = 0; idx < expandedChapters.length; ++idx) {
        const chapterId = expandedChapters[idx];
        const chapter = document.querySelector(
          `.chapter[data-ch-id='${chapterId}']`
        );
        if (chapter) {
          showThumbnails(chapter, true, true); // noAnimation = true, noStore = true
          if (debugModeJsArchiv)
            console.log(
              `DEBUG [archive.js/archive.min.js]: Kapitel mit ID '${chapterId}' erfolgreich wiederhergestellt.`
            );
        } else {
          if (debugModeJsArchiv)
            console.warn(
              `DEBUG [archive.js/archive.min.js]: Kapitel mit ID '${chapterId}' aus Local Storage nicht gefunden.`
            );
        }
      }
    }
  });

  /**
   * Zeigt oder versteckt die Miniaturansichten für ein gegebenes Kapitel.
   * Lädt die Bilder bei Bedarf dynamisch.
   * @param {HTMLElement} element Das HTML-Element des Kapitels.
   * @param {boolean} noAnimation Wenn true, wird die Pfeil-Animation unterdrückt.
   * @param {boolean} noStore Wenn true, wird der Erweiterungsstatus nicht im Local Storage gespeichert.
   * @returns {undefined}
   */
  function showThumbnails(element, noAnimation = false, noStore = false) {
    if (!element) {
      if (debugModeJsArchiv)
        console.warn(
          "DEBUG [showThumbnails]: Element ist null oder undefined."
        );
      return;
    }

    const chapterId = element.dataset.chId;
    if (debugModeJsArchiv)
      console.log(
        `DEBUG [showThumbnails]: Funktion aufgerufen für Kapitel-ID: ${chapterId}. noAnimation: ${noAnimation}, noStore: ${noStore}`
      );

    const chapterHeader = element.querySelector("h2");
    // NEU: Referenz auf den .collapsible-content Div
    const collapsibleContent = element.querySelector(".collapsible-content");
    const linkContainer = element.querySelector(".chapter-links"); // Dies ist der aside-Tag innerhalb von .collapsible-content
    const arrow = chapterHeader.querySelector(".arrow-down, .arrow-left");

    if (!collapsibleContent || !linkContainer || !arrow) {
      if (debugModeJsArchiv)
        console.error(
          `DEBUG [showThumbnails]: Benötigte Elemente für Kapitel '${chapterId}' nicht gefunden. collapsibleContent:`,
          collapsibleContent,
          "linkContainer:",
          linkContainer,
          "arrow:",
          arrow
        );
      return;
    }

    // Überprüfe, ob das Kapitel derzeit ausgeklappt ist (basierend auf der display-Eigenschaft von .collapsible-content)
    const isExpanded = collapsibleContent.style.display !== "none";

    if (!isExpanded) {
      // Kapitel ist eingeklappt, klappe es aus
      if (debugModeJsArchiv)
        console.log(
          `DEBUG [showThumbnails]: Kapitel '${chapterId}' wird ausgeklappt.`
        );

      element.classList.add("expanded"); // Füge die 'expanded' Klasse zur Sektion hinzu

      // Setze die Pfeilrichtung und animiere den Pfeil
      if (!noAnimation) {
        arrow.style.transition = "transform 0.3s ease-out"; // Füge Transition direkt hinzu
      } else {
        arrow.style.transition = "none"; // Keine Animation
      }
      arrow.style.transform = "rotate(0deg)"; // Pfeil zeigt nach unten (ausgeklappt)

      // NEU: Zeige den gesamten collapsible-content Div an
      collapsibleContent.style.display = "block"; // Verwende 'block' für den Text- und Bild-Container

      // Der linkContainer (aside.chapter-links) hat bereits display:flex im CSS,
      // daher wird er automatisch korrekt angezeigt, wenn sein Elternelement sichtbar ist.

      if (debugModeJsArchiv)
        console.log(
          `DEBUG [showThumbnails]: Kapitel '${chapterId}' auf 'expanded' gesetzt und Anzeige/Pfeil angepasst.`
        );

      // Lade Bilder dynamisch (nur wenn noch nicht geladen)
      if (!linkContainer.dataset.loaded) {
        linkContainer.dataset.loaded = true;
        if (debugModeJsArchiv)
          console.log(
            `DEBUG [showThumbnails]: Lade Thumbnails für Kapitel '${chapterId}'.`
          );

        linkContainer.querySelectorAll("img").forEach((img, index) => {
          img.addEventListener("load", () => {
            img.closest("a").classList.add("loaded");
            if (debugModeJsArchiv)
              console.log(
                `DEBUG [showThumbnails]: Thumbnail (Index: ${index}) für Kapitel '${chapterId}' erfolgreich geladen. src: ${img.src}`
              );
          });

          img.addEventListener("error", () => {
            if (debugModeJsArchiv)
              console.error(
                `DEBUG [showThumbnails]: Fehler beim Laden des Thumbnails (Index: ${index}) für Kapitel '${chapterId}'. data-src: ${img.dataset.src}`
              );
            // Fallback-Bild bei Fehler
            img.src = "https://placehold.co/150x150/cccccc/000000?text=Fehler";
            img.closest("a").classList.add("loaded"); // Trotz Fehler einblenden
          });

          img.setAttribute("src", img.dataset.src);
          if (debugModeJsArchiv)
            console.log(
              `DEBUG [showThumbnails]: Thumbnail (Index: ${index}) für Kapitel '${chapterId}' Ladevorgang gestartet. data-src: ${img.dataset.src}`
            );
        });
      }
    } else {
      // Kapitel ist ausgeklappt, klappe es ein
      if (debugModeJsArchiv)
        console.log(
          `DEBUG [showThumbnails]: Kapitel '${chapterId}' wird eingeklappt.`
        );

      element.classList.remove("expanded"); // Entferne die 'expanded' Klasse

      // Setze die Pfeilrichtung und animiere den Pfeil
      if (!noAnimation) {
        arrow.style.transition = "transform 0.3s ease-out"; // Füge Transition direkt hinzu
      } else {
        arrow.style.transition = "none"; // Keine Animation
      }
      arrow.style.transform = "rotate(-90deg)"; // Pfeil zeigt nach links (eingeklappt)

      // NEU: Blende den gesamten collapsible-content Div aus
      collapsibleContent.style.display = "none";

      if (debugModeJsArchiv)
        console.log(
          `DEBUG [showThumbnails]: Kapitel '${chapterId}' auf 'eingeklappt' gesetzt und Anzeige/Pfeil angepasst.`
        );
    }

    // Speichere den aktuellen Erweiterungsstatus im Local Storage, es sei denn 'noStore' ist true.
    if (typeof window.localStorage !== "undefined" && !noStore) {
      const expandedChaptersElements =
        document.querySelectorAll(".chapter.expanded");
      const chapterArray = Array.from(expandedChaptersElements).map(
        (chap) => chap.dataset.chId
      );

      const storageObj = {
        expireTime: new Date().getTime() + 600000, // 10 Minuten von jetzt
        expandedChapters: chapterArray,
      };

      try {
        window.localStorage.archiveExpansion = JSON.stringify(storageObj);
        if (debugModeJsArchiv)
          console.log(
            "DEBUG [showThumbnails]: Erweiterungsstatus in Local Storage gespeichert:",
            storageObj
          );
      } catch (e) {
        if (debugModeJsArchiv)
          console.error(
            "DEBUG [showThumbnails]: Fehler beim Speichern des Erweiterungsstatus im Local Storage:",
            e
          );
      }
    } else if (debugModeJsArchiv && noStore) {
      console.log(
        "DEBUG [showThumbnails]: Erweiterungsstatus nicht in Local Storage gespeichert (noStore ist true)."
      );
    }
  }
})();
