/**
 * Enables dynamic loading of thumbnails for archive page.
 */
// Die debugMode Variable wird von der PHP-Seite gesetzt.
// Standardwert ist false, falls die PHP-Variable nicht injiziert wird.
let debugMode =
  typeof window.phpDebugMode !== "undefined" ? window.phpDebugMode : false;

(function () {
  if (debugMode) console.log("DEBUG: archive.js wird geladen.");

  addEventListener("DOMContentLoaded", () => {
    if (debugMode) console.log("DEBUG: DOMContentLoaded in archive.js.");

    document.querySelectorAll(".chapter h2").forEach((el) =>
      el.addEventListener("click", (ev) => {
        if (debugMode) console.log("DEBUG: Kapitel-Header geklickt.");
        showThumbnails(ev.target.closest(".chapter"));
      })
    );

    document.querySelectorAll(".chapter-links").forEach((el) => {
      el.style.display = "none";
      if (debugMode) console.log("DEBUG: Kapitel-Links initial ausgeblendet.");
    });

    var storedExpansion = null;
    var expandedChapters = [];
    var expireTime = null;
    if (
      typeof window.localStorage != "undefined" &&
      typeof window.localStorage.archiveExpansion != "undefined"
    ) {
      storedExpansion = JSON.parse(window.localStorage.archiveExpansion);
      expandedChapters = storedExpansion.expandedChapters;
      expireTime = storedExpansion.expireTime;
      if (debugMode)
        console.log(
          "DEBUG: Gespeicherte Erweiterung aus Local Storage geladen:",
          storedExpansion
        );
    } else {
      if (debugMode)
        console.log(
          "DEBUG: Keine gespeicherte Erweiterung im Local Storage gefunden."
        );
    }

    // If the user has previously expanded sections of this page, re-expand those sections on return.
    var time = new Date().getTime();
    if (!expandedChapters.length || expireTime <= time) {
      const firstChapter = document.querySelector(".chapter:first-of-type");
      if (debugMode)
        console.log(
          "DEBUG: Erste Kapitel wird erweitert (keine gespeicherten Daten oder abgelaufen)."
        );
      showThumbnails(firstChapter, true);
    } else {
      if (debugMode)
        console.log("DEBUG: Gespeicherte Kapitel werden erweitert.");
      for (var idx = 0; idx < expandedChapters.length; ++idx) {
        var chapter = document.querySelector(
          ".chapter[data-ch-id='" + expandedChapters[idx] + "']"
        );
        if (chapter) {
          showThumbnails(chapter, true);
          if (debugMode)
            console.log(
              "DEBUG: Kapitel " + expandedChapters[idx] + " erweitert."
            );
        } else {
          if (debugMode)
            console.log(
              "DEBUG: Kapitel " + expandedChapters[idx] + " nicht gefunden."
            );
        }
      }
    }
  });

  /**
   * Displays the thumbnails of a given chapter.
   * @param {HTMLElement} chapter The chapter element.
   * @param {bool} noStore If true, the chapter's expanded state will not be stored in local storage.
   * @returns {undefined}
   */
  function showThumbnails(chapter, noStore) {
    if (chapter == null) {
      if (debugMode)
        console.log(
          "DEBUG: showThumbnails aufgerufen mit null-Kapitel, beendet."
        );
      return;
    }

    var linkContainer = chapter.querySelector(".chapter-links");
    var arrow = chapter.querySelector(".arrow-left, .arrow-down");

    if (!linkContainer || !arrow) {
      if (debugMode)
        console.log(
          "DEBUG: linkContainer oder arrow nicht gefunden für Kapitel:",
          chapter
        );
      return;
    }

    if (linkContainer.style.display == "none") {
      if (debugMode)
        console.log("DEBUG: Kapitel wird erweitert:", chapter.dataset.chId);
      // Switch arrow direction and animate the container
      arrow.classList.remove("arrow-left");
      arrow.classList.add("arrow-down");
      arrow.classList.add("animate");
      setTimeout(() => {
        arrow.classList.remove("animate");
      }, 1500);

      linkContainer.closest(".chapter").classList.add("expanded");
      linkContainer.style.display = "flex"; // Changed from "block" to "flex" for better layout

      // Dynamically load images
      if (!linkContainer.dataset.loaded) {
        linkContainer.dataset.loaded = true;
        if (debugMode)
          console.log(
            "DEBUG: Bilder für Kapitel " +
              chapter.dataset.chId +
              " werden geladen."
          );

        linkContainer.querySelectorAll("img").forEach((img) => {
          // When the image is done loading, fade it in
          // At the end of the fade, swap the page number to the front and only show it on hover.
          img.addEventListener("load", () => {
            img.closest("a").classList.add("loaded");
            if (debugMode) console.log("DEBUG: Bild geladen:", img.dataset.src);
          });

          // Load each image
          img.setAttribute("src", img.dataset.src);
        });
      } else {
        if (debugMode)
          console.log(
            "DEBUG: Bilder für Kapitel " +
              chapter.dataset.chId +
              " bereits geladen."
          );
      }
    } else {
      if (debugMode)
        console.log("DEBUG: Kapitel wird eingeklappt:", chapter.dataset.chId);
      // Switch arrow direction and animate the container
      arrow.classList.add("arrow-left");
      arrow.classList.remove("arrow-down");
      linkContainer.closest(".chapter").classList.remove("expanded");
      linkContainer.style.display = "none";
    }

    // Write a new list of expanded chapters to local storage
    if (typeof window.localStorage != "undefined" && !noStore) {
      var expandedChapters = document.querySelectorAll(".chapter.expanded");
      var chapterArray = [];

      expandedChapters.forEach((chapter) => {
        chapterArray.push(chapter.dataset.chId);
      });

      var storageObj = {
        // 10 minutes from now
        expireTime: new Date().getTime() + 600000,
        expandedChapters: chapterArray,
      };

      window.localStorage.archiveExpansion = JSON.stringify(storageObj);
      if (debugMode)
        console.log(
          "DEBUG: Erweiterungsstatus in Local Storage gespeichert:",
          storageObj
        );
    } else if (debugMode && noStore) {
      console.log(
        "DEBUG: Erweiterungsstatus nicht in Local Storage gespeichert (noStore ist true)."
      );
    } else if (debugMode) {
      console.log("DEBUG: Local Storage nicht verfügbar.");
    }
  }
})();
