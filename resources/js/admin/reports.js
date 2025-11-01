/**
 * @file      ROOT/resources/js/admin_reports.js
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   1.1.0
 * @since     1.0.0 Initiale Erstellung
 * @since     1.1.0 Anpassung an HTML-Transkripte und neue Modal-Struktur in management_reports.php
 *
 * @description Dieses Skript steuert das Detail-Modal auf der Seite management_reports.php.
 * Es befüllt das Modal mit Daten (HTML gerendert, HTML-Code) und
 * generiert eine visuelle Text-Diff-Ansicht mit jsDiff.
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

  // NEU: Angepasste Elemente für HTML-Anzeige
  const suggestionHtmlDisplay = document.getElementById(
    "detail-suggestion-html"
  );
  const suggestionCodeTextarea = document.getElementById(
    "detail-suggestion-code"
  );

  // API-Endpunkt (aus window.adminApiEndpoints, das von init_admin.php gesetzt werden sollte)
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

    // Daten aus data-Attributen lesen (Browser dekodiert HTML-Entitäten automatisch)
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
    } else {
      descriptionP.textContent = "Keine Beschreibung vorhanden.";
    }
    descriptionContainer.style.display = "block";

    // Transkript-Sektion nur für "transcript"-Typ anzeigen
    if (reportType === "transcript") {
      transcriptSection.style.display = "block";

      // NEU: Vorschlag in HTML-Ansicht und Code-Ansicht füllen
      if (suggestion) {
        if (suggestionHtmlDisplay) suggestionHtmlDisplay.innerHTML = suggestion;
        if (suggestionCodeTextarea) suggestionCodeTextarea.value = suggestion;
      } else {
        if (suggestionHtmlDisplay)
          suggestionHtmlDisplay.innerHTML = "<em>Kein Vorschlag (HTML).</em>";
        if (suggestionCodeTextarea)
          suggestionCodeTextarea.value = "Kein Vorschlag (Code).";
      }

      // Diff-Ansicht vorbereiten und Text-Diff generieren
      diffViewer.innerHTML =
        '<p class="loading-text">Text-Diff wird generiert...</p>';

      // Wir verwenden das 'original' aus dem Report.
      // Hinweis: fetchOriginalTranscript wird nicht mehr benötigt, da das Original-HTML
      // jetzt immer mit dem Report gespeichert wird (seit submit_report v1.1.0).
      if (debugMode)
        console.log(
          'DEBUG [admin_reports.js]: Verwende "Original" aus dem Report für Diff.'
        );
      generateTextDiff(original, suggestion);
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
    if (suggestionHtmlDisplay) suggestionHtmlDisplay.innerHTML = "";
    if (suggestionCodeTextarea) suggestionCodeTextarea.value = "";
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

  // --- 3. Diff-Logik ---

  /**
   * Hilfsfunktion, um einen HTML-String in reinen Text umzuwandeln,
   * wobei <p> und <br> als Zeilenumbrüche für einen sauberen Diff interpretiert werden.
   * @param {string} html
   * @returns {string}
   */
  const convertHtmlToText = (html) => {
    // Prüfen, ob es überhaupt HTML ist (alte Reports könnten Plain Text sein)
    if (html && html.trim().startsWith("<")) {
      const tempDiv = document.createElement("div");
      tempDiv.innerHTML = html;
      // Ersetze <p> durch Zeilenumbrüche für Lesbarkeit
      tempDiv.querySelectorAll("p").forEach((p) => {
        p.after(document.createTextNode("\n"));
      });
      // Entferne <br> aber behalte Zeilenumbruch bei
      tempDiv.querySelectorAll("br").forEach((br) => {
        br.after(document.createTextNode("\n"));
      });
      return (tempDiv.textContent || tempDiv.innerText || "").trim();
    }
    // Es ist bereits Plain Text (alte Reports)
    return (html || "").trim();
  };

  /**
   * Generiert die Diff-Ansicht mit jsDiff basierend auf dem Textinhalt.
   * @param {string} originalHtml - Der Original-HTML-Text.
   * @param {string} suggestionHtml - Der vorgeschlagene HTML-Text.
   */
  const generateTextDiff = (originalHtml, suggestionHtml) => {
    // Sicherstellen, dass jsDiff geladen ist
    if (typeof Diff === "undefined" || typeof Diff.diffLines === "undefined") {
      diffViewer.innerHTML =
        '<p class="status-message status-red">Fehler: jsDiff-Bibliothek nicht geladen.</p>';
      return;
    }

    if (!suggestionHtml) {
      diffViewer.innerHTML =
        '<p class="status-message status-info">Kein Transkript-Vorschlag vorhanden.</p>';
      return;
    }

    // Konvertiere HTML zu Text für den Diff
    const originalText = convertHtmlToText(originalHtml);
    const suggestionText = convertHtmlToText(suggestionHtml);

    if (originalText === suggestionText) {
      diffViewer.innerHTML =
        '<p class="status-message status-info">Keine Änderungen am reinen Textinhalt gefunden.</p>';
      return;
    }

    // Wir verwenden diffLines für eine bessere Lesbarkeit
    const diff = Diff.diffLines(originalText, suggestionText, {
      newlineIsToken: true,
    });
    const fragment = document.createDocumentFragment();

    diff.forEach((part) => {
      const node = document.createElement(
        part.added ? "ins" : part.removed ? "del" : "span"
      );
      // Füge ein Leerzeichen-Symbol für Zeilen hinzu, die nur aus Whitespace bestehen,
      // damit sie im Diff sichtbar sind.
      if (part.value.match(/^\s+$/)) {
        node.style.opacity = "0.6";
        node.appendChild(document.createTextNode("[Whitespace-Änderung]"));
      } else {
        node.appendChild(document.createTextNode(part.value));
      }
      fragment.appendChild(node);
    });

    diffViewer.innerHTML = ""; // Lade-Text entfernen
    diffViewer.appendChild(fragment);
  };

  // Alte Hilfsfunktionen (stripHtmlTags, decodeHtmlEntities) sind
  // in convertHtmlToText aufgegangen und werden nicht mehr benötigt.
});
