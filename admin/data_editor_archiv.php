<?php
// === DEBUG-MODUS STEUERUNG ===
// Setze auf true, um DEBUG-Meldungen zu aktivieren, auf false, um sie zu deaktivieren.
$debugMode = false;

if ($debugMode)
    error_log("DEBUG: data_editor_archiv.php wird geladen.");

// Start den Output Buffer als ALLERERSTE Zeile
ob_start();
if ($debugMode)
    error_log("DEBUG: Output Buffer in data_editor_archiv.php gestartet.");

// Starte die PHP-Sitzung. Notwendig, um den Anmeldestatus zu überprüfen.
session_start();

// NEU: Binde die zentrale Sicherheits- und Sitzungsüberprüfung ein.
require_once __DIR__ . '/../src/components/security_check.php';

if ($debugMode)
    error_log("DEBUG: Session gestartet in data_editor_archiv.php.");

// Logout-Logik: Muss vor dem Sicherheitscheck erfolgen.
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    if ($debugMode)
        error_log("DEBUG: Logout-Aktion erkannt.");
    // Zerstöre alle Session-Variablen.
    $_SESSION = array();

    // Lösche das Session-Cookie.
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httpholy"]
        );
    }

    // Zerstöre die Session.
    session_destroy();

    // Weiterleitung zur Login-Seite (index.php im Admin-Bereich).
    ob_end_clean();
    header('Location: index.php');
    exit;
}

// SICHERHEITSCHECK: Nur für angemeldete Administratoren zugänglich.
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    if ($debugMode)
        error_log("DEBUG: Nicht angemeldet, Weiterleitung zur Login-Seite von data_editor_archiv.php.");
    // Wenn nicht angemeldet, zur Login-Seite weiterleiten.
    ob_end_clean(); // Output Buffer leeren, da wir umleiten
    header('Location: index.php');
    exit;
}
if ($debugMode)
    error_log("DEBUG: Admin in data_editor_archiv.php angemeldet.");

// Pfad zur JSON-Datei mit den Archivkapiteln
$archiveChaptersJsonPath = __DIR__ . '/../src/config/archive_chapters.json';
if ($debugMode)
    error_log("DEBUG: Pfad zu archive_chapters.json: " . $archiveChaptersJsonPath);

$message = '';
$messageType = ''; // 'success' oder 'error' oder 'info' oder 'warning'
$scrollToId = ''; // Variable, um die ID des Elements zu speichern, zu dem gescrollt werden soll

/**
 * Funktion, um den effektiven Sortierwert für ein Kapitel zu erhalten.
 * Diese Funktion ist identisch mit der in archiv.php, um konsistente Sortierung zu gewährleisten.
 * Gibt ein Array zurück: [Priorität, Sortier-Schlüssel]
 * Prioritäten:
 * 0: Numerische chapterId (z.B. "1", "10", "5.5", "6,1" wird zu "6.1") - sortiert numerisch
 * 1: Andere String-chapterId (z.B. "Chapter X") - sortiert natürlich als String
 * 2: Leere chapterId ("") - sortiert ganz ans Ende
 */
function getChapterSortValue(array $chapter): array
{
    $rawChapterId = $chapter['chapterId'] ?? '';

    // Priorität 2: Leere chapterId ("") - sortiert ganz ans Ende
    if ($rawChapterId === '') {
        return [2, PHP_INT_MAX]; // Höchste Priorität, um ans Ende zu gehen
    }

    // Ersetze Komma durch Punkt für die numerische Prüfung, falls vorhanden
    $numericCheckId = str_replace(',', '.', $rawChapterId);

    // Priorität 0: Numerische chapterId (z.B. "1", "10", "5.5", "6,1" wird zu "6.1")
    if (is_numeric($numericCheckId)) {
        return [0, (float) $numericCheckId]; // Niedrigste Priorität, sortiert numerisch
    }

    // Priorität 1: Andere String-chapterId (z.g. "Chapter X")
    return [1, $rawChapterId]; // Mittlere Priorität, sortiert natürlich als String
}

/**
 * Lädt Kapitel-Metadaten aus einer JSON-Datei.
 * @param string $path Der Pfad zur JSON-Datei.
 * @param bool $debugMode Debug-Modus Flag.
 * @return array Die dekodierten Daten als assoziatives Array (chapterId => data) oder ein leeres Array im Fehlerfall.
 */
function loadArchiveChapters(string $path, bool $debugMode): array
{
    if ($debugMode)
        error_log("DEBUG: loadArchiveChapters() aufgerufen für: " . basename($path));
    if (!file_exists($path) || filesize($path) === 0) {
        if ($debugMode)
            error_log("DEBUG: archive_chapters.json nicht gefunden oder leer.");
        return [];
    }
    $content = file_get_contents($path);
    if ($content === false) {
        error_log("Fehler beim Lesen der JSON-Datei: " . $path);
        if ($debugMode)
            error_log("DEBUG: Fehler beim Lesen des Inhalts von: " . $path);
        return [];
    }
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Fehler beim Dekodieren von archive_chapters.json: " . json_last_error_msg());
        if ($debugMode)
            error_log("DEBUG: Fehler beim Dekodieren von archive_chapters.json: " . json_last_error_msg());
        return [];
    }
    // Stelle sicher, dass es ein Array ist
    if (!is_array($data)) {
        if ($debugMode)
            error_log("DEBUG: Dekodierte Daten sind kein Array.");
        return [];
    }
    // Sortiere nach der neuen getChapterSortValue Logik
    usort($data, function ($a, $b) {
        $sortValueA = getChapterSortValue($a);
        $sortValueB = getChapterSortValue($b);

        // Vergleiche zuerst nach Priorität
        if ($sortValueA[0] !== $sortValueB[0]) {
            return $sortValueA[0] <=> $sortValueB[0];
        }

        // Wenn Prioritäten gleich sind, vergleiche nach dem Sortier-Schlüssel
        if ($sortValueA[0] === 1) { // Beide sind andere Strings (Priorität 1)
            return strnatcmp($sortValueA[1], $sortValueB[1]); // Natürliche String-Sortierung
        }

        // Für Priorität 0 (numerisch) und 2 (leere ID), verwende den Spaceship-Operator
        return $sortValueA[1] <=> $sortValueB[1];
    });
    if ($debugMode)
        error_log("DEBUG: Archivkapitel erfolgreich geladen und sortiert. Anzahl: " . count($data));
    return $data;
}

/**
 * Speichert Kapitel-Metadaten in einer JSON-Datei.
 * @param string $path Der Pfad zur JSON-Datei.
 * @param array $data Die zu speichernden Daten.
 * @param bool $debugMode Debug-Modus Flag.
 * @return bool True bei Erfolg, False bei Fehler.
 */
function saveArchiveChapters(string $path, array $data, bool $debugMode): bool
{
    if ($debugMode)
        error_log("DEBUG: saveArchiveChapters() aufgerufen für: " . basename($path));
    // Sortiere die Daten vor dem Speichern nach der neuen getChapterSortValue Logik
    usort($data, function ($a, $b) {
        $sortValueA = getChapterSortValue($a);
        $sortValueB = getChapterSortValue($b);

        if ($sortValueA[0] !== $sortValueB[0]) {
            return $sortValueA[0] <=> $sortValueB[0];
        }

        if ($sortValueA[0] === 1) {
            return strnatcmp($sortValueA[1], $sortValueB[1]);
        }
        return $sortValueA[1] <=> $sortValueB[1];
    });
    if ($debugMode)
        error_log("DEBUG: Archivdaten vor dem Speichern sortiert.");
    $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($jsonContent === false) {
        error_log("Fehler beim Kodieren von Archivdaten in JSON: " . json_last_error_msg());
        if ($debugMode)
            error_log("DEBUG: Fehler beim Kodieren von Archivdaten in JSON: " . json_last_error_msg());
        return false;
    }
    if (file_put_contents($path, $jsonContent) !== false) {
        if ($debugMode)
            error_log("DEBUG: Archivdaten erfolgreich gespeichert.");
        return true;
    } else {
        error_log("Fehler beim Schreiben der Archivdaten nach " . $path);
        if ($debugMode)
            error_log("DEBUG: Fehler beim Schreiben der Archivdaten nach " . $path);
        return false;
    }
}

// Lade die aktuellen Kapiteldaten
$chapters = loadArchiveChapters($archiveChaptersJsonPath, $debugMode);

// Initialisiere Variablen für das Bearbeitungsformular
$editChapterId = '';
$editTitle = '';
$editDescription = '';
$formAction = 'add'; // Standardaktion ist "Hinzufügen"
$originalChapterId = ''; // Wird nur im Bearbeitungsmodus gesetzt
$leaveIdEmptyChecked = false; // Flag für die Checkbox

// === POST-Anfragen verarbeiten ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($debugMode)
        error_log("DEBUG: POST-Anfrage erkannt.");
    // Inhalte vom Formular abrufen
    $postedTitle = $_POST['title'] ?? ''; // Titel ist jetzt ein normales Textfeld
    $postedDescription = $_POST['description'] ?? ''; // Beschreibung bleibt Summernote
    // trim() entfernt Leerzeichen am Anfang/Ende der ID
    $postedChapterId = trim($_POST['chapter_id'] ?? '');
    $originalChapterId = trim($_POST['original_chapter_id'] ?? '');
    $leaveIdEmpty = isset($_POST['leave_id_empty']); // Checkbox-Status

    // NEU: Ersetze Komma durch Punkt in der geposteten ID, bevor sie weiterverarbeitet wird
    $postedChapterId = str_replace(',', '.', $postedChapterId);

    if ($debugMode) {
        error_log("DEBUG: Posted Title: " . $postedTitle);
        error_log("DEBUG: Posted Description: " . $postedDescription);
        error_log("DEBUG: Posted Chapter ID (nach Komma-Ersetzung): '" . $postedChapterId . "'"); // Anführungszeichen für leere ID
        error_log("DEBUG: Original Chapter ID: '" . $originalChapterId . "'"); // Anführungszeichen für leere ID
        error_log("DEBUG: Leave ID Empty Checkbox: " . ($leaveIdEmpty ? 'true' : 'false'));
    }

    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        if ($debugMode)
            error_log("DEBUG: Ausgeführte Aktion: " . $action);

        switch ($action) {
            case 'add':
                $newChapterId = $postedChapterId;

                // Wenn Checkbox "leer lassen" aktiviert ist, setze ID auf leeren String
                if ($leaveIdEmpty) {
                    $newChapterId = '';
                    if ($debugMode)
                        error_log("DEBUG: Kapitel ID explizit auf leer gesetzt durch Checkbox.");
                }
                // Ansonsten, wenn die ID leer ist (und Checkbox nicht aktiviert), automatisch generieren
                elseif (empty($newChapterId)) {
                    $maxId = 0;
                    if (!empty($chapters)) {
                        foreach ($chapters as $chapter) {
                            // Nur numerische IDs für die Max-Berechnung berücksichtigen
                            $currentId = $chapter['chapterId'] ?? '';
                            // Ersetze Komma durch Punkt für numerische Prüfung
                            $numericCurrentId = str_replace(',', '.', $currentId);
                            if (is_numeric($numericCurrentId) && (float) $numericCurrentId > $maxId) {
                                $maxId = (float) $numericCurrentId;
                            }
                        }
                    }
                    // Generiere die nächste Ganzzahl-ID
                    $newChapterId = (string) (floor($maxId) + 1);
                    if ($debugMode)
                        error_log("DEBUG: Kapitel ID automatisch generiert: " . $newChapterId);
                }

                // Überprüfe, ob die (neue oder explizit leere) ID bereits existiert
                foreach ($chapters as $chapter) {
                    if (isset($chapter['chapterId']) && $chapter['chapterId'] == $newChapterId) {
                        $message = 'Fehler: Kapitel ID existiert bereits. Bitte wählen Sie eine andere ID oder lassen Sie das Feld leer für automatische Generierung.';
                        $messageType = 'error';
                        if ($debugMode)
                            error_log("DEBUG: Fehler: Kapitel ID existiert bereits: '" . $newChapterId . "'");
                        header('Location: ' . $_SERVER['PHP_SELF'] . '?message=' . urlencode($message) . '&type=' . urlencode($messageType));
                        exit;
                    }
                }

                $newChapter = [
                    'chapterId' => $newChapterId, // ID als String speichern
                    'title' => $postedTitle,
                    'description' => $postedDescription
                ];
                $chapters[] = $newChapter;

                if (saveArchiveChapters($archiveChaptersJsonPath, $chapters, $debugMode)) {
                    $message = 'Kapitel erfolgreich hinzugefügt!';
                    $messageType = 'success';
                    $scrollToId = $newChapterId; // Setze die ID zum Scrollen
                    if ($debugMode)
                        error_log("DEBUG: Kapitel '" . $newChapterId . "' erfolgreich hinzugefügt. Scroll-ID: " . $scrollToId);
                } else {
                    $message = 'Fehler beim Hinzufügen des Kapitels.';
                    $messageType = 'error';
                    if ($debugMode)
                        error_log("DEBUG: Fehler beim Hinzufügen des Kapitels '" . $newChapterId . "'.");
                }
                break;

            case 'edit':
                $foundIndex = -1;
                foreach ($chapters as $index => $chapter) {
                    // Vergleich der originalChapterId, die auch "" oder "6.1" sein kann
                    if (isset($chapter['chapterId']) && $chapter['chapterId'] == $originalChapterId) {
                        $foundIndex = $index;
                        break;
                    }
                }

                if ($foundIndex !== -1) {
                    if ($debugMode)
                        error_log("DEBUG: Ursprüngliches Kapitel zum Bearbeiten gefunden bei Index: " . $foundIndex . " (ID: '" . $originalChapterId . "')");

                    $effectivePostedChapterId = $postedChapterId;
                    // Wenn Checkbox "leer lassen" aktiviert ist, setze ID auf leeren String
                    if ($leaveIdEmpty) {
                        $effectivePostedChapterId = '';
                        if ($debugMode)
                            error_log("DEBUG: Kapitel ID explizit auf leer gesetzt im Bearbeitungsmodus durch Checkbox.");
                    }

                    // Wenn die ID geändert wird, validiere die neue ID
                    if ($effectivePostedChapterId != $originalChapterId) {
                        if ($debugMode)
                            error_log("DEBUG: Kapitel ID wird geändert von '" . $originalChapterId . "' zu '" . $effectivePostedChapterId . "'");

                        // Überprüfe, ob die neue ID bereits existiert (außer dem aktuell bearbeiteten Kapitel)
                        foreach ($chapters as $index => $chapter) {
                            if ($index !== $foundIndex && isset($chapter['chapterId']) && $chapter['chapterId'] == $effectivePostedChapterId) {
                                $message = 'Fehler: Die neue Kapitel ID existiert bereits. Bitte wählen Sie eine andere ID.';
                                $messageType = 'error';
                                if ($debugMode)
                                    error_log("DEBUG: Fehler: Neue Kapitel ID existiert bereits: '" . $effectivePostedChapterId . "'");
                                header('Location: ' . $_SERVER['PHP_SELF'] . '?message=' . urlencode($message) . '&type=' . urlencode($messageType));
                                exit;
                            }
                        }
                    }

                    // Update the chapter data
                    $chapters[$foundIndex]['chapterId'] = $effectivePostedChapterId; // ID als String speichern
                    $chapters[$foundIndex]['title'] = $postedTitle;
                    $chapters[$foundIndex]['description'] = $postedDescription;

                    if (saveArchiveChapters($archiveChaptersJsonPath, $chapters, $debugMode)) {
                        $message = 'Kapitel erfolgreich aktualisiert!';
                        $messageType = 'success';
                        $scrollToId = $effectivePostedChapterId; // Setze die ID zum Scrollen
                        if ($debugMode)
                            error_log("DEBUG: Kapitel '" . $effectivePostedChapterId . "' erfolgreich aktualisiert. Scroll-ID: " . $scrollToId);
                    } else {
                        $message = 'Fehler beim Aktualisieren des Kapitels.';
                        $messageType = 'error';
                        if ($debugMode)
                            error_log("DEBUG: Fehler beim Aktualisieren des Kapitels '" . $effectivePostedChapterId . "'.");
                    }
                } else {
                    $message = 'Fehler: Ursprüngliches Kapitel zum Bearbeiten nicht gefunden.';
                    $messageType = 'error';
                    if ($debugMode)
                        error_log("DEBUG: Fehler: Ursprüngliches Kapitel zum Bearbeiten nicht gefunden (ID: '" . $originalChapterId . "').");
                }
                break;

            case 'delete':
                $chapterIdToDelete = trim($_POST['chapter_id'] ?? '');
                if ($debugMode)
                    error_log("DEBUG: Löschaktion für Kapitel ID: '" . $chapterIdToDelete . "'");
                $chapters = array_filter($chapters, function ($chapter) use ($chapterIdToDelete) {
                    return (isset($chapter['chapterId']) && $chapter['chapterId'] != $chapterIdToDelete);
                });

                if (saveArchiveChapters($archiveChaptersJsonPath, array_values($chapters), $debugMode)) { // array_values um Indizes neu zu ordnen
                    $message = 'Kapitel erfolgreich gelöscht!';
                    $messageType = 'success';
                    // Kein Scrollen nach dem Löschen, da das Element nicht mehr existiert
                    if ($debugMode)
                        error_log("DEBUG: Kapitel '" . $chapterIdToDelete . "' erfolgreich gelöscht.");
                } else {
                    $message = 'Fehler beim Löschen des Kapitels.';
                    $messageType = 'error';
                    if ($debugMode)
                        error_log("DEBUG: Fehler beim Löschen des Kapitels '" . $chapterIdToDelete . "'.");
                }
                break;
        }
        // Nach einer POST-Anfrage Redirect, um Formular-Resubmission zu vermeiden
        $redirectUrl = $_SERVER['PHP_SELF'] . '?message=' . urlencode($message) . '&type=' . urlencode($messageType);
        if (!empty($scrollToId)) {
            // URL-Encoding für den Fragment-Bezeichner
            $redirectUrl .= '#chapter-' . urlencode($scrollToId);
        }
        if ($debugMode)
            error_log("DEBUG: Redirect nach POST-Anfrage zu: " . $redirectUrl);
        header('Location: ' . $redirectUrl);
        exit;
    }
}

// === GET-Parameter für Bearbeitung und Nachrichten verarbeiten ===
if (isset($_GET['edit_id'])) {
    $editChapterId = trim($_GET['edit_id']);
    $formAction = 'edit';
    if ($debugMode)
        error_log("DEBUG: Bearbeitungsmodus aktiviert für ID: '" . $editChapterId . "'");
    foreach ($chapters as $chapter) {
        if (isset($chapter['chapterId']) && $chapter['chapterId'] == $editChapterId) {
            $editTitle = $chapter['title'];
            $editDescription = $chapter['description'];
            $originalChapterId = $editChapterId; // Setze die ursprüngliche ID für den Bearbeitungsmodus
            // Setze den Status der Checkbox, wenn die ID leer ist
            $leaveIdEmptyChecked = ($editChapterId === '');
            if ($debugMode)
                error_log("DEBUG: Bearbeitungsdaten geladen für ID '" . $editChapterId . "': Titel='" . $editTitle . "', Checkbox 'leer lassen' ist " . ($leaveIdEmptyChecked ? 'aktiviert' : 'deaktiviert'));
            break;
        }
    }
}

// Nachrichten aus GET-Parametern anzeigen
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $messageType = htmlspecialchars($_GET['type']);
    if ($debugMode)
        error_log("DEBUG: Nachricht aus GET-Parametern: Typ='" . $messageType . "', Nachricht='" . $message . "'");
}

// Prüfen, ob ein Scroll-Anker in der URL vorhanden ist (nach Redirect)
if (isset($_GET['scroll_to'])) {
    $scrollToId = htmlspecialchars($_GET['scroll_to']);
    if ($debugMode)
        error_log("DEBUG: Scroll-To ID aus GET-Parametern: " . $scrollToId);
}


// Setze Parameter für den Header.
$pageTitle = 'Archiv Daten Editor';
$pageHeader = 'Archiv Daten Editor';
$siteDescription = 'Seite zum Bearbeiten der Archivkapitel-Daten.';
$robotsContent = 'noindex, nofollow'; // Diese Seite soll nicht indexiert werden
if ($debugMode) {
    error_log("DEBUG: Seiten-Titel: " . $pageTitle);
    error_log("DEBUG: Robots-Content: " . $robotsContent);
}

// Heredoc-Syntax für $additionalScripts
$additionalScripts = <<<EOT
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            console.log("DOM fully loaded and parsed."); // Debug-Meldung

            const editFormSection = document.getElementById("edit-form-section");
            const formHeader = document.getElementById("form-header");
            const submitButton = document.getElementById("submit-button");
            const cancelEditButton = document.getElementById("cancel-edit-button");
            const chapterIdInput = document.getElementById("chapter_id_input"); // Feld für die Kapitel ID
            const originalChapterIdHidden = document.getElementById("original_chapter_id_hidden"); // Hidden input for original ID
            const formActionInput = document.getElementById("form_action");
            const titleInput = document.getElementById("title"); // Referenz auf Input-Feld
            const descriptionTextarea = $("#description"); // jQuery-Objekt für Summernote
            const leaveIdEmptyCheckbox = document.getElementById("leave_id_empty_checkbox"); // Neue Checkbox

            // Referenz zum Icon im Formular-Header
            const formHeaderIcon = formHeader ? formHeader.querySelector("i") : null;
            if (!formHeaderIcon) {
                console.error("Error: Collapsible header icon for form section not found on DOMContentLoaded.");
            }

            // Funktion zur Initialisierung von Summernote (nur für Beschreibung)
            function initializeSummernote() {
                if (!descriptionTextarea.data("summernote")) {
                    console.log("Initializing Summernote for description..."); // Debug-Meldung
                    descriptionTextarea.summernote({
                        placeholder: "Beschreibung hier eingeben...",
                        tabsize: 2,
                        height: 200,
                        toolbar: [
                            ["style", ["style"]],
                            ["font", ["bold", "italic", "underline", "clear"]],
                            ["color", ["color"]],
                            ["para", ["ul", "ol", "paragraph"]],
                            ["table", ["table"]],
                            ["insert", ["link"]],
                            ["view", ["fullscreen", "codeview", "help"]]
                        ],
                        defaultParagraphSeparator: null // Verhindert automatische <p>-Tags
                    });
                }
                console.log("Summernote initialization check complete."); // Debug-Meldung
            }

            // Funktion zum Zerstören von Summernote-Instanzen (nur für Beschreibung)
            function destroySummernote() {
                if (descriptionTextarea.data("summernote")) {
                    descriptionTextarea.summernote("destroy");
                    console.log("Summernote for description destroyed."); // Debug-Meldung
                }
            }

            // Funktion zum Zurücksetzen des Formulars
            function resetForm() {
                chapterIdInput.value = "";
                chapterIdInput.disabled = false; // Sicherstellen, dass das Feld aktiviert ist
                chapterIdInput.placeholder = "Automatisch generieren, wenn leer"; // Platzhalter zurücksetzen
                if (leaveIdEmptyCheckbox) {
                    leaveIdEmptyCheckbox.checked = false; // Checkbox deaktivieren
                }
                originalChapterIdHidden.value = ""; // Auch die ursprüngliche ID zurücksetzen
                
                titleInput.value = ""; // Titel-Input leeren
                
                // Summernote spezifisch: Inhalte leeren
                if (descriptionTextarea.data("summernote")) {
                    descriptionTextarea.summernote("code", "");
                } else {
                    descriptionTextarea.val(""); // Fallback für nicht-initialisiertes Summernote
                }

                formActionInput.value = "add";
                formHeader.textContent = "Neues Kapitel hinzufügen"; // Angepasste Überschrift
                submitButton.textContent = "Kapitel hinzufügen";
                cancelEditButton.style.display = "none"; // Verstecke Abbrechen-Button
                
                editFormSection.classList.remove("expanded"); // Klappe Formular ein
                if (formHeaderIcon) {
                    formHeaderIcon.classList.remove("fa-chevron-down");
                    formHeaderIcon.classList.add("fa-chevron-right");
                }
                console.log("Form reset and collapsed."); // Debug-Meldung
            }

            // Logik für den "Bearbeiten" Button
            document.querySelectorAll(".edit-button").forEach(button => {
                button.addEventListener("click", function() {
                    console.log("Edit button clicked."); // Debug-Meldung
                    const chapterId = this.dataset.id;
                    const title = this.dataset.title;
                    const description = this.dataset.description;

                    chapterIdInput.value = chapterId; // ID im editierbaren Feld anzeigen
                    originalChapterIdHidden.value = chapterId; // Ursprüngliche ID speichern
                    
                    // Setze den Zustand der "Leer lassen"-Checkbox basierend auf der chapterId
                    if (leaveIdEmptyCheckbox) {
                        if (chapterId === "") {
                            leaveIdEmptyCheckbox.checked = true;
                            chapterIdInput.disabled = true;
                            chapterIdInput.placeholder = "ID wird leer gelassen";
                        } else {
                            leaveIdEmptyCheckbox.checked = false;
                            chapterIdInput.disabled = false;
                            chapterIdInput.placeholder = "Automatisch generieren, wenn leer";
                        }
                    }

                    titleInput.value = title; // Titel-Input füllen
                    
                    // Summernote initialisieren, falls noch nicht geschehen
                    initializeSummernote();
                    descriptionTextarea.summernote("code", description); // Summernote spezifisch
                    
                    formActionInput.value = "edit";
                    formHeader.textContent = "Kapitel bearbeiten (ID: " + (chapterId === "" ? "Leer" : chapterId) + ")";
                    submitButton.textContent = "Änderungen speichern";
                    cancelEditButton.style.display = "inline-block"; // Zeige Abbrechen-Button

                    // Scrolle zum Formular und klappe es aus
                    if (!editFormSection.classList.contains("expanded")) {
                        editFormSection.classList.add("expanded");
                        if (formHeaderIcon) { // Sicherstellen, dass das Icon existiert
                            formHeaderIcon.classList.remove("fa-chevron-right");
                            formHeaderIcon.classList.add("fa-chevron-down");
                        }
                        console.log("Form section expanded for editing."); // Debug-Meldung
                    }
                    editFormSection.scrollIntoView({ behavior: "smooth" });
                });
            });

            // Logik für den "Abbrechen" Button
            cancelEditButton.addEventListener("click", function() {
                console.log("Cancel button clicked."); // Debug-Meldung
                resetForm();
                // Optional: Nachricht anzeigen
                const messageBoxElement = document.getElementById("message-box");
                if (messageBoxElement) {
                    messageBoxElement.textContent = "Bearbeitung abgebrochen.";
                    messageBoxElement.className = "message-box info";
                    messageBoxElement.style.display = "block";
                    setTimeout(() => { messageBoxElement.style.display = "none"; }, 5000);
                }
            });

            // Custom Confirmation Modal
            const customConfirmModal = document.getElementById("customConfirmModal");
            const confirmMessage = document.getElementById("confirmMessage");
            const confirmYes = document.getElementById("confirmYes");
            const confirmNo = document.getElementById("confirmNo");
            let deleteActionCallback = null; // Callback-Funktion für die Löschaktion

            function showCustomConfirm(message, callback) {
                confirmMessage.textContent = message;
                deleteActionCallback = callback;
                customConfirmModal.style.display = "block";
                console.log("Custom confirmation modal shown."); // Debug-Meldung
            }

            confirmYes.onclick = function() {
                customConfirmModal.style.display = "none";
                if (deleteActionCallback) {
                    deleteActionCallback(true);
                    console.log("Confirmation: Yes clicked."); // Debug-Meldung
                }
            };

            confirmNo.onclick = function() {
                customConfirmModal.style.display = "none";
                if (deleteActionCallback) {
                    deleteActionCallback(false);
                    console.log("Confirmation: No clicked."); // Debug-Meldung
                }
            };

            // Logik für den "Löschen" Button mit Custom Confirm
            document.querySelectorAll(".delete-button").forEach(button => {
                button.addEventListener("click", function(event) {
                    event.preventDefault();
                    console.log("Delete button clicked (table)."); // Debug-Meldung
                    const chapterId = this.dataset.id;
                    showCustomConfirm("Sind Sie sicher, dass Sie dieses Kapitel löschen möchten?", function(confirmed) {
                        if (confirmed) {
                            const form = document.createElement("form");
                            form.method = "POST";
                            form.style.display = "none";

                            const actionInput = document.createElement("input");
                            actionInput.type = "hidden";
                            actionInput.name = "action";
                            actionInput.value = "delete";
                            form.appendChild(actionInput);

                            const idInput = document.createElement("input");
                            idInput.type = "hidden";
                            idInput.name = "chapter_id"; // Hier wird die zu löschende ID übergeben
                            idInput.value = chapterId;
                            form.appendChild(idInput);

                            document.body.appendChild(form);
                            form.submit();
                            console.log("Delete form submitted for ID:", chapterId); // Debug-Meldung
                        } else {
                            console.log("Delete cancelled for ID:", chapterId); // Debug-Meldung
                        }
                    });
                });
            });

            // Add event listeners for collapsible headers
            document.querySelectorAll(".collapsible-header").forEach(header => {
                const section = header.closest(".collapsible-section");
                const icon = header.querySelector("i");
                const isFormSection = section.classList.contains("form-section");

                // Set initial icon based on HTML class
                if (icon) { // Sicherstellen, dass das Icon existiert
                    if (section.classList.contains("expanded")) {
                        icon.classList.remove("fa-chevron-right");
                        icon.classList.add("fa-chevron-down");
                        if (isFormSection) {
                            initializeSummernote(); // Summernote initialisieren, wenn Formular beim Laden bereits aufgeklappt ist
                            console.log("Form section was expanded on load, Summernote initialized."); // Debug-Meldung
                        }
                    } else {
                        icon.classList.remove("fa-chevron-down");
                        icon.classList.add("fa-chevron-right");
                    }
                }


                header.addEventListener("click", function() {
                    console.log("Collapsible header clicked."); // Debug-Meldung
                    const wasExpanded = section.classList.contains("expanded");
                    section.classList.toggle("expanded");
                    // Update the icon based on the new expanded state
                    if (icon) { // Sicherstellen, dass das Icon existiert
                        if (section.classList.contains("expanded")) {
                            icon.classList.remove("fa-chevron-right");
                            icon.classList.add("fa-chevron-down");
                            // If it's the form section and it's expanded, initialize Summernote
                            if (isFormSection && !wasExpanded) { // Nur initialisieren, wenn es vorher nicht expanded war
                                initializeSummernote();
                                console.log("Form section expanded, Summernote initialized."); // Debug-Meldung
                            }
                            // Scroll to the form if it's expanded
                            if (isFormSection) {
                                section.scrollIntoView({ behavior: "smooth" });
                                console.log("Scrolled to form section."); // Debug-Meldung
                            }
                        } else {
                            icon.classList.remove("fa-chevron-down");
                            icon.classList.add("fa-chevron-right");
                            // If it's the form section and it's collapsed, destroy Summernote
                            if (isFormSection && wasExpanded) { // Nur zerstören, wenn es vorher expanded war
                                destroySummernote();
                                console.log("Form section collapsed, Summernote destroyed."); // Debug-Meldung
                            }
                        }
                    }
                });
            });

            // Display message from GET parameters on page load
            const urlParams = new URLSearchParams(window.location.search);
            const msg = urlParams.get("message");
            const type = urlParams.get("type");
            const messageBoxElement = document.getElementById("message-box");
            const scrollToHash = window.location.hash; // Den Hash-Teil der URL abrufen

            if (msg && type && messageBoxElement) {
                messageBoxElement.textContent = msg;
                messageBoxElement.className = "message-box " + type;
                messageBoxElement.style.display = "block";
                // Clean URL parameters after displaying message, but keep hash
                history.replaceState({}, document.title, window.location.pathname + scrollToHash);
                setTimeout(() => { messageBoxElement.style.display = "none"; }, 5000);
                console.log("Message from GET displayed:", msg, "Type:", type); // Debug-Meldung
            }

            // Scrollen zum Element, wenn ein Hash in der URL vorhanden ist
            if (scrollToHash) {
                const targetElementId = scrollToHash.substring(1); // '#' entfernen
                const targetElement = document.getElementById(targetElementId);
                if (targetElement) {
                    targetElement.scrollIntoView({ behavior: 'smooth', block: 'center' }); // 'block: center' für bessere Sichtbarkeit
                    console.log("Scrolled to element with ID:", targetElementId); // Debug-Meldung
                } else {
                    console.warn("Target element for scrolling not found:", targetElementId); // Debug-Meldung
                }
            }


            // Add new chapter button functionality
            document.getElementById("add-new-chapter-button").addEventListener("click", function() {
                console.log("Add new chapter button clicked."); // Debug-Meldung
                resetForm();
                // Beim Hinzufügen eines neuen Kapitels Summernote initialisieren und Formular aufklappen
                editFormSection.classList.add("expanded");
                if (formHeaderIcon) { // Sicherstellen, dass das Icon existiert
                    formHeaderIcon.classList.remove("fa-chevron-right");
                    formHeaderIcon.classList.add("fa-chevron-down");
                }
                initializeSummernote(); // Summernote initialisieren (nur für Beschreibung)
                editFormSection.scrollIntoView({ behavior: "smooth" });
                formHeader.textContent = "Neues Kapitel hinzufügen"; // Setze die Überschrift explizit
                console.log("New chapter form opened and Summernote initialized."); // Debug-Meldung
            });

            // Initialen Zustand des Formulars setzen (z.B. wenn edit_id in URL ist)
            // Dies muss nach allen Event Listeners passieren
            if ("<?php echo $formAction; ?>" === "edit") {
                console.log("Page loaded in edit mode."); // Debug-Meldung
                // Wenn wir im Bearbeitungsmodus starten, klappe das Formular auf und setze die Daten
                editFormSection.classList.add("expanded");
                if (formHeaderIcon) { // Sicherstellen, dass das Icon existiert
                    formHeaderIcon.classList.remove("fa-chevron-right");
                    formHeaderIcon.classList.add("fa-chevron-down");
                }
                initializeSummernote(); // Summernote initialisieren (nur für Beschreibung)
                titleInput.value = "<?php echo htmlspecialchars($editTitle); ?>"; // Titel-Input füllen
                descriptionTextarea.summernote("code", "<?php echo htmlspecialchars($editDescription); ?>");
                chapterIdInput.value = "<?php echo htmlspecialchars($editChapterId); ?>";
                originalChapterIdHidden.value = "<?php echo htmlspecialchars($originalChapterId); ?>";
                formHeader.textContent = "Kapitel bearbeiten (ID: <?php echo htmlspecialchars($editChapterId); ?>)";
                submitButton.textContent = "Änderungen speichern";
                cancelEditButton.style.display = "inline-block";
                console.log("Form pre-filled for editing."); // Debug-Meldung
            } else {
                console.log("Page loaded in add mode."); // Debug-Meldung
            }
        });
    </script>
EOT;

// Heredoc-Syntax für $additionalHeadContent
$additionalHeadContent = <<<EOT
    <style>
        /* Allgemeine Layout-Anpassungen */
        .admin-container {
            padding: 20px;
            max-width: 1200px;
            margin: 20px auto;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        body.theme-night .admin-container {
            background-color: #00334c; /* Dunklerer Hintergrund für den Container im Dark Mode */
            color: #fff;
        }

        .message-box {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
            display: none; /* Standardmäßig ausgeblendet */
        }

        .message-box.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message-box.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .message-box.info { /* Added info message style */
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .message-box.warning { /* Added warning message style */
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }


        body.theme-night .message-box.success {
            background-color: #28a745; /* Dunkelgrün */
            color: #fff;
            border-color: #218838;
        }

        body.theme-night .message-box.error {
            background-color: #dc3545; /* Dunkelrot */
            color: #fff;
            border-color: #c82333;
        }

        body.theme-night .message-box.info { /* Dark mode for info message */
            background-color: #17a2b8;
            color: #fff;
            border-color: #138496;
        }

        body.theme-night .message-box.warning { /* Dark mode for warning message */
            background-color: #6c5b00;
            color: #fff;
            border-color: #927c00;
        }

        /* Collapsible Sections */
        .collapsible-section {
            margin-bottom: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            overflow: hidden; /* Ensures content doesn\'t spill during transition */
        }

        body.theme-night .collapsible-section {
            background-color: #00425c;
        }

        .collapsible-header {
            cursor: pointer;
            padding: 15px 20px; /* More padding for a better clickable area */
            background-color: #f2f2f2; /* Light background for header */
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1.5em; /* Match h2 font size */
            font-weight: bold;
            color: #333;
        }

        body.theme-night .collapsible-header {
            background-color: #005a7e;
            color: #fff;
            border-bottom-color: #007bb5;
        }

        .collapsible-header i {
            transition: transform 0.3s ease;
            margin-left: 10px; /* Space between text and icon */
        }

        .collapsible-section.expanded .collapsible-header i {
            transform: rotate(0deg); /* Down arrow */
        }

        .collapsible-section:not(.expanded) .collapsible-header i {
            transform: rotate(-90deg); /* Right arrow */
        }

        .collapsible-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
            padding: 0 20px; /* Initial padding, will be adjusted when expanded */
            display: block; /* Sicherstellen, dass es nicht 'display: none' ist, wenn es von max-height gesteuert wird */
        }

        .collapsible-section.expanded .collapsible-content {
            max-height: 4800px; /* Sufficiently large to show content */
            padding-top: 20px; /* Restore top padding */
            padding-bottom: 20px; /* Restore bottom padding */
            display: block !important; /* Explizit sicherstellen, dass es sichtbar ist */
        }

        /* Remove padding from the section classes themselves as it\'s now on collapsible-content */
        .form-section, .comic-list-section, .report-section {
            padding: 0;
        }

        /* Restore border-radius for collapsed sections */
        .collapsible-section:not(.expanded) {
            border-radius: 8px;
        }
        /* Ensure header has rounded corners when section is collapsed */
        .collapsible-section:not(.expanded) .collapsible-header {
            border-radius: 8px;
            border-bottom: none; /* No bottom border when collapsed */
        }
        /* Ensure header has rounded top corners when expanded */
        .collapsible-section.expanded .collapsible-header {
            border-radius: 8px 8px 0 0;
        }


        /* Formular- und Button-Stile */
        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }

        body.theme-night .form-group label {
            color: #ccc;
        }

        .form-group input[type="text"],
        .form-group textarea,
        .form-group select {
            width: calc(100% - 22px); /* Padding berücksichtigen */
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1em;
            box-sizing: border-box; /* Padding und Border in der Breite enthalten */
            background-color: #fff;
            color: #333;
        }

        /* Summernote editor frame */
        .note-editor {
            width: 100% !important; /* Ensure full width */
            border: 1px solid #ccc !important;
            border-radius: 4px !important;
            box-sizing: border-box !important;
        }

        body.theme-night .form-group input[type="text"],
        body.theme-night .form-group select,
        body.theme-night .note-editor { /* Summernote editor frame dark mode */
            background-color: #005a7e;
            border-color: #007bb5;
            color: #fff;
        }

        /* Summernote specific dark mode adjustments */
        body.theme-night .note-toolbar {
            background-color: #005a7e !important;
            border-bottom: 1px solid #007bb5 !important;
        }
        body.theme-night .note-btn {
            background-color: #006690 !important;
            color: #fff !important;
            border-color: #007bb5 !important;
        }
        body.theme-night .note-btn:hover {
            background-color: #007bb5 !important;
        }
        body.theme-night .note-editable {
            background-color: #005a7e !important;
            color: #fff !important;
        }
        body.theme-night .note-statusbar {
            background-color: #005a7e !important;
            border-top: 1px solid #007bb5 !important;
        }
        body.theme-night .note-dropdown-menu {
            background-color: #005a7e !important;
            color: #fff !important;
        }
        body.theme-night .note-dropdown-menu li a {
            color: #fff !important;
        }
        body.theme-night .note-dropdown-menu li a:hover {
            background-color: #006690 !important;
        }


        .button-group {
            text-align: right;
            margin-top: 20px;
        }

        button, .button {
            padding: 10px 20px;
            border: none;
            border-radius: 50px; /* Changed to rounded corners */
            background-color: #007bff;
            color: white;
            font-size: 1em;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.2s ease; /* Added transform and box-shadow */
            text-decoration: none; /* Für .button Klasse */
            display: inline-block; /* Für .button Klasse */
            margin-left: 10px; /* Abstand zwischen Buttons */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Added shadow */
        }

        button:hover, .button:hover {
            background-color: #0056b3;
            transform: translateY(-2px); /* Lift effect on hover */
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3); /* Larger shadow on hover */
        }

        button.delete, .button.delete {
            background-color: #dc3545;
        }

        button.delete:hover, .button.delete:hover {
            background-color: #c82333;
        }

        button.edit, .button.edit {
            background-color: #ffc107;
            color: #333;
        }

        button.edit:hover, .button.edit:hover {
            background-color: #e0a800;
        }

        /* Icon buttons in table */
        .action-buttons button {
            background-color: transparent; /* Make background transparent */
            border: 1px solid transparent; /* Remove border */
            color: #007bff; /* Use primary color for icons */
            padding: 5px; /* Adjust padding for icon-only buttons */
            margin: 0 2px; /* Adjust margin */
            font-size: 1.1em; /* Make icons slightly larger */
            box-shadow: none; /* Remove shadow for small buttons */
            border-radius: 5px; /* Smaller rounded corners for table buttons */
        }

        .action-buttons button:hover {
            background-color: rgba(0, 123, 255, 0.1); /* Light hover background */
            border-color: #007bff; /* Add border on hover */
            transform: none; /* No lift effect for small buttons */
            box-shadow: none;
        }

        body.theme-night .action-buttons button {
            color: #7bbdff; /* Lighter color for icons in dark mode */
        }

        body.theme-night .action-buttons button:hover {
            background-color: rgba(123, 189, 255, 0.1);
            border-color: #7bbdff;
        }


        body.theme-night button, body.theme-night .button {
            background-color: #2a6177;
        }

        body.theme-night button:hover, body.theme-night .button:hover {
            background-color: #48778a;
        }

        body.theme-night button.delete, body.theme-night .button.delete {
            background-color: #dc3545;
        }

        body.theme-night button.delete:hover, body.theme-night .button.delete:hover {
            background-color: #c82333;
        }

        body.theme-night button.edit, body.theme-night .button.edit {
            background-color: #ffc107;
            color: #333; /* Textfarbe bleibt dunkel für Kontrast */
        }

        body.theme-night button.edit:hover, body.theme-night .button.edit:hover {
            background-color: #e0a800;
        }

        /* Comic-Liste und Tabelle */
        .archive-table-container {
            overflow-x: auto; /* Ermöglicht horizontales Scrollen bei kleinen Bildschirmen */
        }

        .archive-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .archive-table th, .archive-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }

        body.theme-night .archive-table th, body.theme-night .archive-table td {
            border-color: #005a7e;
        }

        .archive-table th {
            background-color: #f2f2f2;
            color: #333;
            font-weight: bold;
        }

        body.theme-night .archive-table th {
            background-color: #005a7e;
            color: #fff;
        }

        .archive-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        body.theme-night .archive-table tr:nth-child(even) {
            background-color: #004c6b;
        }

        .archive-table tr:hover {
            background-color: #f1f1f1;
        }

        body.theme-night .archive-table tr:hover {
            background-color: #006690;
        }

        .archive-table td .editable-field {
            width: 100%;
            padding: 5px;
            border: 1px solid #eee; /* Leichter Rand im Nicht-Bearbeitungsmodus */
            border-radius: 3px;
            box-sizing: border-box;
            background-color: transparent; /* Standardmäßig transparent */
            cursor: text; /* Zeigt an, dass es klickbar ist */
            min-height: 30px; /* Mindesthöhe für leere Felder */
            display: block; /* Stellt sicher, dass es die volle Breite einnimmt */
            color: #333; /* Standardtextfarbe */
        }

        body.theme-night .archive-table td .editable-field {
            border-color: #005a7e;
            color: #fff;
        }

        .archive-table td .editable-field:hover {
            border-color: #ccc; /* Rand beim Hover */
        }

        body.theme-night .archive-table td .editable-field:hover {
            border-color: #007bb5;
        }

        .archive-table td .editable-field.missing-info {
            border: 2px solid #dc3545; /* Roter Rand für fehlende Infos */
            background-color: #f8d7da; /* Hellroter Hintergrund */
        }

        body.theme-night .archive-table td .editable-field.missing-info {
            border-color: #ff4d4d; /* Helleres Rot */
            background-color: #721c24; /* Dunkleres Rot */
        }

        .archive-table td .actions {
            white-space: nowrap; /* Buttons bleiben in einer Zeile */
        }

        /* Responsive Anpassungen */
        @media (max-width: 768px) {
            .admin-container {
                padding: 10px;
                margin: 10px auto;
            }

            .form-group input[type="text"],
            .form-group textarea,
            .form-group select {
                width: calc(100% - 20px); /* Anpassung für kleinere Bildschirme */
            }

            .collapsible-header {
                padding: 10px 15px; /* Adjust padding for smaller screens */
                font-size: 1.2em;
            }

            .collapsible-content {
                padding: 0 15px; /* Adjust padding for smaller screens */
            }
            .collapsible-section.expanded .collapsible-content {
                padding-top: 15px;
                padding-bottom: 15px;
            }

            .archive-table th, .archive-table td {
                padding: 6px;
                font-size: 0.9em;
            }

            .action-buttons button {
                padding: 3px 6px;
                font-size: 0.8em;
                margin-left: 2px;
            }
        }

        /* Custom Confirmation Modal Styles */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1000; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto; /* 15% from the top and centered */
            padding: 20px;
            border: 1px solid #888;
            width: 80%; /* Could be more or less, depending on screen size */
            max-width: 500px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            text-align: center;
        }

        body.theme-night .modal-content {
            background-color: #00425c;
            border-color: #007bb5;
            color: #fff;
        }

        .modal-content button {
            margin: 10px;
            padding: 10px 20px;
            cursor: pointer;
        }
        .note-modal-backdrop {
            /*Fix Summernote backdrop Problem*/
            z-index: 99;
        }

    /* NEU: Fix für Summernote Tooltip-Positionierung */
    .note-tooltip {
        /* Setzt die Breite auf einen sinnvollen Wert. */
        width: auto !important;
        /* Erzwingt, dass die Höhe sich am Inhalt orientiert. */
        height: auto !important;
        /* Setzt eine eventuell geerbte Mindesthöhe zurück, die den Container aufbläht. */
        min-height: 0 !important;
        /* Verhindert, dass der Tooltip nach links aus dem Bild wandert. */
        left: auto !important;
        right: auto !important;
        /* KORREKTUR: Setzt eine normale Zeilenhöhe, um den vertikalen Versatz zu beheben. */
        line-height: 1.2 !important;
        /* Sorgt für einen kleinen Abstand zum Mauszeiger. */
        padding: 5px;
        white-space: nowrap;
        /* Verhindert unerwünschten Zeilenumbruch im Tooltip. */
    }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
EOT;

$viewportContent = 'width=device-width, initial-scale=1.0'; // Standard Viewport für Admin-Bereich

// Binde den gemeinsamen Header ein. Pfad relativ zu admin/
include __DIR__ . '/../src/layout/header.php';
?>

<div class="admin-container">
    <div id="message-box" class="message-box"></div>

    <section class="form-section collapsible-section" id="edit-form-section"> <!-- Standardmäßig eingeklappt -->
        <h2 class="collapsible-header" id="form-header">Neues Kapitel hinzufügen <i class="fas fa-chevron-right"></i>
        </h2>
        <div class="collapsible-content">
            <form method="POST">
                <!-- Hidden input for the action (add/edit/delete) -->
                <input type="hidden" name="action" id="form_action"
                    value="<?php echo htmlspecialchars($formAction); ?>">
                <!-- Hidden input to store the original chapter ID when editing -->
                <input type="hidden" name="original_chapter_id" id="original_chapter_id_hidden"
                    value="<?php echo htmlspecialchars($originalChapterId); ?>">

                <div class="form-group">
                    <label for="chapter_id_input">Kapitel ID:</label>
                    <input type="text" id="chapter_id_input" name="chapter_id"
                        value="<?php echo htmlspecialchars($editChapterId); ?>"
                        placeholder="Automatisch generieren, wenn leer">
                    <div class="checkbox-group" style="margin-top: 5px;">
                        <input type="checkbox" id="leave_id_empty_checkbox" name="leave_id_empty" <?php echo $leaveIdEmptyChecked ? 'checked' : ''; ?>>
                        <label for="leave_id_empty_checkbox" style="display: inline; font-weight: normal;">Kapitel ID
                            leer lassen ("")</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="title">Titel:</label>
                    <!-- GEÄNDERT: Input-Feld statt Textarea -->
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($editTitle); ?>"
                        placeholder="Titel hier eingeben...">
                </div>

                <div class="form-group">
                    <label for="description">Beschreibung:</label>
                    <textarea id="description" name="description"
                        rows="8"><?php echo htmlspecialchars($editDescription); ?></textarea>
                </div>

                <div class="button-group">
                    <button type="submit"
                        id="submit-button"><?php echo ($formAction === 'edit' ? 'Änderungen speichern' : 'Kapitel hinzufügen'); ?></button>
                    <button type="button" id="cancel-edit-button" class="button delete"
                        style="display: <?php echo ($formAction === 'edit' ? 'inline-block' : 'none'); ?>;">Abbrechen</button>
                </div>
            </form>
        </div>
    </section>

    <section class="archive-list-section collapsible-section expanded"> <!-- Standardmäßig ausgeklappt -->
        <h2 class="collapsible-header">Existierende Kapitel <i class="fas fa-chevron-down"></i></h2>
        <div class="collapsible-content">
            <div class="archive-table-container">
                <table class="archive-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Titel</th>
                            <th>Beschreibung</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($chapters)): ?>
                            <tr>
                                <td colspan="4">Es sind noch keine Archivkapitel vorhanden.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($chapters as $chapter):
                                // strip_tags mit erlaubten Tags, um nur reinen Text für die Prüfung zu erhalten
                                $isTitleMissing = empty(trim(strip_tags($chapter['title'] ?? '')));
                                $isDescriptionMissing = empty(trim(strip_tags($chapter['description'] ?? '', '<b><i><u><p><br>')));
                                $isMissingInfoRow = $isTitleMissing || $isDescriptionMissing;
                                ?>
                                <tr id="chapter-<?php echo htmlspecialchars($chapter['chapterId'] ?? ''); ?>"
                                    data-chapter-id="<?php echo htmlspecialchars($chapter['chapterId'] ?? ''); ?>"
                                    class="<?php echo $isMissingInfoRow ? 'missing-info-row' : ''; ?>">
                                    <td><?php echo htmlspecialchars($chapter['chapterId'] ?? 'N/A'); ?></td>
                                    <td><span
                                            class="editable-field chapter-title-display <?php echo $isTitleMissing ? 'missing-info' : ''; ?>"><?php echo htmlspecialchars($chapter['title'] ?? ''); ?></span>
                                    </td>
                                    <td><span
                                            class="editable-field chapter-description-display <?php echo $isDescriptionMissing ? 'missing-info' : ''; ?>"><?php echo $chapter['description'] ?? ''; ?></span>
                                    </td>
                                    <td class="action-buttons">
                                        <button type="button" class="edit-button button edit"
                                            data-id="<?php echo htmlspecialchars($chapter['chapterId'] ?? ''); ?>"
                                            data-title="<?php echo htmlspecialchars($chapter['title'] ?? ''); ?>"
                                            data-description="<?php echo htmlspecialchars($chapter['description'] ?? ''); ?>"
                                            title="Bearbeiten">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="delete-button button delete"
                                            data-id="<?php echo htmlspecialchars($chapter['chapterId'] ?? ''); ?>"
                                            title="Löschen">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <button type="button" id="add-new-chapter-button" class="button"><i class="fas fa-plus"></i> Neues Kapitel
                hinzufügen (+)</button>
        </div>
    </section>
</div>

<!-- Custom Confirmation Modal -->
<div id="customConfirmModal" class="modal">
    <div class="modal-content">
        <p id="confirmMessage"></p>
        <button id="confirmYes" class="button">Ja</button>
        <button id="confirmNo" class="button delete">Nein</button>
    </div>
</div>

<?php
// Binde den gemeinsamen Footer ein.
include __DIR__ . '/../src/layout/footer.php';
ob_end_flush();
?>