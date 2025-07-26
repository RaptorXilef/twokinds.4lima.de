<?php
// Start den Output Buffer als ALLERERSTE Zeile
ob_start();

// Starte die PHP-Sitzung. Notwendig, um den Anmeldestatus zu überprüfen.
session_start();

// Logout-Logik: Muss vor dem Sicherheitscheck erfolgen.
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
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
            $params["httponly"]
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
    // Wenn nicht angemeldet, zur Login-Seite weiterleiten.
    ob_end_clean(); // Output Buffer leeren, da wir umleiten
    header('Location: index.php');
    exit;
}

// Pfad zur JSON-Datei mit den Archivkapiteln
$archiveChaptersJsonPath = __DIR__ . '/../src/config/archive_chapters.json';

$message = '';
$messageType = ''; // 'success' oder 'error' oder 'info' oder 'warning'

/**
 * Lädt Kapitel-Metadaten aus einer JSON-Datei.
 * @param string $path Der Pfad zur JSON-Datei.
 * @return array Die dekodierten Daten als assoziatives Array (chapterId => data) oder ein leeres Array im Fehlerfall.
 */
function loadArchiveChapters(string $path): array
{
    if (!file_exists($path) || filesize($path) === 0) {
        return [];
    }
    $content = file_get_contents($path);
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Fehler beim Dekodieren von archive_chapters.json: " . json_last_error_msg());
        return [];
    }
    // Stelle sicher, dass es ein Array ist
    if (!is_array($data)) {
        return [];
    }
    // Sortiere nach chapterId, um Konsistenz zu gewährleisten
    usort($data, function ($a, $b) {
        return ($a['chapterId'] ?? 0) <=> ($b['chapterId'] ?? 0);
    });
    return $data;
}

/**
 * Speichert Kapitel-Metadaten in einer JSON-Datei.
 * @param string $path Der Pfad zur JSON-Datei.
 * @param array $data Die zu speichernden Daten.
 * @return bool True bei Erfolg, False bei Fehler.
 */
function saveArchiveChapters(string $path, array $data): bool
{
    // Sortiere die Daten vor dem Speichern nach chapterId
    usort($data, function ($a, $b) {
        return ($a['chapterId'] ?? 0) <=> ($b['chapterId'] ?? 0);
    });
    $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($jsonContent === false) {
        error_log("Fehler beim Kodieren von Archivdaten in JSON: " . json_last_error_msg());
        return false;
    }
    return file_put_contents($path, $jsonContent) !== false;
}

// Lade die aktuellen Kapiteldaten
$chapters = loadArchiveChapters($archiveChaptersJsonPath);

// Initialisiere Variablen für das Bearbeitungsformular
$editChapterId = '';
$editTitle = '';
$editDescription = '';
$formAction = 'add'; // Standardaktion ist "Hinzufügen"
$originalChapterId = ''; // Wird nur im Bearbeitungsmodus gesetzt

// === POST-Anfragen verarbeiten ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rich-Text-Editor-Inhalte von Summernote abrufen
    $postedTitle = $_POST['title'] ?? '';
    $postedDescription = $_POST['description'] ?? '';
    $postedChapterId = $_POST['chapter_id'] ?? ''; // Die vom Benutzer eingegebene ID
    $originalChapterId = $_POST['original_chapter_id'] ?? ''; // Die ursprüngliche ID im Bearbeitungsmodus

    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        switch ($action) {
            case 'add':
                $newChapterId = $postedChapterId;
                if (empty($newChapterId)) {
                    // Auto-generate if not provided
                    $maxId = 0;
                    if (!empty($chapters)) {
                        foreach ($chapters as $chapter) {
                            if (isset($chapter['chapterId']) && $chapter['chapterId'] > $maxId) {
                                $maxId = $chapter['chapterId'];
                            }
                        }
                    }
                    $newChapterId = $maxId + 1;
                } else {
                    // Validate if manually provided ID is unique and numeric
                    if (!is_numeric($newChapterId) || $newChapterId <= 0) {
                        $message = 'Fehler: Kapitel ID muss eine positive Zahl sein.';
                        $messageType = 'error';
                        header('Location: ' . $_SERVER['PHP_SELF'] . '?message=' . urlencode($message) . '&type=' . urlencode($messageType));
                        exit;
                    }
                    foreach ($chapters as $chapter) {
                        if (isset($chapter['chapterId']) && $chapter['chapterId'] == $newChapterId) {
                            $message = 'Fehler: Kapitel ID existiert bereits. Bitte wählen Sie eine andere ID.';
                            $messageType = 'error';
                            header('Location: ' . $_SERVER['PHP_SELF'] . '?message=' . urlencode($message) . '&type=' . urlencode($messageType));
                            exit;
                        }
                    }
                }

                $newChapter = [
                    'chapterId' => (int) $newChapterId, // Cast to int
                    'title' => $postedTitle,
                    'description' => $postedDescription
                ];
                $chapters[] = $newChapter;

                if (saveArchiveChapters($archiveChaptersJsonPath, $chapters)) {
                    $message = 'Kapitel erfolgreich hinzugefügt!';
                    $messageType = 'success';
                } else {
                    $message = 'Fehler beim Hinzufügen des Kapitels.';
                    $messageType = 'error';
                }
                break;

            case 'edit':
                // The ID to find in the existing array is originalChapterId
                // The new ID for this chapter will be postedChapterId
                $foundIndex = -1;
                foreach ($chapters as $index => $chapter) {
                    if (isset($chapter['chapterId']) && $chapter['chapterId'] == $originalChapterId) {
                        $foundIndex = $index;
                        break;
                    }
                }

                if ($foundIndex !== -1) {
                    // If the ID is being changed, validate new ID
                    if ($postedChapterId != $originalChapterId) {
                        if (!is_numeric($postedChapterId) || $postedChapterId <= 0) {
                            $message = 'Fehler: Kapitel ID muss eine positive Zahl sein.';
                            $messageType = 'error';
                            header('Location: ' . $_SERVER['PHP_SELF'] . '?message=' . urlencode($message) . '&type=' . urlencode($messageType));
                            exit;
                        }
                        foreach ($chapters as $index => $chapter) {
                            if ($index !== $foundIndex && isset($chapter['chapterId']) && $chapter['chapterId'] == $postedChapterId) {
                                $message = 'Fehler: Die neue Kapitel ID existiert bereits. Bitte wählen Sie eine andere ID.';
                                $messageType = 'error';
                                header('Location: ' . $_SERVER['PHP_SELF'] . '?message=' . urlencode($message) . '&type=' . urlencode($messageType));
                                exit;
                            }
                        }
                    }

                    // Update the chapter data
                    $chapters[$foundIndex]['chapterId'] = (int) $postedChapterId;
                    $chapters[$foundIndex]['title'] = $postedTitle;
                    $chapters[$foundIndex]['description'] = $postedDescription;

                    if (saveArchiveChapters($archiveChaptersJsonPath, $chapters)) {
                        $message = 'Kapitel erfolgreich aktualisiert!';
                        $messageType = 'success';
                    } else {
                        $message = 'Fehler beim Aktualisieren des Kapitels.';
                        $messageType = 'error';
                    }
                } else {
                    $message = 'Fehler: Ursprüngliches Kapitel zum Bearbeiten nicht gefunden.';
                    $messageType = 'error';
                }
                break;

            case 'delete':
                $chapterIdToDelete = $_POST['chapter_id'] ?? null;
                $chapters = array_filter($chapters, function ($chapter) use ($chapterIdToDelete) {
                    return (isset($chapter['chapterId']) && $chapter['chapterId'] != $chapterIdToDelete);
                });

                if (saveArchiveChapters($archiveChaptersJsonPath, array_values($chapters))) { // array_values um Indizes neu zu ordnen
                    $message = 'Kapitel erfolgreich gelöscht!';
                    $messageType = 'success';
                } else {
                    $message = 'Fehler beim Löschen des Kapitels.';
                    $messageType = 'error';
                }
                break;
        }
        // Nach einer POST-Anfrage Redirect, um Formular-Resubmission zu vermeiden
        header('Location: ' . $_SERVER['PHP_SELF'] . '?message=' . urlencode($message) . '&type=' . urlencode($messageType));
        exit;
    }
}

// === GET-Parameter für Bearbeitung und Nachrichten verarbeiten ===
if (isset($_GET['edit_id'])) {
    $editChapterId = $_GET['edit_id'];
    $formAction = 'edit';
    foreach ($chapters as $chapter) {
        if (isset($chapter['chapterId']) && $chapter['chapterId'] == $editChapterId) {
            $editTitle = $chapter['title'];
            $editDescription = $chapter['description'];
            $originalChapterId = $editChapterId; // Setze die ursprüngliche ID für den Bearbeitungsmodus
            break;
        }
    }
}

// Nachrichten aus GET-Parametern anzeigen
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $messageType = htmlspecialchars($_GET['type']);
}

// Setze Parameter für den Header.
$pageTitle = 'Archiv Daten Editor';
$pageHeader = 'Archiv Daten Editor';
$siteDescription = 'Seite zum Bearbeiten der Archivkapitel-Daten.';
$robotsContent = 'noindex, nofollow'; // Diese Seite soll nicht indexiert werden

// Heredoc-Syntax für $additionalScripts
$additionalScripts = <<<EOT
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const editFormSection = document.getElementById("edit-form-section");
            const formHeader = document.getElementById("form-header");
            const submitButton = document.getElementById("submit-button");
            const cancelEditButton = document.getElementById("cancel-edit-button");
            const chapterIdInput = document.getElementById("chapter_id_input"); // Neues Feld für die ID
            const originalChapterIdHidden = document.getElementById("original_chapter_id_hidden"); // Hidden input for original ID
            const formActionInput = document.getElementById("form_action");
            const titleTextarea = $("#title"); // jQuery-Objekt für Summernote
            const descriptionTextarea = $("#description"); // jQuery-Objekt für Summernote

            // Referenz zum Icon im Formular-Header
            const formHeaderIcon = formHeader ? formHeader.querySelector("i") : null;

            // Funktion zur Initialisierung von Summernote
            function initializeSummernote() {
                // Nur initialisieren, wenn es noch nicht initialisiert wurde
                if (!titleTextarea.data("summernote")) {
                    titleTextarea.summernote({
                        placeholder: "Titel hier eingeben...",
                        tabsize: 2,
                        height: 120,
                        toolbar: [
                            ["style", ["style"]],
                            ["font", ["bold", "italic", "underline", "clear"]],
                            ["color", ["color"]],
                            ["para", ["ul", "ol", "paragraph"]],
                            ["table", ["table"]],
                            ["insert", ["link"]],
                            ["view", ["fullscreen", "codeview", "help"]]
                        ]
                    });
                }
                if (!descriptionTextarea.data("summernote")) {
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
                        ]
                    });
                }
            }

            // Funktion zum Zerstören von Summernote-Instanzen
            function destroySummernote() {
                if (titleTextarea.data("summernote")) {
                    titleTextarea.summernote("destroy");
                }
                if (descriptionTextarea.data("summernote")) {
                    descriptionTextarea.summernote("destroy");
                }
            }

            // Funktion zum Zurücksetzen des Formulars
            function resetForm() {
                chapterIdInput.value = "";
                originalChapterIdHidden.value = ""; // Auch die ursprüngliche ID zurücksetzen
                titleTextarea.summernote("code", ""); // Summernote spezifisch
                descriptionTextarea.summernote("code", ""); // Summernote spezifisch
                formActionInput.value = "add";
                formHeader.textContent = "Neues Kapitel hinzufügen"; // Angepasste Überschrift
                submitButton.textContent = "Kapitel hinzufügen";
                cancelEditButton.style.display = "none"; // Verstecke Abbrechen-Button
                
                // Summernote zerstören, wenn Formular eingeklappt wird
                if (editFormSection.classList.contains("expanded")) {
                    destroySummernote();
                }
                editFormSection.classList.remove("expanded"); // Klappe Formular ein
                // Sicherstellen, dass das Icon existiert, bevor darauf zugegriffen wird
                if (formHeaderIcon) {
                    formHeaderIcon.classList.remove("fa-chevron-down");
                    formHeaderIcon.classList.add("fa-chevron-right");
                } else {
                    console.error("Error: Collapsible header icon for form section not found in resetForm.");
                }
            }

            // Logik für den "Bearbeiten" Button
            document.querySelectorAll(".edit-button").forEach(button => {
                button.addEventListener("click", function() {
                    const chapterId = this.dataset.id;
                    const title = this.dataset.title;
                    const description = this.dataset.description;

                    chapterIdInput.value = chapterId; // ID im editierbaren Feld anzeigen
                    originalChapterIdHidden.value = chapterId; // Ursprüngliche ID speichern
                    
                    // Summernote initialisieren, falls noch nicht geschehen
                    initializeSummernote();
                    titleTextarea.summernote("code", title); // Summernote spezifisch
                    descriptionTextarea.summernote("code", description); // Summernote spezifisch
                    formActionInput.value = "edit";
                    formHeader.textContent = "Kapitel bearbeiten (ID: " + chapterId + ")";
                    submitButton.textContent = "Änderungen speichern";
                    cancelEditButton.style.display = "inline-block"; // Zeige Abbrechen-Button

                    // Scrolle zum Formular und klappe es aus
                    if (!editFormSection.classList.contains("expanded")) {
                        editFormSection.classList.add("expanded");
                        if (formHeaderIcon) { // Sicherstellen, dass das Icon existiert
                            formHeaderIcon.classList.remove("fa-chevron-right");
                            formHeaderIcon.classList.add("fa-chevron-down");
                        }
                    }
                    editFormSection.scrollIntoView({ behavior: "smooth" });
                });
            });

            // Logik für den "Abbrechen" Button
            cancelEditButton.addEventListener("click", function() {
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
            }

            confirmYes.onclick = function() {
                customConfirmModal.style.display = "none";
                if (deleteActionCallback) {
                    deleteActionCallback(true);
                }
            };

            confirmNo.onclick = function() {
                customConfirmModal.style.display = "none";
                if (deleteActionCallback) {
                    deleteActionCallback(false);
                }
            };

            // Logik für den "Löschen" Button mit Custom Confirm
            document.querySelectorAll(".delete-button").forEach(button => {
                button.addEventListener("click", function(event) {
                    event.preventDefault();
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
                        }
                    } else {
                        icon.classList.remove("fa-chevron-down");
                        icon.classList.add("fa-chevron-right");
                    }
                }


                header.addEventListener("click", function() {
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
                            }
                            // Scroll to the form if it's expanded
                            if (isFormSection) {
                                section.scrollIntoView({ behavior: "smooth" });
                            }
                        } else {
                            icon.classList.remove("fa-chevron-down");
                            icon.classList.add("fa-chevron-right");
                            // If it's the form section and it's collapsed, destroy Summernote
                            if (isFormSection && wasExpanded) { // Nur zerstören, wenn es vorher expanded war
                                destroySummernote();
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

            if (msg && type && messageBoxElement) {
                messageBoxElement.textContent = msg;
                messageBoxElement.className = "message-box " + type;
                messageBoxElement.style.display = "block";
                // Clean URL parameters after displaying message
                history.replaceState({}, document.title, window.location.pathname);
                setTimeout(() => { messageBoxElement.style.display = "none"; }, 5000);
            }

            // Add new chapter button functionality
            document.getElementById("add-new-chapter-button").addEventListener("click", function() {
                resetForm();
                // Beim Hinzufügen eines neuen Kapitels Summernote initialisieren und Formular aufklappen
                editFormSection.classList.add("expanded");
                if (formHeaderIcon) { // Sicherstellen, dass das Icon existiert
                    formHeaderIcon.classList.remove("fa-chevron-right");
                    formHeaderIcon.classList.add("fa-chevron-down");
                }
                initializeSummernote(); // Summernote initialisieren
                editFormSection.scrollIntoView({ behavior: "smooth" });
                formHeader.textContent = "Neues Kapitel hinzufügen"; // Setze die Überschrift explizit
            });

            // Initialen Zustand des Formulars setzen (z.B. wenn edit_id in URL ist)
            // Dies muss nach allen Event Listeners passieren
            if ("<?php echo $formAction; ?>" === "edit") {
                // Wenn wir im Bearbeitungsmodus starten, klappe das Formular auf und setze die Daten
                editFormSection.classList.add("expanded");
                if (formHeaderIcon) { // Sicherstellen, dass das Icon existiert
                    formHeaderIcon.classList.remove("fa-chevron-right");
                    formHeaderIcon.classList.add("fa-chevron-down");
                }
                initializeSummernote(); // Summernote initialisieren
                titleTextarea.summernote("code", "<?php echo htmlspecialchars($editTitle); ?>");
                descriptionTextarea.summernote("code", "<?php echo htmlspecialchars($editDescription); ?>");
                chapterIdInput.value = "<?php echo htmlspecialchars($editChapterId); ?>";
                originalChapterIdHidden.value = "<?php echo htmlspecialchars($originalChapterId); ?>";
                formHeader.textContent = "Kapitel bearbeiten (ID: <?php echo htmlspecialchars($editChapterId); ?>)";
                submitButton.textContent = "Änderungen speichern";
                cancelEditButton.style.display = "inline-block";
            } else {
                // Wenn wir im Hinzufügen-Modus starten, stelle sicher, dass die ID-Anzeige ausgeblendet ist
                // (Nicht mehr nötig, da das Feld immer sichtbar ist, aber für Konsistenz)
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
        }

        .collapsible-section.expanded .collapsible-content {
            max-height: 4800px; /* Sufficiently large to show content */
            padding-top: 20px; /* Restore top padding */
            padding-bottom: 20px; /* Restore bottom padding */
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
            border-radius: 5px;
            background-color: #007bff;
            color: white;
            font-size: 1em;
            cursor: pointer;
            transition: background-color 0.3s ease;
            text-decoration: none; /* Für .button Klasse */
            display: inline-block; /* Für .button Klasse */
            margin-left: 10px; /* Abstand zwischen Buttons */
        }

        button:hover, .button:hover {
            background-color: #0056b3;
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
        }

        .action-buttons button:hover {
            background-color: rgba(0, 123, 255, 0.1); /* Light hover background */
            border-color: #007bff; /* Add border on hover */
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
                </div>

                <div class="form-group">
                    <label for="title">Titel:</label>
                    <textarea id="title" name="title" rows="4"><?php echo htmlspecialchars($editTitle); ?></textarea>
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
                                $isTitleMissing = empty(trim(strip_tags($chapter['title'] ?? '', '<b><i><u><p><br>')));
                                $isDescriptionMissing = empty(trim(strip_tags($chapter['description'] ?? '', '<b><i><u><p><br>')));
                                $isMissingInfoRow = $isTitleMissing || $isDescriptionMissing;
                                ?>
                                <tr data-chapter-id="<?php echo htmlspecialchars($chapter['chapterId'] ?? ''); ?>"
                                    class="<?php echo $isMissingInfoRow ? 'missing-info-row' : ''; ?>">
                                    <td><?php echo htmlspecialchars($chapter['chapterId'] ?? 'N/A'); ?></td>
                                    <td><span
                                            class="editable-field chapter-title-display <?php echo $isTitleMissing ? 'missing-info' : ''; ?>"><?php echo $chapter['title'] ?? ''; ?></span>
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