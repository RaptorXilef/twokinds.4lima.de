document.addEventListener("DOMContentLoaded", () => {
  // Globale Variablen und DOM-Elemente
  let characterData = window.characterData || {};
  const editorContainer = document.getElementById("character-editor-container");
  const messageContainer = document.getElementById("message-container");

  // Modal-Elemente
  const modal = document.getElementById("edit-modal");
  const modalTitle = document.getElementById("modal-title");
  const modalCloseBtn = modal.querySelector(".close-button");
  const modalSaveBtn = document.getElementById("modal-save-btn");
  const modalCancelBtn = document.getElementById("modal-cancel-btn");
  const editForm = document.getElementById("edit-form");

  // Formular-Felder
  const modalGroupSelect = document.getElementById("modal-group");
  const modalNewGroupInput = document.getElementById("modal-new-group");
  const modalNameInput = document.getElementById("modal-name");
  const modalPicUrlInput = document.getElementById("modal-pic-url");
  const modalImagePreview = document.getElementById("modal-image-preview");

  let activeEditKey = null; // Speichert den Schlüssel des zu bearbeitenden Charakters
  let activeEditGroup = null; // Speichert die Gruppe des zu bearbeitenden Charakters

  /**
   * Zeigt eine Statusnachricht an.
   * @param {string} message - Die anzuzeigende Nachricht.
   * @param {string} type - 'success' (grün) oder 'error' (rot).
   */
  function showMessage(message, type = "success") {
    messageContainer.innerHTML = `<div class="message message-${
      type === "success" ? "green" : "red"
    }"><p>${message}</p></div>`;
    setTimeout(() => {
      messageContainer.innerHTML = "";
    }, 5000);
  }

  /**
   * Rendert den gesamten Editor-Inhalt basierend auf den aktuellen characterData.
   */
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

      let groupHTML = `<h3>${groupName}</h3>`;

      if (Object.keys(group).length === 0) {
        groupHTML +=
          '<p style="padding: 10px;">Diese Gruppe enthält keine Charaktere.</p>';
      } else {
        for (const charKey in group) {
          const character = group[charKey];
          const picUrl = character.charaktere_pic_url || "";
          const fullPicUrl = picUrl
            ? `${window.baseUrl}${picUrl}`
            : "https://placehold.co/50x50/cccccc/333333?text=?";

          groupHTML += `
                        <div class="character-entry" data-key="${charKey}" data-group="${groupName}">
                            <img src="${fullPicUrl}" alt="${charKey}" onerror="this.src='https://placehold.co/50x50/cccccc/333333?text=Error'">
                            <div class="character-info">
                                <strong>${charKey.replace(/_/g, " ")}</strong>
                                <p>${picUrl || "Kein Bildpfad angegeben"}</p>
                            </div>
                            <div class="character-actions">
                                <button class="button edit-btn">Bearbeiten</button>
                                <button class="button delete-button delete-btn">Löschen</button>
                            </div>
                        </div>
                    `;
        }
      }
      groupContainer.innerHTML = groupHTML;
      editorContainer.appendChild(groupContainer);
    }
  }

  /**
   * Öffnet und konfiguriert das Bearbeitungs-Modal.
   * @param {string|null} charKey - Der Schlüssel des Charakters oder null für einen neuen Charakter.
   * @param {string|null} groupName - Der Name der Gruppe.
   */
  function openModal(charKey = null, groupName = null) {
    editForm.reset();
    activeEditKey = charKey;
    activeEditGroup = groupName;

    // Gruppen-Dropdown füllen
    modalGroupSelect.innerHTML = "";
    Object.keys(characterData).forEach((group) => {
      const option = document.createElement("option");
      option.value = group;
      option.textContent = group;
      modalGroupSelect.appendChild(option);
    });

    if (charKey && groupName) {
      // Bearbeiten-Modus
      modalTitle.textContent = "Charakter bearbeiten";
      modalNameInput.readOnly = true; // Den Schlüssel sollte man nicht ändern

      const character = characterData[groupName][charKey];
      modalGroupSelect.value = groupName;
      modalNameInput.value = charKey;
      modalPicUrlInput.value = character.charaktere_pic_url || "";
    } else {
      // Hinzufügen-Modus
      modalTitle.textContent = "Neuen Charakter hinzufügen";
      modalNameInput.readOnly = false;
    }

    updateImagePreview();
    modal.style.display = "block";
  }

  /**
   * Aktualisiert die Bildvorschau im Modal.
   */
  function updateImagePreview() {
    const picUrl = modalPicUrlInput.value;
    if (picUrl) {
      modalImagePreview.src = `${window.baseUrl}${picUrl}`;
    } else {
      modalImagePreview.src =
        "https://placehold.co/100x100/cccccc/333333?text=Kein+Bild";
    }
  }

  // --- EVENT LISTENERS ---

  // Editor-Container für Edit/Delete-Buttons (Event Delegation)
  editorContainer.addEventListener("click", (e) => {
    const target = e.target;
    const entry = target.closest(".character-entry");
    if (!entry) return;

    const charKey = entry.dataset.key;
    const groupName = entry.dataset.group;

    if (target.classList.contains("edit-btn")) {
      openModal(charKey, groupName);
    } else if (target.classList.contains("delete-btn")) {
      if (
        confirm(
          `Sind Sie sicher, dass Sie den Charakter "${charKey}" löschen möchten?`
        )
      ) {
        delete characterData[groupName][charKey];
        showMessage(
          `Charakter "${charKey}" zum Löschen vorgemerkt.`,
          "success"
        );
        renderEditor();
      }
    }
  });

  // Globale Buttons
  document
    .getElementById("add-character-btn")
    .addEventListener("click", () => openModal());

  document
    .getElementById("save-all-btn")
    .addEventListener("click", async () => {
      try {
        const response = await fetch("data_editor_charaktere.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-CSRF-Token": window.csrfToken,
          },
          body: JSON.stringify(characterData),
        });

        if (!response.ok) {
          throw new Error(`Server-Fehler: ${response.statusText}`);
        }

        const result = await response.json();
        if (result.success) {
          showMessage(result.message, "success");
        } else {
          showMessage(
            result.message || "Ein unbekannter Fehler ist aufgetreten.",
            "error"
          );
        }
      } catch (error) {
        showMessage(`Speichern fehlgeschlagen: ${error.message}`, "error");
      }
    });

  // Modal-Interaktionen
  modalCloseBtn.addEventListener("click", () => (modal.style.display = "none"));
  modalCancelBtn.addEventListener(
    "click",
    () => (modal.style.display = "none")
  );
  modalPicUrlInput.addEventListener("input", updateImagePreview);

  modalImagePreview.onerror = function () {
    this.src = "https://placehold.co/100x100/cccccc/333333?text=Pfad+falsch";
  };

  editForm.addEventListener("submit", (e) => {
    e.preventDefault();

    const name = modalNameInput.value.trim().replace(/\s+/g, "_");
    if (!name) {
      alert("Der Charakter-Name darf nicht leer sein.");
      return;
    }

    let group = modalNewGroupInput.value.trim() || modalGroupSelect.value;
    if (!group) {
      alert("Bitte eine Gruppe auswählen oder eine neue erstellen.");
      return;
    }

    // Neue Gruppe erstellen, falls nicht vorhanden
    if (!characterData[group]) {
      characterData[group] = {};
    }

    const characterDetails = {
      charaktere_pic_url: modalPicUrlInput.value.trim(),
    };

    if (activeEditKey) {
      // Bearbeiten
      // Wenn die Gruppe geändert wurde, muss der Charakter verschoben werden
      if (group !== activeEditGroup) {
        delete characterData[activeEditGroup][activeEditKey];
      }
      characterData[group][activeEditKey] = characterDetails;
      showMessage(`Charakter "${activeEditKey}" aktualisiert.`);
    } else {
      // Hinzufügen
      if (characterData[group][name]) {
        alert(
          `Ein Charakter mit dem Namen "${name}" existiert bereits in dieser Gruppe.`
        );
        return;
      }
      characterData[group][name] = characterDetails;
      showMessage(
        `Neuer Charakter "${name}" in Gruppe "${group}" hinzugefügt.`
      );
    }

    modal.style.display = "none";
    renderEditor();
  });

  // Initiales Rendern
  renderEditor();
});
