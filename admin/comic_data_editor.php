<?php
/**
 * Dies ist die Administrationsseite zum Bearbeiten der comic_var.json Konfigurationsdatei.
 * Sie ermöglicht das Hinzufügen, Bearbeiten und Löschen von Comic-Einträgen
 * über eine benutzerfreundliche Oberfläche.
 *
 * Zusätzlich werden fehlende Comic-IDs aus den Bildordnern automatisch hinzugefügt
 * und unvollständige Einträge visuell hervorgehoben.
 * Ein Bericht über fehlende Informationen wird am Ende der Seite angezeigt.
 */

// Starte den Output Buffer als ALLERERSTE Zeile, um wirklich jede Ausgabe abzufangen.
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
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
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

// Pfade zu den benötigten Ressourcen
$headerPath = __DIR__ . '/../src/layout/header.php';
$footerPath = __DIR__ . '/../src/layout/footer.php';
$comicVarJsonPath = __DIR__ . '/../src/components/comic_var.json';
$comicLowresDirPath = __DIR__ . '/../assets/comic_lowres/';
$comicHiresDirPath = __DIR__ . '/../assets/comic_hires/';

// Setze Parameter für den Header.
$pageTitle = 'Comic Daten Editor';
$pageHeader = 'Comic Daten Editor';
$robotsContent = 'noindex, nofollow'; // Admin-Seiten nicht crawlen

$message = '';
$messageType = ''; // 'success' or 'error'

// Optionen für 'type' und 'chapter'
$comicTypeOptions = ['Comicseite vom ', 'Lückenfüller']; // "Comicseite vom " ist der Standard für neue Seiten
$chapterOptions = range(1, 100); // Beispiel: Kapitel 1 bis 100

/**
 * Lädt Comic-Metadaten aus einer JSON-Datei.
 * @param string $path Der Pfad zur JSON-Datei.
 * @return array Die dekodierten Daten als assoziatives Array oder ein leeres Array im Fehlerfall.
 */
function loadComicData(string $path): array {
    if (!file_exists($path)) {
        return [];
    }
    $content = file_get_contents($path);
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Fehler beim Dekodieren von comic_var.json: " . json_last_error_msg());
        return [];
    }
    return is_array($data) ? $data : [];
}

/**
 * Speichert Comic-Daten in die JSON-Datei, alphabetisch sortiert.
 * @param string $path Der Pfad zur JSON-Datei.
 * @param array $data Die zu speichernden Daten.
 * @return bool True bei Erfolg, False bei Fehler.
 */
function saveComicData(string $path, array $data): bool {
    // Sortiere das Array alphabetisch nach Schlüsseln (Comic-IDs)
    ksort($data);
    $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($jsonContent === false) {
        error_log("Fehler beim Kodieren von Comic-Daten: " . json_last_error_msg());
        return false;
    }
    if (file_put_contents($path, $jsonContent) === false) {
        error_log("Fehler beim Schreiben der Comic-Daten nach " . $path);
        return false;
    }
    return true;
}

/**
 * Scannt die Comic-Bildverzeichnisse nach vorhandenen Comic-IDs.
 * @param string $lowresDir Pfad zum lowres-Verzeichnis.
 * @param string $hiresDir Pfad zum hires-Verzeichnis.
 * @return array Eine Liste eindeutiger Comic-IDs (Dateinamen ohne Erweiterung), sortiert.
 */
function getComicIdsFromImages(string $lowresDir, string $hiresDir): array {
    $imageIds = [];
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif'];

    $dirs = [$lowresDir, $hiresDir];
    foreach ($dirs as $dir) {
        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $file) {
                // Ignoriere . und .. sowie versteckte Dateien und "in_translation" Bilder
                if ($file === '.' || $file === '..' || substr($file, 0, 1) === '.' || strpos($file, 'in_translation') !== false) {
                    continue;
                }
                $info = pathinfo($file);
                if (isset($info['filename']) && preg_match('/^\d{8}$/', $info['filename']) && isset($info['extension']) && in_array(strtolower($info['extension']), $imageExtensions)) {
                    $imageIds[$info['filename']] = true; // Verwende assoziatives Array für Eindeutigkeit
                }
            }
        }
    }
    $sortedIds = array_keys($imageIds);
    sort($sortedIds);
    return $sortedIds;
}


// Verarbeite POST-Anfragen zum Speichern
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_comic_data'])) {
    $updatedComicData = [];
    if (isset($_POST['comic_id']) && is_array($_POST['comic_id'])) {
        foreach ($_POST['comic_id'] as $index => $comicId) {
            $comicId = trim($comicId);
            if (empty($comicId)) {
                continue; // Überspringe leere IDs
            }

            $type = isset($_POST['comic_type'][$index]) ? trim($_POST['comic_type'][$index]) : '';
            $name = isset($_POST['comic_name'][$index]) ? trim($_POST['comic_name'][$index]) : '';
            $transcript = isset($_POST['comic_transcript'][$index]) ? $_POST['comic_transcript'][$index] : ''; // HTML-Inhalt
            $chapter = isset($_POST['comic_chapter'][$index]) ? (int)$_POST['comic_chapter'][$index] : null;

            // Validierung für Chapter (muss eine Zahl sein)
            if (!is_numeric($chapter) || $chapter <= 0) {
                $chapter = null; // Setze auf null oder einen Standardwert, wenn ungültig
            }

            $updatedComicData[$comicId] = [
                'type' => $type,
                'name' => $name,
                'transcript' => $transcript,
                'chapter' => $chapter
            ];
        }
    }

    if (saveComicData($comicVarJsonPath, $updatedComicData)) {
        $message = 'Comic-Daten erfolgreich gespeichert!';
        $messageType = 'success';
    } else {
        $message = 'Fehler beim Speichern der Comic-Daten.';
        $messageType = 'error';
    }
}

// Lade die aktuelle Konfiguration für die Anzeige
$currentComicData = loadComicData($comicVarJsonPath);
$imageComicIds = getComicIdsFromImages($comicLowresDirPath, $comicHiresDirPath);

// Füge fehlende Comic-IDs aus den Bildern hinzu
foreach ($imageComicIds as $id) {
    if (!isset($currentComicData[$id])) {
        $currentComicData[$id] = [
            'type' => '', // Leer lassen für neue Einträge
            'name' => '', // Leer lassen
            'transcript' => '', // Leer lassen
            'chapter' => null // Leer lassen
        ];
    }
}

// Sortiere die Daten für die Anzeige nach Comic-ID
ksort($currentComicData);

// Bericht über unvollständige Informationen
$incompleteInfoReport = [];
foreach ($currentComicData as $id => $data) {
    $missingFields = [];
    if (empty($data['type'])) {
        $missingFields[] = 'type';
    }
    if (empty($data['name'])) {
        $missingFields[] = 'name';
    }
    if (empty($data['transcript'])) {
        $missingFields[] = 'transcript';
    }
    if ($data['chapter'] === null || $data['chapter'] <= 0) {
        $missingFields[] = 'chapter';
    }

    if (!empty($missingFields)) {
        $incompleteInfoReport[$id] = $missingFields;
    }
}

// Binde den gemeinsamen Header ein.
if (file_exists($headerPath)) {
    include $headerPath;
} else {
    die('Fehler: Header-Datei nicht gefunden. Pfad: ' . htmlspecialchars($headerPath));
}
?>

<style>
    /* Allgemeine Tabellenstile */
    .comic-data-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    .comic-data-table th, .comic-data-table td {
        border: 1px solid #ccc;
        padding: 8px;
        text-align: left;
        vertical-align: top; /* Für Textarea */
    }
    .comic-data-table th {
        background-color: #ddead7;
    }
    .comic-data-table tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    .comic-data-table input[type="text"],
    .comic-data-table input[type="number"],
    .comic-data-table select,
    .comic-data-table textarea {
        width: 100%;
        padding: 5px;
        box-sizing: border-box;
        border: 1px solid #ddd;
        border-radius: 3px;
    }
    .comic-data-table textarea {
        min-height: 80px; /* Mindesthöhe für Transcript */
        resize: vertical; /* Nur vertikal skalierbar */
        font-family: 'Open Sans', sans-serif; /* Konsistente Schriftart */
        font-size: 15px;
    }
    .comic-data-table button.remove-row {
        background-color: #f44336;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 3px;
        cursor: pointer;
    }
    .comic-data-table button.remove-row:hover {
        background-color: #d32f2f;
    }
    .button-container {
        margin-top: 20px;
        text-align: right;
    }
    .button-container .button {
        margin-left: 10px;
    }
    .message-box {
        margin-top: 20px;
        padding: 10px;
        border-radius: 5px;
        color: #155724; /* Standardfarbe für Erfolg */
        border: 1px solid #c3e6cb; /* Standardfarbe für Erfolg */
        background-color: #d4edda; /* Standardfarbe für Erfolg */
    }
    .message-box.error {
        color: #721c24;
        border-color: #f5c6cb;
        background-color: #f8d7da;
    }
    /* Hervorhebung für unvollständige Einträge */
    .incomplete-entry {
        background-color: #f8d7da !important; /* Rosa/Rot */
    }
    /* Dark Theme Anpassungen */
    body.theme-night .comic-data-table th {
        background-color: #48778a;
        color: #fff;
        border-color: #002b3c;
    }
    body.theme-night .comic-data-table td {
        border-color: #002b3c;
    }
    body.theme-night .comic-data-table tr:nth-child(even) {
        background-color: #00334c;
    }
    body.theme-night .comic-data-table input[type="text"],
    body.theme-night .comic-data-table input[type="number"],
    body.theme-night .comic-data-table select,
    body.theme-night .comic-data-table textarea {
        background-color: #2a6177;
        color: #fff;
        border-color: #002b3c;
    }
    body.theme-night .comic-data-table button.remove-row {
        background-color: #a00;
    }
    body.theme-night .comic-data-table button.remove-row:hover {
        background-color: #c00;
    }
    body.theme-night .message-box {
        color: #d4edda; /* Textfarbe für Erfolg im Dark Theme */
        border-color: #2a6177;
        background-color: #00334c;
    }
    body.theme-night .message-box.error {
        color: #f5c6cb;
        border-color: #721c24;
        background-color: #5a0000;
    }
    body.theme-night .incomplete-entry {
        background-color: #721c24 !important; /* Dunkleres Rot für Dark Mode */
    }

    /* Transcript Editor Buttons */
    .transcript-editor-toolbar {
        margin-bottom: 5px;
        display: flex;
        gap: 5px;
        flex-wrap: wrap; /* Für kleinere Bildschirme */
    }
    .transcript-editor-toolbar button {
        padding: 4px 8px;
        border: 1px solid #ccc;
        background-color: #f0f0f0;
        border-radius: 3px;
        cursor: pointer;
        font-size: 0.9em;
    }
    .transcript-editor-toolbar button:hover {
        background-color: #e0e0e0;
    }
    body.theme-night .transcript-editor-toolbar button {
        background-color: #2a6177;
        color: #fff;
        border-color: #002b3c;
    }
    body.theme-night .transcript-editor-toolbar button:hover {
        background-color: #48778a;
    }
    .incomplete-report {
        margin-top: 30px;
        padding: 15px;
        border: 1px solid #f5c6cb;
        background-color: #f8d7da;
        border-radius: 5px;
        color: #721c24;
    }
    body.theme-night .incomplete-report {
        background-color: #5a0000;
        border-color: #721c24;
        color: #f5c6cb;
    }
</style>

<section>
    <h2 class="page-header">Comic Daten bearbeiten</h2>

    <?php if (!empty($message)): ?>
        <div class="message-box <?php echo $messageType; ?>">
            <p><?php echo htmlspecialchars($message); ?></p>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <table class="comic-data-table" id="comic-data-editor-table">
            <thead>
                <tr>
                    <th>Comic ID</th>
                    <th>Typ</th>
                    <th>Name</th>
                    <th>Transkript</th>
                    <th>Kapitel</th>
                    <th>Aktion</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($currentComicData)): ?>
                    <?php foreach ($currentComicData as $id => $data):
                        $isComplete = empty($incompleteInfoReport[$id]);
                        $rowClass = $isComplete ? '' : 'incomplete-entry';
                    ?>
                        <tr class="<?php echo $rowClass; ?>" data-comic-id="<?php echo htmlspecialchars($id); ?>">
                            <td><input type="text" name="comic_id[]" value="<?php echo htmlspecialchars($id); ?>" readonly></td>
                            <td>
                                <select name="comic_type[]">
                                    <?php foreach ($comicTypeOptions as $option): ?>
                                        <option value="<?php echo htmlspecialchars($option); ?>" <?php echo ($data['type'] == $option) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($option); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="text" name="comic_name[]" value="<?php echo htmlspecialchars($data['name']); ?>"></td>
                            <td>
                                <div class="transcript-editor-toolbar">
                                    <button type="button" class="format-button" data-tag="b"><b>B</b></button>
                                    <button type="button" class="format-button" data-tag="i"><i>I</i></button>
                                    <button type="button" class="format-button" data-tag="u"><u>U</u></button>
                                    <button type="button" class="format-button" data-tag="p">¶</button>
                                    <button type="button" class="format-button" data-tag="br">↵</button>
                                </div>
                                <textarea name="comic_transcript[]" class="transcript-textarea"><?php echo htmlspecialchars($data['transcript']); ?></textarea>
                            </td>
                            <td>
                                <input type="number" name="comic_chapter[]" value="<?php echo htmlspecialchars($data['chapter'] ?? ''); ?>" min="1">
                            </td>
                            <td><button type="button" class="remove-row">Entfernen</button></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr id="no-entries-row">
                        <td colspan="6" style="text-align: center;">Keine Comic-Einträge vorhanden. Füge neue hinzu oder lade Bilder hoch.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="button-container">
            <button type="button" id="add-new-comic-entry" class="button">Neuen Comic-Eintrag hinzufügen (+)</button>
            <button type="submit" name="save_comic_data" class="button">Änderungen speichern</button>
        </div>
    </form>

    <?php if (!empty($incompleteInfoReport)): ?>
        <div class="incomplete-report">
            <h3>Informationen fehlen bei:</h3>
            <ul>
                <?php foreach ($incompleteInfoReport as $id => $missingFields): ?>
                    <li><strong><?php echo htmlspecialchars($id); ?></strong>: <?php echo implode(', ', $missingFields); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tableBody = document.querySelector('#comic-data-editor-table tbody');
    const addEntryButton = document.getElementById('add-new-comic-entry');
    const noEntriesRow = document.getElementById('no-entries-row');

    // Optionen für die Dropdowns (müssen im JS wiederholt werden, da PHP-Variablen nicht direkt zugänglich sind)
    const comicTypeOptions = <?php echo json_encode($comicTypeOptions); ?>;
    const chapterOptions = <?php echo json_encode($chapterOptions); ?>;

    // Funktion zum Hinzufügen einer neuen Zeile
    function addRow(comic = {id: '', type: '', name: '', transcript: '', chapter: null}, isNew = true) {
        // Wenn die "Keine Einträge vorhanden"-Zeile existiert, entfernen
        if (noEntriesRow) {
            noEntriesRow.remove();
        }

        const newRow = document.createElement('tr');
        // Neue Einträge sind initial unvollständig, daher die Klasse
        if (isNew) {
            newRow.classList.add('incomplete-entry');
        }
        
        let typeOptionsHtml = '';
        comicTypeOptions.forEach(option => {
            typeOptionsHtml += `<option value="${htmlspecialchars(option)}" ${comic.type === option ? 'selected' : ''}>${htmlspecialchars(option)}</option>`;
        });

        let chapterOptionsHtml = '';
        chapterOptions.forEach(option => {
            chapterOptionsHtml += `<option value="${htmlspecialchars(option)}" ${comic.chapter == option ? 'selected' : ''}>${htmlspecialchars(option)}</option>`;
        });

        newRow.innerHTML = `
            <td><input type="text" name="comic_id[]" value="${htmlspecialchars(comic.id)}" ${isNew ? '' : 'readonly'}></td>
            <td>
                <select name="comic_type[]">
                    <option value="" ${comic.type === '' ? 'selected' : ''}>-- Auswählen --</option>
                    ${typeOptionsHtml}
                </select>
            </td>
            <td><input type="text" name="comic_name[]" value="${htmlspecialchars(comic.name)}"></td>
            <td>
                <div class="transcript-editor-toolbar">
                    <button type="button" class="format-button" data-tag="b"><b>B</b></button>
                    <button type="button" class="format-button" data-tag="i"><i>I</i></button>
                    <button type="button" class="format-button" data-tag="u"><u>U</u></button>
                    <button type="button" class="format-button" data-tag="p">¶</button>
                    <button type="button" class="format-button" data-tag="br">↵</button>
                </div>
                <textarea name="comic_transcript[]" class="transcript-textarea">${htmlspecialchars(comic.transcript)}</textarea>
            </td>
            <td>
                <input type="number" name="comic_chapter[]" value="${htmlspecialchars(comic.chapter ?? '')}" min="1">
            </td>
            <td><button type="button" class="remove-row">Entfernen</button></td>
        `;
        tableBody.appendChild(newRow);

        // Füge Event Listener für die neuen Format-Buttons hinzu
        const newTextarea = newRow.querySelector('.transcript-textarea');
        newRow.querySelectorAll('.format-button').forEach(button => {
            button.addEventListener('click', function() {
                applyFormatting(newTextarea, button.dataset.tag);
            });
        });

        // Event Listener für Input-Änderungen, um die "incomplete-entry" Klasse zu aktualisieren
        newRow.querySelectorAll('input, select, textarea').forEach(input => {
            input.addEventListener('input', updateRowCompleteness);
        });

        // Initial die Vollständigkeit der neuen Zeile prüfen
        updateRowCompleteness.call({target: newRow.querySelector('input, select, textarea')});
    }

    // Event Listener für "Neuen Comic-Eintrag hinzufügen" Button
    addEntryButton.addEventListener('click', function() {
        addRow();
    });

    // Event Listener für "Entfernen" Buttons (Delegation)
    tableBody.addEventListener('click', function(event) {
        if (event.target.classList.contains('remove-row')) {
            event.target.closest('tr').remove();
            // Wenn keine Zeilen mehr vorhanden, die "Keine Einträge vorhanden"-Zeile wieder hinzufügen
            if (tableBody.children.length === 0) {
                const emptyRow = document.createElement('tr');
                emptyRow.id = 'no-entries-row';
                emptyRow.innerHTML = '<td colspan="6" style="text-align: center;">Keine Comic-Einträge vorhanden. Füge neue hinzu oder lade Bilder hoch.</td>';
                tableBody.appendChild(emptyRow);
            }
        }
    });

    // Funktion zum Anwenden von Formatierungen im Textarea
    function applyFormatting(textarea, tag) {
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const selectedText = textarea.value.substring(start, end);
        let replacement = '';

        switch (tag) {
            case 'b':
                replacement = `<b>${selectedText}</b>`;
                break;
            case 'i':
                replacement = `<i>${selectedText}</i>`;
                break;
            case 'u':
                replacement = `<u>${selectedText}</u>`;
                break;
            case 'p':
                replacement = `<p>${selectedText}</p>`;
                break;
            case 'br':
                replacement = `${selectedText}<br>`;
                break;
            default:
                return;
        }

        textarea.value = textarea.value.substring(0, start) + replacement + textarea.value.substring(end);
        textarea.focus();
        textarea.setSelectionRange(start + replacement.length, start + replacement.length); // Cursor ans Ende der Formatierung setzen
    }

    // Initialisiere Formatierungs-Buttons für bereits vorhandene Textareas
    document.querySelectorAll('.transcript-textarea').forEach(textarea => {
        const toolbar = textarea.previousElementSibling; // Die Toolbar ist das vorherige Geschwisterelement
        if (toolbar && toolbar.classList.contains('transcript-editor-toolbar')) {
            toolbar.querySelectorAll('.format-button').forEach(button => {
                button.addEventListener('click', function() {
                    applyFormatting(textarea, button.dataset.tag);
                });
            });
        }
    });

    // Funktion zur Überprüfung der Vollständigkeit einer Zeile
    function updateRowCompleteness() {
        const row = this.closest('tr');
        const comicIdInput = row.querySelector('input[name="comic_id[]"]');
        const typeSelect = row.querySelector('select[name="comic_type[]"]');
        const nameInput = row.querySelector('input[name="comic_name[]"]');
        const transcriptTextarea = row.querySelector('textarea[name="comic_transcript[]"]');
        const chapterInput = row.querySelector('input[name="comic_chapter[]"]');

        const isComplete = comicIdInput.value.trim() !== '' &&
                           typeSelect.value.trim() !== '' &&
                           nameInput.value.trim() !== '' &&
                           transcriptTextarea.value.trim() !== '' &&
                           chapterInput.value.trim() !== '' &&
                           parseInt(chapterInput.value) > 0;

        if (isComplete) {
            row.classList.remove('incomplete-entry');
        } else {
            row.classList.add('incomplete-entry');
        }
    }

    // Initialisiere die Vollständigkeitsprüfung für alle vorhandenen Zeilen
    document.querySelectorAll('#comic-data-editor-table tbody tr').forEach(row => {
        // Da die Zeilen bereits beim Laden die Klasse haben können,
        // triggern wir die Prüfung, um sie ggf. zu entfernen, wenn alles ausgefüllt ist.
        updateRowCompleteness.call({target: row.querySelector('input, select, textarea')});

        // Füge Event Listener für Input-Änderungen hinzu
        row.querySelectorAll('input, select, textarea').forEach(input => {
            input.addEventListener('input', updateRowCompleteness);
        });
    });


    // Hilfsfunktion für HTML-Escaping in JavaScript
    function htmlspecialchars(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }
});
</script>

<?php
// Binde den gemeinsamen Footer ein.
if (file_exists($footerPath)) {
    include $footerPath;
} else {
    echo "</body></html>"; // HTML schließen, falls Footer fehlt.
}
?>
