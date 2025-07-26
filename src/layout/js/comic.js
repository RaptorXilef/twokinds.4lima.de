// Die debugMode Variable wird von der PHP-Seite gesetzt.
// Standardwert ist false, falls die PHP-Variable nicht injiziert wird.
let debugMode =
  typeof window.phpDebugMode !== "undefined" ? window.phpDebugMode : false;

(() => {
  if (debugMode) console.log("DEBUG: comic.js wird geladen.");

  const bookmarkButtonId = "add-bookmark";
  const activeBookmarkClass = "bookmarked";
  const bookmarkMaxEntries = 50;

  document.addEventListener("DOMContentLoaded", async () => {
    if (debugMode) console.log("DEBUG: DOMContentLoaded in comic.js.");

    // Bind the left and right arrow keys and j/k to comic navigation
    var body = document.getElementsByTagName("body")[0];
    var navprev = document.querySelector("a.navprev");
    var navnext = document.querySelector("a.navnext");

    if (body) {
      body.addEventListener("keyup", (e) => {
        if (debugMode) console.log("DEBUG: Tastendruck erkannt: " + e.key);
        if ((e.key == "ArrowLeft" || e.key == "j" || e.key == "J") && navprev) {
          if (debugMode) console.log("DEBUG: Navigiere zu vorheriger Seite.");
          parent.location = navprev.getAttribute("href");
        } else if (
          (e.key == "ArrowRight" || e.key == "k" || e.key == "K") && // Korrigiert 'k' zu 'K' für Konsistenz
          navnext
        ) {
          if (debugMode) console.log("DEBUG: Navigiere zu nächster Seite.");
          parent.location = navnext.getAttribute("href");
        }
      });
      if (debugMode) console.log("DEBUG: Tastatur-Navigation initialisiert.");
    } else {
      if (debugMode)
        console.log(
          "DEBUG: Body-Element für Tastatur-Navigation nicht gefunden."
        );
    }

    const bookmarkButton = document.getElementById(bookmarkButtonId);
    const bookmarks = await getStoredBookmarks();
    if (debugMode) console.log("DEBUG: Lesezeichen geladen:", bookmarks);

    // Logic for the individual comic page bookmark button
    if (bookmarkButton) {
      if (debugMode)
        console.log(
          "DEBUG: Lesezeichen-Button für einzelne Comic-Seite gefunden."
        );
      bookmarkButton.addEventListener("click", async (e) => {
        if (debugMode) console.log("DEBUG: Lesezeichen-Button geklickt.");
        await toggleBookmark(e.target);
      });
      if (bookmarks.has(bookmarkButton.dataset.id)) {
        setBookmarkButtonActive();
        if (debugMode)
          console.log(
            "DEBUG: Lesezeichen-Button als aktiv gesetzt (Seite ist gebookmarkt)."
          );
      } else {
        if (debugMode)
          console.log(
            "DEBUG: Lesezeichen-Button als inaktiv gesetzt (Seite ist nicht gebookmarkt)."
          );
      }
    }
    // Logic for the bookmarks page (lesezeichen.php)
    else if (document.getElementById("bookmarksPage")) {
      if (debugMode) console.log("DEBUG: Lesezeichen-Seite erkannt.");
      populateBookmarksPage(bookmarks);
      document
        .getElementById("removeAll")
        .addEventListener("click", handleRemoveAllBookmarks);
      document
        .getElementById("export")
        .addEventListener("click", handleExportBookmarks);
      document
        .getElementById("importButton") // Changed to importButton
        .addEventListener("click", () => {
          if (debugMode) console.log("DEBUG: Import-Button geklickt.");
          document.getElementById("import").click();
        });
      document
        .getElementById("import")
        .addEventListener("change", handleImportBookmarks);
      if (debugMode)
        console.log(
          "DEBUG: Event-Listener für Lesezeichen-Seite initialisiert."
        );
    } else {
      if (debugMode)
        console.log(
          "DEBUG: Weder Lesezeichen-Button noch Lesezeichen-Seite gefunden."
        );
    }
  });

  /**
   * Retrieves stored bookmarks from local storage.
   * Ruft gespeicherte Lesezeichen aus dem lokalen Speicher ab.
   * @returns {Promise<Map<string, object>>} A map of bookmarks. Eine Map von Lesezeichen.
   */
  async function getStoredBookmarks() {
    if (debugMode) console.log("DEBUG: getStoredBookmarks aufgerufen.");
    if (typeof window.localStorage == "undefined") {
      if (debugMode) console.log("DEBUG: Local Storage nicht verfügbar.");
      return new Map();
    }
    try {
      const stored = window.localStorage.getItem("comicBookmarks");
      const parsed = stored ? new Map(JSON.parse(stored)) : new Map();
      if (debugMode)
        console.log("DEBUG: Lesezeichen aus Local Storage abgerufen:", parsed);
      return parsed;
    } catch (e) {
      console.error("Error parsing bookmarks from localStorage:", e);
      if (debugMode)
        console.error(
          "DEBUG: Fehler beim Parsen von Lesezeichen aus Local Storage:",
          e
        );
      return new Map();
    }
  }

  /**
   * Stores bookmarks in local storage.
   * Speichert Lesezeichen im lokalen Speicher.
   * @param {Map<string, object>} bookmarkMap The map of bookmarks to store. Die Map der zu speichernden Lesezeichen.
   * @returns {Promise<void>}
   */
  async function storeBookmarks(bookmarkMap) {
    if (debugMode)
      console.log("DEBUG: storeBookmarks aufgerufen mit:", bookmarkMap);
    if (typeof window.localStorage == "undefined") {
      if (debugMode)
        console.log(
          "DEBUG: Local Storage nicht verfügbar, Speichern abgebrochen."
        );
      return;
    }
    try {
      window.localStorage.setItem(
        "comicBookmarks",
        JSON.stringify(Array.from(bookmarkMap.entries()))
      );
      if (debugMode)
        console.log(
          "DEBUG: Lesezeichen erfolgreich im Local Storage gespeichert."
        );
    } catch (e) {
      console.error("Error storing bookmarks to localStorage:", e);
      if (debugMode)
        console.error(
          "DEBUG: Fehler beim Speichern von Lesezeichen im Local Storage:",
          e
        );
    }
  }

  /**
   * Toggles a bookmark for the current page.
   * Schaltet ein Lesezeichen für die aktuelle Seite um.
   * @param {HTMLElement} button The bookmark button element. Das Lesezeichen-Button-Element.
   * @returns {Promise<void>}
   */
  async function toggleBookmark(button) {
    if (debugMode)
      console.log("DEBUG: toggleBookmark aufgerufen für Button:", button);
    const comicId = button.dataset.id;
    const page = button.dataset.page;
    const permalink = button.dataset.permalink;
    const thumb = button.dataset.thumb;

    const bookmarks = await getStoredBookmarks();
    if (debugMode)
      console.log("DEBUG: Aktuelle Lesezeichen vor Toggle:", bookmarks);

    if (bookmarks.has(comicId)) {
      if (debugMode)
        console.log(
          "DEBUG: Lesezeichen für Comic-ID " +
            comicId +
            " existiert, Entfernen wird angeboten."
        );
      // Remove bookmark
      showCustomConfirm(
        "Möchten Sie dieses Lesezeichen wirklich entfernen?",
        async () => {
          bookmarks.delete(comicId);
          await storeBookmarks(bookmarks);
          setBookmarkButtonInactive();
          if (debugMode)
            console.log(
              "DEBUG: Lesezeichen für Comic-ID " + comicId + " entfernt."
            );
          // If on bookmarks page, refresh the list
          if (document.getElementById("bookmarksPage")) {
            populateBookmarksPage(bookmarks);
            if (debugMode)
              console.log(
                "DEBUG: Lesezeichen-Seite nach Entfernung aktualisiert."
              );
          }
        },
        () => {
          if (debugMode)
            console.log("DEBUG: Entfernen des Lesezeichens abgebrochen.");
          // User cancelled, do nothing
        }
      );
    } else {
      if (debugMode)
        console.log(
          "DEBUG: Lesezeichen für Comic-ID " +
            comicId +
            " existiert nicht, Hinzufügen wird angeboten."
        );
      // Add bookmark
      if (bookmarks.size >= bookmarkMaxEntries) {
        if (debugMode)
          console.log(
            "DEBUG: Maximale Anzahl von Lesezeichen erreicht (" +
              bookmarkMaxEntries +
              ")."
          );
        showCustomConfirm(
          `Sie haben die maximale Anzahl von ${bookmarkMaxEntries} Lesezeichen erreicht. Möchten Sie das älteste Lesezeichen entfernen, um dieses hinzuzufügen?`,
          async () => {
            // Find the oldest bookmark (first in sorted order by ID/date)
            const bookmarksSorted = new Map(
              [...bookmarks].sort((a, b) => a[1].id.localeCompare(b[1].id))
            );
            const oldestId = bookmarksSorted.keys().next().value;
            bookmarks.delete(oldestId);
            if (debugMode)
              console.log(
                "DEBUG: Ältestes Lesezeichen " + oldestId + " entfernt."
              );

            bookmarks.set(comicId, { id: comicId, page, permalink, thumb });
            await storeBookmarks(bookmarks);
            setBookmarkButtonActive();
            if (debugMode)
              console.log(
                "DEBUG: Neues Lesezeichen für Comic-ID " +
                  comicId +
                  " hinzugefügt (nach Entfernung des ältesten)."
              );
            // If on bookmarks page, refresh the list
            if (document.getElementById("bookmarksPage")) {
              populateBookmarksPage(bookmarks);
              if (debugMode)
                console.log(
                  "DEBUG: Lesezeichen-Seite nach Hinzufügen aktualisiert."
                );
            }
          },
          () => {
            if (debugMode)
              console.log("DEBUG: Hinzufügen des Lesezeichens abgebrochen.");
            // User cancelled, do nothing
          }
        );
      } else {
        bookmarks.set(comicId, { id: comicId, page, permalink, thumb });
        await storeBookmarks(bookmarks);
        setBookmarkButtonActive();
        if (debugMode)
          console.log(
            "DEBUG: Lesezeichen für Comic-ID " + comicId + " hinzugefügt."
          );
        // If on bookmarks page, refresh the list
        if (document.getElementById("bookmarksPage")) {
          populateBookmarksPage(bookmarks);
          if (debugMode)
            console.log(
              "DEBUG: Lesezeichen-Seite nach Hinzufügen aktualisiert."
            );
        }
      }
    }
  }

  /**
   * Sets the bookmark button to active state.
   * Setzt den Lesezeichen-Button in den aktiven Zustand.
   */
  function setBookmarkButtonActive() {
    if (debugMode) console.log("DEBUG: setBookmarkButtonActive aufgerufen.");
    const bookmarkButton = document.getElementById(bookmarkButtonId);
    if (bookmarkButton) {
      bookmarkButton.classList.add(activeBookmarkClass);
      bookmarkButton.title = "Lesezeichen entfernt"; // German text
      if (debugMode)
        console.log("DEBUG: Lesezeichen-Button visuell als aktiv markiert.");
    } else {
      if (debugMode)
        console.log("DEBUG: Lesezeichen-Button nicht gefunden zum Aktivieren.");
    }
  }

  /**
   * Sets the bookmark button to inactive state.
   * Setzt den Lesezeichen-Button in den inaktiven Zustand.
   */
  function setBookmarkButtonInactive() {
    if (debugMode) console.log("DEBUG: setBookmarkButtonInactive aufgerufen.");
    const bookmarkButton = document.getElementById(bookmarkButtonId);
    if (bookmarkButton) {
      bookmarkButton.classList.remove(activeBookmarkClass);
      bookmarkButton.title = "Diese Seite mit Lesezeichen versehen"; // German text
      if (debugMode)
        console.log("DEBUG: Lesezeichen-Button visuell als inaktiv markiert.");
    } else {
      if (debugMode)
        console.log(
          "DEBUG: Lesezeichen-Button nicht gefunden zum Deaktivieren."
        );
    }
  }

  /**
   * Populates the bookmarks page with stored bookmarks.
   * Füllt die Lesezeichen-Seite mit gespeicherten Lesezeichen.
   * @param {Map<string, object>} bookmarkMap The map of bookmarks to display. Die Map der anzuzeigenden Lesezeichen.
   */
  function populateBookmarksPage(bookmarkMap) {
    if (debugMode)
      console.log("DEBUG: populateBookmarksPage aufgerufen mit:", bookmarkMap);
    const bookmarksSection = document.querySelector("#bookmarksWrapper");
    const noBookmarksTemplate = document.querySelector("#noBookmarks");
    const bookmarkWrapperTemplate = document.querySelector(
      "#pageBookmarkWrapper"
    );
    const pageBookmarkTemplate = document.querySelector("#pageBookmark");

    if (
      !bookmarksSection ||
      !noBookmarksTemplate ||
      !bookmarkWrapperTemplate ||
      !pageBookmarkTemplate
    ) {
      if (debugMode)
        console.error(
          "DEBUG: Eines oder mehrere erforderliche Elemente für Lesezeichen-Seite nicht gefunden."
        );
      return;
    }

    bookmarksSection.innerHTML = ""; // Clear existing content
    if (debugMode) console.log("DEBUG: Lesezeichen-Bereich geleert.");

    if (!bookmarkMap.size) {
      const noBookmarksElement = noBookmarksTemplate.content.cloneNode(true);
      bookmarksSection.appendChild(noBookmarksElement);
      if (debugMode)
        console.log("DEBUG: 'Keine Lesezeichen'-Nachricht angezeigt.");
      return;
    }

    const wrapper = bookmarkWrapperTemplate.content.cloneNode(true);
    // Append the wrapper to the main section first, so its elements are in the DOM
    bookmarksSection.appendChild(wrapper);
    if (debugMode) console.log("DEBUG: Lesezeichen-Wrapper hinzugefügt.");

    // Now query for the .chapter-links inside the appended wrapper
    const chapterLinksContainer =
      bookmarksSection.querySelector(".chapter-links");

    const bookmarksSorted = new Map(
      [...bookmarkMap].sort((a, b) => b[1].id.localeCompare(a[1].id))
    ); // Sort descending by ID (newest first)
    if (debugMode) console.log("DEBUG: Lesezeichen sortiert:", bookmarksSorted);

    bookmarksSorted.values().forEach((b) => {
      const bookmark = pageBookmarkTemplate.content.cloneNode(true);
      const link = bookmark.querySelector("a");
      const pageNum = bookmark.querySelector("span");
      const image = bookmark.querySelector("img");
      const deleteButton = pageNum ? pageNum.querySelector(".delete") : null; // Sicherstellen, dass pageNum existiert

      if (link) link.href = b.permalink;
      if (pageNum) {
        const pageNumTextNode = document.createTextNode(b.page || "");
        pageNum.prepend(pageNumTextNode); // Text vor dem Button einfügen
      }
      if (image) {
        image.src = b.thumb; // This is where the thumbnail URL is set
        image.alt = b.page || "Page";
      }

      // Add the 'loaded' class to the link element to trigger CSS styling
      // This will make the image visible and control the overlay visibility
      if (link) link.classList.add("loaded"); // <--- NEU HINZUGEFÜGT
      if (debugMode)
        console.log(
          "DEBUG: Lesezeichen-Element für ID " + b.id + " erstellt und geladen."
        );

      // Add event listener to the delete button within the cloned bookmark item
      if (deleteButton) {
        deleteButton.addEventListener("click", async (e) => {
          e.preventDefault();
          e.stopPropagation(); // Prevent the link from being followed
          if (debugMode)
            console.log(
              "DEBUG: Löschen-Button für Lesezeichen " + b.id + " geklickt."
            );
          await handleRemoveBookmarkById(b.id);
        });
      } else {
        if (debugMode)
          console.log(
            "DEBUG: Löschen-Button für Lesezeichen " + b.id + " nicht gefunden."
          );
      }

      // Append the individual bookmark item to the chapterLinksContainer
      if (chapterLinksContainer) {
        chapterLinksContainer.appendChild(bookmark);
      } else {
        if (debugMode)
          console.error(
            "DEBUG: chapterLinksContainer not found, cannot append bookmark for ID " +
              b.id +
              "."
          );
      }
    });

    const removeAllButton = document.getElementById("removeAll");
    const exportButton = document.getElementById("export");

    if (removeAllButton) removeAllButton.disabled = false;
    if (exportButton) exportButton.disabled = false;
    if (debugMode)
      console.log(
        "DEBUG: 'Alle entfernen' und 'Exportieren' Buttons aktiviert."
      );
  }

  /**
   * Handles removing a single bookmark by its ID.
   * Behandelt das Entfernen eines einzelnen Lesezeichens anhand seiner ID.
   * @param {string} id The ID of the bookmark to remove. Die ID des zu entfernenden Lesezeichens.
   */
  async function handleRemoveBookmarkById(id) {
    if (debugMode)
      console.log("DEBUG: handleRemoveBookmarkById aufgerufen für ID:", id);
    showCustomConfirm(
      "Möchten Sie dieses Lesezeichen wirklich entfernen?",
      async () => {
        const bookmarks = await getStoredBookmarks();
        bookmarks.delete(id);
        await storeBookmarks(bookmarks);
        populateBookmarksPage(bookmarks); // Refresh the display
        if (debugMode)
          console.log(
            "DEBUG: Lesezeichen " +
              id +
              " erfolgreich entfernt und Seite aktualisiert."
          );
      }
    );
  }

  /**
   * Handles removing all bookmarks.
   * Behandelt das Entfernen aller Lesezeichen.
   */
  async function handleRemoveAllBookmarks() {
    if (debugMode) console.log("DEBUG: handleRemoveAllBookmarks aufgerufen.");
    showCustomConfirm(
      "Möchten Sie wirklich alle Lesezeichen entfernen?",
      async () => {
        await storeBookmarks(new Map()); // Clear all bookmarks
        populateBookmarksPage(new Map()); // Refresh the display
        const removeAllButton = document.getElementById("removeAll");
        const exportButton = document.getElementById("export");
        if (removeAllButton) removeAllButton.disabled = true;
        if (exportButton) exportButton.disabled = true;
        if (debugMode)
          console.log(
            "DEBUG: Alle Lesezeichen entfernt und Buttons deaktiviert."
          );
      }
    );
  }

  /**
   * Handles exporting bookmarks to a JSON file.
   * Behandelt den Export von Lesezeichen in eine JSON-Datei.
   */
  async function handleExportBookmarks() {
    if (debugMode) console.log("DEBUG: handleExportBookmarks aufgerufen.");
    const bookmarks = await getStoredBookmarks();
    if (bookmarks.size === 0) {
      showCustomConfirm("Es gibt keine Lesezeichen zum Exportieren.", () => {
        if (debugMode)
          console.log(
            "DEBUG: Export abgebrochen, keine Lesezeichen vorhanden."
          );
      });
      return;
    }
    const dataStr = JSON.stringify(Array.from(bookmarks.entries()), null, 2);
    const blob = new Blob([dataStr], { type: "application/json" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = "twokinds_bookmarks.json";
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    if (debugMode) console.log("DEBUG: Lesezeichen erfolgreich exportiert.");
  }

  /**
   * Handles importing bookmarks from a JSON file.
   * Behandelt den Import von Lesezeichen aus einer JSON-Datei.
   * @param {Event} event The change event from the file input. Das Change-Event des Datei-Inputs.
   */
  async function handleImportBookmarks(event) {
    if (debugMode) console.log("DEBUG: handleImportBookmarks aufgerufen.");
    const file = event.target.files[0];
    if (!file) {
      if (debugMode)
        console.log("DEBUG: Keine Datei zum Importieren ausgewählt.");
      return;
    }

    const reader = new FileReader();
    reader.onload = async (e) => {
      try {
        const importedData = JSON.parse(e.target.result);
        if (!Array.isArray(importedData)) {
          throw new Error("Invalid JSON format. Expected an array.");
        }
        const newBookmarks = new Map(importedData);
        if (debugMode)
          console.log(
            "DEBUG: Importierte Lesezeichen erfolgreich geparst:",
            newBookmarks
          );

        // Optional: Merge with existing bookmarks or overwrite
        const currentBookmarks = await getStoredBookmarks();
        if (debugMode)
          console.log(
            "DEBUG: Aktuelle Lesezeichen vor dem Import-Merge:",
            currentBookmarks
          );

        newBookmarks.forEach((value, key) => {
          if (currentBookmarks.size < bookmarkMaxEntries) {
            currentBookmarks.set(key, value);
          } else {
            // If max entries reached, replace oldest with new one
            const bookmarksSorted = new Map(
              [...currentBookmarks].sort((a, b) =>
                a[1].id.localeCompare(b[1].id)
              )
            );
            const oldestId = bookmarksSorted.keys().next().value;
            currentBookmarks.delete(oldestId);
            currentBookmarks.set(key, value);
            if (debugMode)
              console.log(
                "DEBUG: Max. Lesezeichen erreicht, ältestes (" +
                  oldestId +
                  ") ersetzt durch neues (" +
                  key +
                  ")."
              );
          }
        });

        await storeBookmarks(currentBookmarks);
        populateBookmarksPage(currentBookmarks);
        showCustomConfirm("Lesezeichen erfolgreich importiert!", () => {
          if (debugMode) console.log("DEBUG: Lesezeichen-Import bestätigt.");
        });
      } catch (error) {
        console.error("Error importing bookmarks:", error);
        if (debugMode)
          console.error(
            "DEBUG: Fehler beim Importieren der Lesezeichen:",
            error
          );
        showCustomConfirm(
          "Fehler beim Importieren der Lesezeichen. Bitte stellen Sie sicher, dass die Datei ein gültiges JSON-Format hat.",
          () => {
            if (debugMode)
              console.log("DEBUG: Lesezeichen-Importfehler bestätigt.");
          }
        );
      }
    };
    reader.readAsText(file);
  }

  /**
   * Displays a custom confirmation modal.
   * Zeigt ein benutzerdefiniertes Bestätigungsmodal an.
   * @param {string} message The message to display. Die anzuzeigende Nachricht.
   * @param {function} onConfirm Callback function if user confirms. Callback-Funktion, wenn der Benutzer bestätigt.
   * @param {function} onCancel Callback function if user cancels. Callback-Funktion, wenn der Benutzer abbricht.
   */
  function showCustomConfirm(message, onConfirm, onCancel = () => {}) {
    if (debugMode)
      console.log(
        "DEBUG: showCustomConfirm aufgerufen mit Nachricht:",
        message
      );
    const modal = document.getElementById("customConfirmModal");
    const confirmMessage = document.getElementById("confirmMessage");
    const confirmYes = document.getElementById("confirmYes");
    const confirmNo = document.getElementById("confirmNo");

    if (!modal || !confirmMessage || !confirmYes || !confirmNo) {
      console.error(
        "Custom confirmation modal elements not found. Falling back to alert/confirm."
      );
      if (debugMode)
        console.error(
          "DEBUG: Bestätigungsmodal-Elemente nicht gefunden, Fallback auf alert/confirm."
        );
      if (confirm(message)) {
        onConfirm();
      } else {
        onCancel();
      }
      return;
    }

    confirmMessage.textContent = message;
    modal.style.display = "block"; // Show the modal
    if (debugMode) console.log("DEBUG: Bestätigungsmodal angezeigt.");

    const handleYes = () => {
      if (debugMode) console.log("DEBUG: Bestätigungsmodal: Ja geklickt.");
      onConfirm();
      modal.style.display = "none";
      confirmYes.removeEventListener("click", handleYes);
      confirmNo.removeEventListener("click", handleNo);
    };

    const handleNo = () => {
      if (debugMode) console.log("DEBUG: Bestätigungsmodal: Nein geklickt.");
      onCancel();
      modal.style.display = "none";
      confirmYes.removeEventListener("click", handleYes);
      confirmNo.removeEventListener("click", handleNo);
    };

    confirmYes.addEventListener("click", handleYes);
    confirmNo.addEventListener("click", handleNo);
    if (debugMode)
      console.log(
        "DEBUG: Event-Listener für Bestätigungsmodal-Buttons hinzugefügt."
      );
  }
})();
