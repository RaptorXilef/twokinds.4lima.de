/**
 * Enables lazy loading for character images on the characters page.
 * 
 * @file      ROOT/public/assets/js/charaktere.js
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-Share-Alike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   1.0.0
 * @since     1.0.0 Initiale Erstellung
 */


// Die debugMode Variable wird von der PHP-Seite gesetzt.
// Standardwert ist false, falls die PHP-Variable nicht injiziert wird.
let debugMode =
  typeof window.phpDebugMode !== "undefined" ? window.phpDebugMode : false;

(() => {
  if (debugMode) console.log("DEBUG: charaktere.js wird geladen.");

  document.addEventListener("DOMContentLoaded", () => {
    if (debugMode) console.log("DEBUG: DOMContentLoaded in charaktere.js.");

    // Select all images that need lazy loading (portraits and swatches)
    // We now target images with data-src and the lazy-char-img class
    const lazyImages = document.querySelectorAll(
      ".char-detail img.lazy-char-img[data-src]"
    );

    if (lazyImages.length === 0) {
      if (debugMode)
        console.log("DEBUG: Keine Bilder für Lazy Loading gefunden.");
      return;
    }

    if ("IntersectionObserver" in window) {
      if (debugMode) console.log("DEBUG: IntersectionObserver wird verwendet.");

      const lazyImageObserver = new IntersectionObserver(
        (entries, observer) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              const lazyImage = entry.target;
              if (debugMode)
                console.log(
                  "DEBUG: Bild im Viewport, lade:",
                  lazyImage.dataset.src
                );
              lazyImage.src = lazyImage.dataset.src;
              lazyImage.classList.remove("lazy-char-img"); // Remove lazy-char-img class once loaded
              lazyImage.classList.add("loaded"); // Add 'loaded' class for potential fade-in effects
              lazyImage.addEventListener("load", () => {
                if (debugMode)
                  console.log(
                    "DEBUG: Bild geladen und 'loaded' Klasse hinzugefügt:",
                    lazyImage.src
                  );
              });
              lazyImage.addEventListener("error", () => {
                if (debugMode)
                  console.error(
                    "DEBUG: Fehler beim Laden des Bildes:",
                    lazyImage.dataset.src
                  );
                // Optional: Fallback zu einem Platzhalterbild, falls der Ladevorgang fehlschlägt
                lazyImage.src =
                  "https://placehold.co/150x150/cccccc/000000?text=Fehler"; // Beispiel-Platzhalter
              });
              observer.unobserve(lazyImage); // Stop observing once loaded
            }
          });
        },
        {
          rootMargin: "0px 0px 100px 0px", // Load images when they are 100px before entering the viewport
        }
      );

      lazyImages.forEach((lazyImage) => {
        lazyImageObserver.observe(lazyImage);
      });

      if (debugMode)
        console.log(
          "DEBUG: IntersectionObserver für " +
            lazyImages.length +
            " Bilder eingerichtet."
        );
    } else {
      // Fallback for browsers that do not support IntersectionObserver
      if (debugMode)
        console.log(
          "DEBUG: IntersectionObserver nicht unterstützt, Fallback auf Scroll-Event."
        );

      const lazyLoad = () => {
        if (debugMode)
          console.log("DEBUG: Scroll-Event ausgelöst, überprüfe Bilder.");
        lazyImages.forEach((lazyImage) => {
          if (
            lazyImage.getBoundingClientRect().top < window.innerHeight + 100 &&
            lazyImage.getBoundingClientRect().bottom > -100
          ) {
            if (debugMode)
              console.log(
                "DEBUG: Bild im Viewport (Fallback), lade:",
                lazyImage.dataset.src
              );
            lazyImage.src = lazyImage.dataset.src;
            lazyImage.classList.remove("lazy-char-img");
            lazyImage.classList.add("loaded");
            lazyImage.addEventListener("load", () => {
              // Image loaded (optional: add fade-in effect via CSS for .loaded class)
            });
            lazyImage.addEventListener("error", () => {
              if (debugMode)
                console.error(
                  "DEBUG: Fehler beim Laden des Bildes (Fallback):",
                  lazyImage.dataset.src
                );
              lazyImage.src =
                "https://placehold.co/150x150/cccccc/000000?text=Fehler";
            });
          }
        });
        // Remove event listener if all images are loaded
        if (
          document.querySelectorAll(".char-detail img.lazy-char-img[data-src]")
            .length === 0
        ) {
          document.removeEventListener("scroll", lazyLoad);
          window.removeEventListener("resize", lazyLoad);
          window.removeEventListener("orientationchange", lazyLoad);
          if (debugMode)
            console.log(
              "DEBUG: Alle Bilder geladen, Scroll-Listener entfernt."
            );
        }
      };

      document.addEventListener("scroll", lazyLoad);
      window.addEventListener("resize", lazyLoad);
      window.addEventListener("orientationchange", lazyLoad);
      lazyLoad(); // Initial check on load
      if (debugMode)
        console.log(
          "DEBUG: Fallback-Lazy-Loading für " +
            lazyImages.length +
            " Bilder eingerichtet."
        );
    }
  });
})();
