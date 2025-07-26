(() => {
  const bookmarkButtonId = "add-bookmark";
  const activeBookmarkClass = "bookmarked";
  const bookmarkMaxEntries = 50;

  document.addEventListener("DOMContentLoaded", async () => {
    // Bind the left and right arrow keys and j/k to comic navigation
    var body = document.getElementsByTagName("body")[0];
    var navprev = document.querySelector("a.navprev");
    var navnext = document.querySelector("a.navnext");
    body.addEventListener("keyup", (e) => {
      if ((e.key == "ArrowLeft" || e.key == "j" || e.key == "J") && navprev) {
        parent.location = navprev.getAttribute("href");
      } else if (
        (e.key == "ArrowRight" || e.key == "k" || e.key == "k") &&
        navnext
      ) {
        parent.location = navnext.getAttribute("href");
      }
    });

    const bookmarkButton = document.getElementById(bookmarkButtonId);
    const bookmarks = await getStoredBookmarks();

    // Logic for the individual comic page bookmark button
    if (bookmarkButton) {
      bookmarkButton.addEventListener("click", async (e) => {
        await toggleBookmark(e.target);
      });
      if (bookmarks.has(bookmarkButton.dataset.id)) {
        setBookmarkButtonActive();
      }
    }
    // Logic for the bookmarks page (lesezeichen.php)
    else if (document.getElementById("bookmarksPage")) {
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
          document.getElementById("import").click();
        });
      document
        .getElementById("import")
        .addEventListener("change", handleImportBookmarks);
    }
  });

  /**
   * Retrieves stored bookmarks from local storage.
   * @returns {Promise<Map<string, object>>} A map of bookmarks.
   */
  async function getStoredBookmarks() {
    if (typeof window.localStorage == "undefined") {
      return new Map();
    }
    try {
      const stored = window.localStorage.getItem("comicBookmarks");
      return stored ? new Map(JSON.parse(stored)) : new Map();
    } catch (e) {
      console.error("Error parsing bookmarks from localStorage:", e);
      return new Map();
    }
  }

  /**
   * Stores bookmarks in local storage.
   * @param {Map<string, object>} bookmarkMap The map of bookmarks to store.
   * @returns {Promise<void>}
   */
  async function storeBookmarks(bookmarkMap) {
    if (typeof window.localStorage == "undefined") {
      return;
    }
    try {
      window.localStorage.setItem(
        "comicBookmarks",
        JSON.stringify(Array.from(bookmarkMap.entries()))
      );
    } catch (e) {
      console.error("Error storing bookmarks to localStorage:", e);
    }
  }

  /**
   * Toggles a bookmark for the current page.
   * @param {HTMLElement} button The bookmark button element.
   * @returns {Promise<void>}
   */
  async function toggleBookmark(button) {
    const comicId = button.dataset.id;
    const page = button.dataset.page;
    const permalink = button.dataset.permalink;
    const thumb = button.dataset.thumb;

    const bookmarks = await getStoredBookmarks();

    if (bookmarks.has(comicId)) {
      // Remove bookmark
      showCustomConfirm(
        "Möchten Sie dieses Lesezeichen wirklich entfernen?",
        async () => {
          bookmarks.delete(comicId);
          await storeBookmarks(bookmarks);
          setBookmarkButtonInactive();
          // If on bookmarks page, refresh the list
          if (document.getElementById("bookmarksPage")) {
            populateBookmarksPage(bookmarks);
          }
        }
      );
    } else {
      // Add bookmark
      if (bookmarks.size >= bookmarkMaxEntries) {
        showCustomConfirm(
          `Sie haben die maximale Anzahl von ${bookmarkMaxEntries} Lesezeichen erreicht. Möchten Sie das älteste Lesezeichen entfernen, um dieses hinzuzufügen?`,
          async () => {
            // Find the oldest bookmark (first in sorted order by ID/date)
            const bookmarksSorted = new Map(
              [...bookmarks].sort((a, b) => a[1].id.localeCompare(b[1].id))
            );
            const oldestId = bookmarksSorted.keys().next().value;
            bookmarks.delete(oldestId);

            bookmarks.set(comicId, { id: comicId, page, permalink, thumb });
            await storeBookmarks(bookmarks);
            setBookmarkButtonActive();
            // If on bookmarks page, refresh the list
            if (document.getElementById("bookmarksPage")) {
              populateBookmarksPage(bookmarks);
            }
          },
          () => {
            // User cancelled, do nothing
          }
        );
      } else {
        bookmarks.set(comicId, { id: comicId, page, permalink, thumb });
        await storeBookmarks(bookmarks);
        setBookmarkButtonActive();
        // If on bookmarks page, refresh the list
        if (document.getElementById("bookmarksPage")) {
          populateBookmarksPage(bookmarks);
        }
      }
    }
  }

  /**
   * Sets the bookmark button to active state.
   */
  function setBookmarkButtonActive() {
    const bookmarkButton = document.getElementById(bookmarkButtonId);
    if (bookmarkButton) {
      bookmarkButton.classList.add(activeBookmarkClass);
      bookmarkButton.title = "Lesezeichen entfernt"; // German text
    }
  }

  /**
   * Sets the bookmark button to inactive state.
   */
  function setBookmarkButtonInactive() {
    const bookmarkButton = document.getElementById(bookmarkButtonId);
    if (bookmarkButton) {
      bookmarkButton.classList.remove(activeBookmarkClass);
      bookmarkButton.title = "Diese Seite mit Lesezeichen versehen"; // German text
    }
  }

  /**
   * Populates the bookmarks page with stored bookmarks.
   * @param {Map<string, object>} bookmarkMap The map of bookmarks to display.
   */
  function populateBookmarksPage(bookmarkMap) {
    const bookmarksSection = document.querySelector("#bookmarksWrapper");
    const noBookmarksTemplate = document.querySelector("#noBookmarks");
    const bookmarkWrapperTemplate = document.querySelector(
      "#pageBookmarkWrapper"
    );
    const pageBookmarkTemplate = document.querySelector("#pageBookmark");
    bookmarksSection.innerHTML = ""; // Clear existing content

    if (!bookmarkMap.size) {
      const noBookmarksElement = noBookmarksTemplate.content.cloneNode(true);
      bookmarksSection.appendChild(noBookmarksElement);
      return;
    }

    const wrapper = bookmarkWrapperTemplate.content.cloneNode(true);
    // Append the wrapper to the main section first, so its elements are in the DOM
    bookmarksSection.appendChild(wrapper);

    // Now query for the .chapter-links inside the appended wrapper
    const chapterLinksContainer =
      bookmarksSection.querySelector(".chapter-links");

    // === NEU HINZUGEFÜGT: Füge die Klasse 'tag-page-links' hinzu ===
    if (chapterLinksContainer) {
      chapterLinksContainer.classList.add("tag-page-links");
    }
    // =============================================================

    const bookmarksSorted = new Map(
      [...bookmarkMap].sort((a, b) => b[1].id.localeCompare(a[1].id))
    ); // Sort descending by ID (newest first)

    bookmarksSorted.values().forEach((b) => {
      const bookmark = pageBookmarkTemplate.content.cloneNode(true);
      const link = bookmark.querySelector("a");
      link.href = b.permalink;
      const pageNum = bookmark.querySelector("span");
      const pageNumTextNode = document.createTextNode(b.page || "");
      pageNum.appendChild(pageNumTextNode);
      const image = bookmark.querySelector("img");
      image.src = b.thumb; // This is where the thumbnail URL is set
      image.alt = b.page || "Page";

      // Add the 'loaded' class to the link element to trigger CSS styling
      // This will make the image visible and control the overlay visibility
      link.classList.add("loaded"); // <--- NEU HINZUGEFÜGT

      // Add event listener to the delete button within the cloned bookmark item
      // The delete button is inside the span, which is a child of the link
      pageNum.querySelector(".delete").addEventListener("click", async (e) => {
        e.preventDefault();
        e.stopPropagation(); // Prevent the link from being followed
        await handleRemoveBookmarkById(b.id);
      });

      // Append the individual bookmark item to the chapterLinksContainer
      if (chapterLinksContainer) {
        chapterLinksContainer.appendChild(bookmark);
      } else {
        console.error(
          "chapterLinksContainer not found, cannot append bookmark."
        );
      }
    });

    document.getElementById("removeAll").disabled = false;
    document.getElementById("export").disabled = false;
  }

  /**
   * Handles removing a single bookmark by its ID.
   * @param {string} id The ID of the bookmark to remove.
   */
  async function handleRemoveBookmarkById(id) {
    showCustomConfirm(
      "Möchten Sie dieses Lesezeichen wirklich entfernen?",
      async () => {
        const bookmarks = await getStoredBookmarks();
        bookmarks.delete(id);
        await storeBookmarks(bookmarks);
        populateBookmarksPage(bookmarks); // Refresh the display
      }
    );
  }

  /**
   * Handles removing all bookmarks.
   */
  async function handleRemoveAllBookmarks() {
    showCustomConfirm(
      "Möchten Sie wirklich alle Lesezeichen entfernen?",
      async () => {
        await storeBookmarks(new Map()); // Clear all bookmarks
        populateBookmarksPage(new Map()); // Refresh the display
        document.getElementById("removeAll").disabled = true;
        document.getElementById("export").disabled = true;
      }
    );
  }

  /**
   * Handles exporting bookmarks to a JSON file.
   */
  async function handleExportBookmarks() {
    const bookmarks = await getStoredBookmarks();
    if (bookmarks.size === 0) {
      showCustomConfirm("Es gibt keine Lesezeichen zum Exportieren.", () => {});
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
  }

  /**
   * Handles importing bookmarks from a JSON file.
   * @param {Event} event The change event from the file input.
   */
  async function handleImportBookmarks(event) {
    const file = event.target.files[0];
    if (!file) {
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

        // Optional: Merge with existing bookmarks or overwrite
        const currentBookmarks = await getStoredBookmarks();
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
          }
        });

        await storeBookmarks(currentBookmarks);
        populateBookmarksPage(currentBookmarks);
        showCustomConfirm("Lesezeichen erfolgreich importiert!", () => {});
      } catch (error) {
        console.error("Error importing bookmarks:", error);
        showCustomConfirm(
          "Fehler beim Importieren der Lesezeichen. Bitte stellen Sie sicher, dass die Datei ein gültiges JSON-Format hat.",
          () => {}
        );
      }
    };
    reader.readAsText(file);
  }

  /**
   * Displays a custom confirmation modal.
   * @param {string} message The message to display.
   * @param {function} onConfirm Callback function if user confirms.
   * @param {function} onCancel Callback function if user cancels.
   */
  function showCustomConfirm(message, onConfirm, onCancel = () => {}) {
    const modal = document.getElementById("customConfirmModal");
    const confirmMessage = document.getElementById("confirmMessage");
    const confirmYes = document.getElementById("confirmYes");
    const confirmNo = document.getElementById("confirmNo");

    if (!modal || !confirmMessage || !confirmYes || !confirmNo) {
      console.error(
        "Custom confirmation modal elements not found. Falling back to alert/confirm."
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

    const handleYes = () => {
      onConfirm();
      modal.style.display = "none";
      confirmYes.removeEventListener("click", handleYes);
      confirmNo.removeEventListener("click", handleNo);
    };

    const handleNo = () => {
      onCancel();
      modal.style.display = "none";
      confirmYes.removeEventListener("click", handleYes);
      confirmNo.removeEventListener("click", handleNo);
    };

    confirmYes.addEventListener("click", handleYes);
    confirmNo.addEventListener("click", handleNo);
  }
})();
