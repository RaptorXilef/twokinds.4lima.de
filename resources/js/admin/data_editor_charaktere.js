/**
 * V2.1: Behebt einen Fehler, bei dem der falsche ID-Selektor für die
 * Nachrichten-Box verwendet wurde, was zu einem Fehler beim Speichern führte.
 */
document.addEventListener("DOMContentLoaded", () => {
  // Globale Variablen und DOM-Elemente
  let characterData = window.characterData || {};
  const editorContainer = document.getElementById("character-editor-container");
  const messageBox = document.getElementById("message-box");
  const lastRunContainer = document.getElementById("last-run-container");

  // Modal-Elemente
  const modal = document.getElementById("edit-modal");
  const modalTitle = document.getElementById("modal-title");
  const modalCloseBtn = modal.querySelector(".close-button");
  const editForm = document.getElementById("edit-form");

  // Formular-Felder
  const modalGroupSelect = document.getElementById("modal-group");
  const modalNewGroupInput = document.getElementById("modal-new-group");
  const modalNameInput = document.getElementById("modal-name");
  const modalPicUrlInput = document.getElementById("modal-pic-url");
  const modalImagePreview = document.getElementById("modal-image-preview");

  let activeEditKey = null;
  let activeEditGroup = null;

  function showMessage(message, type = "success") {
    if (!messageBox) return;
    messageBox.textContent = message;
    messageBox.className = `status-message status-${
      type === "success" ? "green" : "red"
    }`;
    messageBox.style.display = "block";

    setTimeout(() => {
      messageBox.style.display = "none";
    }, 5000);
  }

  function updateLastSavedTimestamp() {
    if (lastRunContainer) {
      const now = new Date();
      const day = String(now.getDate()).padStart(2, "0");
      const month = String(now.getMonth() + 1).padStart(2, "0");
      const year = now.getFullYear();
      const hours = String(now.getHours()).padStart(2, "0");
      const minutes = String(now.getMinutes()).padStart(2, "0");
      const seconds = String(now.getSeconds()).padStart(2, "0");
      const formattedDate = `${day}.${month}.${year} um ${hours}:${minutes}:${seconds}`;
      lastRunContainer.innerHTML = `<p class="status-message status-info">Letzte Speicherung am ${formattedDate} Uhr.</p>`;
    }
  }

  function initSortable() {
    editorContainer
      .querySelectorAll(".character-list-sortable")
      .forEach((list) => {
        new Sortable(list, {
          animation: 150,
          ghostClass: "sortable-ghost",
          onEnd: () => {
            const groupName = list.dataset.group;
            const newKeyOrder = Array.from(list.children).map(
              (el) => el.dataset.key
            );
            const originalGroup = characterData[groupName];
            const newOrderedGroup = {};
            newKeyOrder.forEach((key) => {
              if (originalGroup[key]) {
                newOrderedGroup[key] = originalGroup[key];
              }
            });
            characterData[groupName] = newOrderedGroup;
          },
        });
      });
  }

  function renderEditor() {
    editorContainer.innerHTML = "";
    if (Object.keys(characterData).length === 0) {
      editorContainer.innerHTML = "<p>Keine Charakter-Gruppen gefunden.</p>";
      return;
    }

    for (const groupName in characterData) {
      const group = characterData[groupName];
      const groupContainer = document.createElement("div");
      groupContainer.className = "character-group";
      let listHTML = "";

      for (const charKey in group) {
        const character = group[charKey];
        const picUrl = character.charaktere_pic_url || "";

        let imgSrc;
        let imgClass = "character-image";
        if (!picUrl) {
          imgSrc =
            "https://placehold.co/50x50/cccccc/333333?text=Bild\\nnicht\\ndefiniert";
        } else {
          imgSrc = `${window.baseUrl}${picUrl}`;
          imgClass += " character-image-live";
        }

        listHTML += `
                    <div class="character-entry" data-key="${charKey}" data-group="${groupName}">
                        <img src="${imgSrc}" alt="${charKey}" class="${imgClass}">
                        <div class="character-info">
                            <strong>${charKey.replace(/_/g, " ")}</strong>
                            <p>${picUrl || "Kein Bildpfad angegeben"}</p>
                        </div>
                        <div class="character-actions">
                            <button class="button edit-btn">Bearbeiten</button>
                            <button class="button delete-button delete-btn">Löschen</button>
                        </div>
                    </div>`;
      }

      groupContainer.innerHTML = `<h3>${groupName}</h3>`;
      const sortableList = document.createElement("div");
      sortableList.className = "character-list-sortable";
      sortableList.dataset.group = groupName;
      sortableList.innerHTML =
        listHTML || '<p style="padding: 10px;">Diese Gruppe ist leer.</p>';
      groupContainer.appendChild(sortableList);
      editorContainer.appendChild(groupContainer);
    }
    initSortable();
  }

  function openModal(charKey = null, groupName = null) {
    editForm.reset();
    activeEditKey = charKey;
    activeEditGroup = groupName;
    modalGroupSelect.innerHTML = "";
    Object.keys(characterData).forEach((group) => {
      const option = document.createElement("option");
      option.value = group;
      option.textContent = group;
      modalGroupSelect.appendChild(option);
    });
    if (charKey && groupName) {
      modalTitle.textContent = "Charakter bearbeiten";
      modalNameInput.readOnly = true;
      const character = characterData[groupName][charKey];
      modalGroupSelect.value = groupName;
      modalNameInput.value = charKey;
      modalPicUrlInput.value = character.charaktere_pic_url || "";
    } else {
      modalTitle.textContent = "Neuen Charakter hinzufügen";
      modalNameInput.readOnly = false;
    }
    updateImagePreview();
    modal.style.display = "block";
  }

  function updateImagePreview() {
    const picUrl = modalPicUrlInput.value;
    modalImagePreview.src = picUrl
      ? `${window.baseUrl}${picUrl}`
      : "https://placehold.co/100x100/cccccc/333333?text=Bild\\nnicht\\ndefiniert";
  }

  editorContainer.addEventListener("click", (e) => {
    const entry = e.target.closest(".character-entry");
    if (!entry) return;
    const charKey = entry.dataset.key;
    const groupName = entry.dataset.group;
    if (e.target.classList.contains("edit-btn")) {
      openModal(charKey, groupName);
    } else if (e.target.classList.contains("delete-btn")) {
      if (confirm(`Sind Sie sicher, dass Sie "${charKey}" löschen möchten?`)) {
        delete characterData[groupName][charKey];
        renderEditor();
      }
    }
  });

  editorContainer.addEventListener(
    "error",
    (e) => {
      if (
        e.target?.tagName === "IMG" &&
        e.target.classList.contains("character-image-live")
      ) {
        e.target.onerror = null;
        e.target.src =
          "https://placehold.co/50x50/cccccc/333333?text=Bild\\nFehlt";
        e.target.classList.remove("character-image-live");
      }
    },
    true
  );

  document
    .querySelectorAll(".add-character-btn")
    .forEach((btn) => btn.addEventListener("click", () => openModal()));
  document
    .getElementById("save-all-btn")
    .addEventListener("click", async () => {
      try {
        const response = await fetch(window.location.href, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-CSRF-Token": window.csrfToken,
          },
          body: JSON.stringify(characterData),
        });
        const result = await response.json();
        if (result.success) {
          showMessage(result.message, "success");
          updateLastSavedTimestamp();
        } else {
          showMessage(
            result.message || "Ein unbekannter Fehler ist aufgetreten.",
            "error"
          );
          if (result.message.includes("CSRF")) {
            setTimeout(() => window.location.reload(), 3000);
          }
        }
      } catch (error) {
        showMessage(`Speichern fehlgeschlagen: ${error.message}`, "error");
      }
    });

  modalCloseBtn.addEventListener("click", () => (modal.style.display = "none"));
  document
    .getElementById("modal-cancel-btn")
    .addEventListener("click", () => (modal.style.display = "none"));
  modalPicUrlInput.addEventListener("input", updateImagePreview);
  modalImagePreview.addEventListener("error", function () {
    this.onerror = null;
    this.src = "https://placehold.co/100x100/cccccc/333333?text=Bild\\nFehlt";
  });

  editForm.addEventListener("submit", (e) => {
    e.preventDefault();
    const name = modalNameInput.value.trim().replace(/\s+/g, "_");
    if (!name) return alert("Der Charakter-Name darf nicht leer sein.");
    let group = modalNewGroupInput.value.trim() || modalGroupSelect.value;
    if (!group)
      return alert("Bitte eine Gruppe auswählen oder eine neue erstellen.");

    if (!characterData[group]) characterData[group] = {};

    const characterDetails = {
      charaktere_pic_url: modalPicUrlInput.value.trim(),
    };

    if (activeEditKey && activeEditKey !== name) {
      alert(
        "Der Name eines existierenden Charakters kann nicht geändert werden."
      );
      return;
    }

    if (activeEditKey && group !== activeEditGroup) {
      delete characterData[activeEditGroup][activeEditKey];
    }

    characterData[group][name] = characterDetails;

    modal.style.display = "none";
    renderEditor();
  });

  renderEditor();
});
