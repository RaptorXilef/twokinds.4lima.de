/**
 * @file      ROOT/resources/js/admin_reports.js
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   1.0.0
 * @since     1.0.0 Initiale Erstellung
 *
 * @description Dieses Skript steuert das Detail-Modal auf der Seite management_reports.php.
 * Es befüllt das Modal mit Daten, ruft das Original-Transkript per Fetch-API
 * und generiert eine visuelle Diff-Ansicht mit jsDiff.
 */

document.addEventListener("DOMContentLoaded", () => {
  // Debug-Modus (kann global gesetzt werden, hier als Fallback)
  const debugMode = window.debugMode || false;

  // --- 1. Elemente auswählen ---
  const modal = document.getElementById("report-detail-modal");
  const table = document.getElementById("reports-table");

  if (!modal || !table) {
    if (debugMode)
      console.log(
        "DEBUG [admin_reports.js]: Modal oder Tabelle nicht gefunden. Skript beendet."
      );
    return;
  }

  // Modal-Inhaltselemente
  const modalContent = document.getElementById("report-detail-content");
  const comicLink = document.getElementById("detail-comic-link");
  const comicIdSpan = document.getElementById("detail-comic-id");
  const dateSpan = document.getElementById("detail-date");
  const submitterSpan = document.getElementById("detail-submitter");
  const typeSpan = document.getElementById("detail-type");
  const descriptionContainer = document.getElementById(
    "detail-description-container"
  );
  const descriptionP = document.getElementById("detail-description");
  const transcriptSection = document.getElementById(
    "detail-transcript-section"
  );
  const diffViewer = document.getElementById("detail-diff-viewer");
  const suggestionContainer = document.getElementById(
    "detail-suggestion-container"
  );
  const suggestionTextarea = document.getElementById("detail-suggestion");

  // API-Endpunkt (aus window.adminApiEndpoints, das von init_admin.php gesetzt werden sollte,
  // hier als Fallback hartcodiert, falls es fehlt)
  const apiBaseUrl =
    window.adminApiEndpoints?.getComicData || "./api/get_comic_data.php";
  // CSRF-Token (von init_admin.php)
  const csrfToken = window.csrfToken || "";

  // --- 2. Modal Öffnen/Schließen Logik ---

  /**
   * Öffnet das Modal und befüllt es mit Daten aus der Tabellenzeile.
   * @param {HTMLTableRowElement} row - Die angeklickte Tabellenzeile (TR).
   */
  const openModal = (row) => {
    const dataset = row.dataset;

    // Daten aus data-Attributen lesen
    const comicId = dataset.comicId || "k.A.";
    const reportType = dataset.type || "k.A.";
    const description = dataset.fullDescription || "";
    const suggestion = dataset.suggestion || "";
    const original = dataset.original || ""; // Das vom Benutzer übermittelte "Original"

    // Modal mit Basis-Daten füllen
    comicIdSpan.textContent = comicId;
    comicLink.href = `./comic.php?id=${comicId}`; // Annahme über Link-Struktur
    try {
      // Hole die Comic-Basis-URL aus dem Link in der Tabelle (robuster)
      const linkInTable = row.querySelector('td a[target="_blank"]');
      if (linkInTable) {
        comicLink.href = linkInTable.href;
      }
    } catch (e) {}

    dateSpan.textContent = dataset.date || "k.A.";
    submitterSpan.textContent = dataset.submitter || "k.A.";
    typeSpan.textContent = reportType;

    // Beschreibung anzeigen
    if (description) {
      descriptionP.textContent = description;
      descriptionContainer.style.display = "block";
    } else {
      descriptionP.textContent = "Keine Beschreibung vorhanden.";
      descriptionContainer.style.display = "block"; // Sicherstellen, dass "Keine..." angezeigt wird
    }

    // Transkript-Sektion nur für "transcript"-Typ anzeigen
    if (reportType === "transcript") {
      transcriptSection.style.display = "block";

      // Vorschlag anzeigen
      if (suggestion) {
        suggestionTextarea.value = suggestion;
        suggestionContainer.style.display = "block";
      } else {
        suggestionTextarea.value = "";
        suggestionContainer.style.display = "none";
      }

      // Diff-Ansicht vorbereiten und Fetch starten
      diffViewer.innerHTML =
        '<p class="loading-text">Original-Transkript wird geladen...</p>';
      // Wir verwenden das 'original' aus dem Report, falls vorhanden.
      // Falls nicht, holen wir das *aktuelle* Transkript vom Server.
      if (original) {
        if (debugMode)
          console.log(
            'DEBUG [admin_reports.js]: Verwende "Original" aus dem Report für Diff.'
          );
        generateDiff(original, suggestion);
      } else {
        if (debugMode)
          console.log(
            "DEBUG [admin_reports.js]: Hole aktuelles Transkript vom Server für Diff."
          );
        fetchOriginalTranscript(comicId, suggestion);
      }
    } else {
      // Für "image" oder "other" die Transkript-Sektion ausblenden
      transcriptSection.style.display = "none";
    }

    modal.style.display = "flex";
  };

  /**
   * Schließt das Modal.
   */
  const closeModal = () => {
    modal.style.display = "none";
    // Diff-Viewer leeren, um alte Daten zu entfernen
    diffViewer.innerHTML = "";
  };

  // Event-Listener für Klicks im Modal (Schließen-Buttons)
  modal.addEventListener("click", (e) => {
    if (e.target.dataset.action === "close-detail-modal") {
      closeModal();
    }
  });

  // Event-Listener für Klicks auf die Tabelle (Detail-Buttons)
  table.addEventListener("click", (e) => {
    const detailButton = e.target.closest(".detail-button");
    if (detailButton) {
      const row = detailButton.closest("tr");
      if (row) {
        openModal(row);
      }
    }
  });

  // Schließen bei ESC
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && modal.style.display === "flex") {
      closeModal();
    }
  });

  // --- 3. Fetch- & Diff-Logik ---

  /**
   * Holt das Original-Transkript vom Admin-API-Endpunkt.
   * @param {string} comicId - Die ID des Comics.
   * @param {string} suggestion - Der Vorschlag des Benutzers (für Diff).
   */
  const fetchOriginalTranscript = async (comicId, suggestion) => {
    if (!comicId) {
      diffViewer.innerHTML =
        '<p class="status-message status-red">Fehler: Keine Comic-ID zum Abrufen des Transkripts vorhanden.</p>';
      return;
    }

    try {
      // Wir müssen den CSRF-Token per Header senden (oder als URL-Parameter, wenn API es unterstützt)
      // Für init_admin.php API-Aufrufe ist oft ein GET mit Token einfacher.
      const apiUrl = `${apiBaseUrl}?id=${encodeURIComponent(
        comicId
      )}&csrf_token=${encodeURIComponent(csrfToken)}`;

      const response = await fetch(apiUrl, {
        method: "GET",
        headers: {
          Accept: "application/json",
        },
      });

      if (!response.ok) {
        throw new Error(
          `Server-Antwort: ${response.status} ${response.statusText}`
        );
      }

      const data = await response.json();

      if (data.success && data.transcript) {
        if (debugMode)
          console.log(
            "DEBUG [admin_reports.js]: Original-Transkript erfolgreich geladen."
          );
        // Transkript bereinigen (HTML-Tags entfernen), da der Vorschlag auch Plain Text ist
        const plainTranscript = decodeHtmlEntities(
          stripHtmlTags(data.transcript)
        );
        generateDiff(plainTranscript, suggestion);
      } else {
        throw new Error(
          data.message ||
            "Transkript konnte nicht geladen werden (ungültige Antwort)."
        );
      }
    } catch (error) {
      if (debugMode)
        console.error("DEBUG [admin_reports.js]: Fetch-Fehler:", error);
      diffViewer.innerHTML = `<p class="status-message status-red">Fehler beim Laden des Original-Transkripts: ${error.message}</p>`;
    }
  };

  /**
   * Generiert die Diff-Ansicht mit jsDiff.
   * @param {string} original - Der Originaltext.
   * @param {string} suggestion - Der vorgeschlagene Text.
   */
  const generateDiff = (original, suggestion) => {
    // Sicherstellen, dass jsDiff geladen ist
    if (typeof Diff === "undefined" || typeof Diff.diffChars === "undefined") {
      diffViewer.innerHTML =
        '<p class="status-message status-red">Fehler: jsDiff-Bibliothek nicht geladen.</p>';
      return;
    }

    if (!suggestion) {
      diffViewer.innerHTML =
        '<p class="status-message status-info">Kein Transkript-Vorschlag vorhanden.</p>';
      return;
    }

    if (original === suggestion) {
      diffViewer.innerHTML =
        '<p class="status-message status-info">Vorschlag ist identisch mit dem Original.</p>';
      return;
    }

    const diff = Diff.diffChars(original, suggestion);
    const fragment = document.createDocumentFragment();

    diff.forEach((part) => {
      const node = document.createElement(
        part.added ? "ins" : part.removed ? "del" : "span"
      );
      node.appendChild(document.createTextNode(part.value));
      fragment.appendChild(node);
    });

    diffViewer.innerHTML = ""; // Lade-Text entfernen
    diffViewer.appendChild(fragment);
  };

  // --- 4. Hilfsfunktionen ---

  function stripHtmlTags(str) {
    if (!str) return "";
    const div = document.createElement("div");
    div.innerHTML = str;
    return div.textContent || div.innerText || "";
  }

  function decodeHtmlEntities(str) {
    if (!str) return "";
    const textarea = document.createElement("textarea");
    textarea.innerHTML = str;
    return textarea.value;
  }
});
