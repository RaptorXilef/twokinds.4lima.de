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
        .getElementById("importButton")
        .addEventListener("click", () =>
          document.getElementById("import").click()
        ); // Trigger file input click
      document
        .getElementById("import")
        .addEventListener("change", handleImportBookmarks);
    }
  });

  /**
   * Toggles the bookmark status for the current comic page.
   * @param {HTMLElement} button The bookmark button element.
   */
  async function toggleBookmark(button) {
    const comicId = button.dataset.id;
    const comicName = button.dataset.name;
    const comicType = button.dataset.type;
    const comicChapter = button.dataset.chapter;
    const comicLink = window.location.href; // Get the current page URL
    const comicThumb = button.dataset.thumb; // Thumbnail URL from data-thumb

    let bookmarks = await getStoredBookmarks();

    if (bookmarks.has(comicId)) {
      // Remove bookmark
      bookmarks.delete(comicId);
      setBookmarkButtonInactive();
    } else {
      // Add bookmark
      if (bookmarks.size >= bookmarkMaxEntries) {
        alert(
          "Du hast die maximale Anzahl von Lesezeichen erreicht (" +
            bookmarkMaxEntries +
            "). Bitte entferne zuerst einige, bevor du neue hinzufügst."
        );
        return;
      }
      bookmarks.set(comicId, {
        id: comicId,
        name: comicName,
        type: comicType,
        chapter: comicChapter,
        link: comicLink,
        thumb: comicThumb, // Store the thumbnail URL
      });
      setBookmarkButtonActive();
    }
    await storeBookmarks(bookmarks);
  }

  /**
   * Sets the bookmark button to active state (e.g., changes color).
   */
  function setBookmarkButtonActive() {
    const bookmarkButton = document.getElementById(bookmarkButtonId);
    if (bookmarkButton) {
      bookmarkButton.classList.add(activeBookmarkClass);
      bookmarkButton.title = "Lesezeichen entfernen";
    }
  }

  /**
   * Sets the bookmark button to inactive state.
   */
  function setBookmarkButtonInactive() {
    const bookmarkButton = document.getElementById(bookmarkButtonId);
    if (bookmarkButton) {
      bookmarkButton.classList.remove(activeBookmarkClass);
      bookmarkButton.title = "Lesezeichen hinzufügen";
    }
  }

  /**
   * Retrieves stored bookmarks from localStorage.
   * @returns {Promise<Map<string, Object>>} A map of bookmarks.
   */
  async function getStoredBookmarks() {
    return new Promise((resolve) => {
      if (typeof window.localStorage !== "undefined") {
        try {
          const stored = window.localStorage.getItem("comicBookmarks");
          if (stored) {
            // Convert plain object back to Map
            resolve(new Map(Object.entries(JSON.parse(stored))));
          }
        } catch (e) {
          console.error(
            "Fehler beim Laden der Lesezeichen aus localStorage:",
            e
          );
        }
      }
      resolve(new Map()); // Return empty map if no localStorage or error
    });
  }

  /**
   * Stores bookmarks to localStorage.
   * @param {Map<string, Object>} bookmarks The map of bookmarks to store.
   * @returns {Promise<void>}
   */
  async function storeBookmarks(bookmarks) {
    return new Promise((resolve) => {
      if (typeof window.localStorage !== "undefined") {
        try {
          // Convert Map to plain object for localStorage storage
          window.localStorage.setItem(
            "comicBookmarks",
            JSON.stringify(Object.fromEntries(bookmarks))
          );
        } catch (e) {
          console.error(
            "Fehler beim Speichern der Lesezeichen in localStorage:",
            e
          );
        }
      }
      resolve();
    });
  }

  /**
   * Populates the bookmarks page with stored bookmarks.
   * @param {Map<string, Object>} bookmarkMap The map of bookmarks to display.
   */
  function populateBookmarksPage(bookmarkMap) {
    const bookmarksSection = document.querySelector("#bookmarksWrapper"); // This is the main container
    const noBookmarksTemplate = document.querySelector("#noBookmarks");
    const pageBookmarkWrapperTemplate = document.querySelector(
      "#pageBookmarkWrapper"
    ); // Get the wrapper template
    const pageBookmarkTemplate = document.querySelector("#pageBookmark");

    // Clear existing content
    bookmarksSection.innerHTML = "";

    if (bookmarkMap.size === 0) {
      const noBookmarksElement = noBookmarksTemplate.content.cloneNode(true);
      bookmarksSection.appendChild(noBookmarksElement);
      // Disable action buttons if no bookmarks
      document.getElementById("removeAll").disabled = true;
      document.getElementById("export").disabled = true;
      return;
    }

    // Append the wrapper template's content to the bookmarksSection
    // This ensures .chapter-links exists before trying to query it
    const wrapperContent = pageBookmarkWrapperTemplate.content.cloneNode(true);
    bookmarksSection.appendChild(wrapperContent);

    // Now, get the actual .chapter-links element that was just added to the DOM
    const chapterLinksContainer =
      bookmarksSection.querySelector(".chapter-links");
    if (!chapterLinksContainer) {
      console.error(
        "Fehler: .chapter-links Container wurde nicht gefunden, obwohl das Wrapper-Template hinzugefügt wurde."
      );
      return; // Exit if the container is still not found
    }

    // Sort bookmarks by ID (comic date) for consistent display
    const bookmarksSorted = new Map(
      [...bookmarkMap].sort((a, b) => a[1].id.localeCompare(b[1].id))
    );

    bookmarksSorted.values().forEach((b) => {
      const bookmark = pageBookmarkTemplate.content.cloneNode(true);
      const link = bookmark.querySelector("a");
      link.href = b.link;

      const image = bookmark.querySelector("img");
      image.src =
        b.thumb || "https://placehold.co/96x96/cccccc/333333?text=No+Thumb"; // Fallback thumbnail
      image.alt = b.name || "Comic Page";

      const pageNum = bookmark.querySelector("span");
      pageNum.textContent = b.name || b.id; // Display comic name or ID

      const deleteButton = bookmark.querySelector(".delete");
      deleteButton.addEventListener("click", async (e) => {
        e.preventDefault();
        e.stopPropagation(); // Prevent the link from being followed
        // Use custom confirmation instead of alert/confirm
        showCustomConfirm(
          "Möchten Sie dieses Lesezeichen wirklich entfernen?",
          async () => {
            await handleRemoveBookmarkById(b.id);
            // Re-populate the page after deletion
            populateBookmarksPage(await getStoredBookmarks());
          }
        );
      });

      chapterLinksContainer.appendChild(bookmark); // Append to the actual container
    });

    // Enable action buttons if bookmarks exist
    document.getElementById("removeAll").disabled = false;
    document.getElementById("export").disabled = false;
  }

  /**
   * Handles removing a single bookmark by its ID.
   * @param {string} id The ID of the bookmark to remove.
   */
  async function handleRemoveBookmarkById(id) {
    let bookmarks = await getStoredBookmarks();
    if (bookmarks.has(id)) {
      bookmarks.delete(id);
      await storeBookmarks(bookmarks);
      // Re-populate the page to reflect the change
      populateBookmarksPage(bookmarks);
    }
  }

  /**
   * Handles removing all bookmarks.
   */
  async function handleRemoveAllBookmarks() {
    showCustomConfirm(
      "Möchten Sie wirklich ALLE Lesezeichen entfernen?",
      async () => {
        await storeBookmarks(new Map()); // Store an empty map
        populateBookmarksPage(new Map()); // Update the display
      }
    );
  }

  /**
   * Handles exporting bookmarks to a JSON file.
   */
  async function handleExportBookmarks() {
    const bookmarks = await getStoredBookmarks();
    const dataStr = JSON.stringify(Object.fromEntries(bookmarks), null, 2);
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
        // Convert imported object back to Map
        let newBookmarks = new Map(Object.entries(importedData));
        await storeBookmarks(newBookmarks);
        populateBookmarksPage(newBookmarks);
        // Using the custom confirm modal for alerts too
        showCustomConfirm("Lesezeichen erfolgreich importiert!", () => {});
      } catch (error) {
        console.error("Fehler beim Importieren der Lesezeichen:", error);
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
   */
  function showCustomConfirm(message, onConfirm) {
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
      modal.style.display = "none";
      confirmYes.removeEventListener("click", handleYes);
      confirmNo.removeEventListener("click", handleNo);
    };

    confirmYes.addEventListener("click", handleYes);
    confirmNo.addEventListener("click", handleNo);
  }
})();
