<?php
/**
 * Administrationsseite zum Erstellen des Bild-Caches inkl. Cache-Busting.
 * Dieses Tool scannt die Bildverzeichnisse, ermittelt den letzten Änderungszeitpunkt
 * jeder Datei und speichert die relativen Pfade mit einem Cache-Busting-Parameter
 * (?c=timestamp) in der zentralen 'comic_image_cache.json'.
 * Es priorisiert dabei die Dateiendungen (webp, png, etc.), um die performanteste Version zu wählen.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = false;

// === ZENTRALE ADMIN-INITIALISIERUNG (enthält Nonce und CSRF-Setup) ===
require_once __DIR__ . '/src/components/admin_init.php';

// Pfade
$headerPath = __DIR__ . '/../src/layout/header.php';
$footerPath = __DIR__ . '/../src/layout/footer.php';
$cachePath = __DIR__ . '/../src/config/comic_image_cache.json';
$dirsToScan = [
    'thumbnails' => [
        'path' => __DIR__ . '/../assets/comic_thumbnails/',
        'relativePath' => 'assets/comic_thumbnails/'
    ],
    'lowres' => [
        'path' => __DIR__ . '/../assets/comic_lowres/',
        'relativePath' => 'assets/comic_lowres/'
    ],
    'hires' => [
        'path' => __DIR__ . '/../assets/comic_hires/',
        'relativePath' => 'assets/comic_hires/'
    ],
    'socialmedia' => [
        'path' => __DIR__ . '/../assets/comic_socialmedia/',
        'relativePath' => 'assets/comic_socialmedia/'
    ]
];

/**
 * Scannt ein Verzeichnis nach Bilddateien und hängt einen Cache-Busting-Parameter an.
 * Sucht für jeden eindeutigen Dateinamen die beste verfügbare Erweiterung in priorisierter Reihenfolge.
 *
 * @param string $dir Der absolute Pfad zum zu scannenden Verzeichnis.
 * @param string $relativePathPrefix Der relative Pfad-Präfix für die Ausgabe.
 * @return array Ein assoziatives Array [comicId => relativerPfad?c=timestamp].
 */
function scanDirectoryForImagesWithCacheBusting(string $dir, string $relativePathPrefix): array
{
    $images = [];
    if (!is_dir($dir)) {
        return [];
    }

    // Priorisierte Liste der Erweiterungen
    $prioritizedExtensions = ['webp', 'png', 'gif', 'jpg', 'jpeg'];
    $allowedExtensions = array_flip($prioritizedExtensions);

    // Zuerst alle eindeutigen Basis-Dateinamen sammeln
    $baseNames = [];
    $files = scandir($dir);
    foreach ($files as $file) {
        $fileInfo = pathinfo($file);
        if (isset($fileInfo['filename'], $fileInfo['extension']) && !empty($fileInfo['filename']) && isset($allowedExtensions[strtolower($fileInfo['extension'])])) {
            $baseNames[$fileInfo['filename']] = true; // Verwende Keys für Eindeutigkeit
        }
    }
    $uniqueBaseNames = array_keys($baseNames);

    // Für jeden Basisnamen die beste verfügbare Erweiterung in priorisierter Reihenfolge suchen
    foreach ($uniqueBaseNames as $baseName) {
        foreach ($prioritizedExtensions as $ext) {
            $fileName = $baseName . '.' . $ext;
            $absolutePath = $dir . $fileName;

            if (is_file($absolutePath)) {
                // Die erste gefundene Datei (gemäß Priorität) wird verwendet
                $lastModified = @filemtime($absolutePath);
                if ($lastModified === false) {
                    $lastModified = time(); // Fallback
                }
                $pathWithBuster = $relativePathPrefix . $fileName . '?c=' . $lastModified;
                $images[$baseName] = $pathWithBuster;

                break; // Suche für diesen Basisnamen abbrechen und zum nächsten springen
            }
        }
    }
    return $images;
}

// --- AJAX-Anfrage-Handler ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'build_cache') {
    // SICHERHEIT: CSRF-Token validieren
    verify_csrf_token();

    ob_end_clean();
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => '', 'counts' => []];

    // Verarbeitet einzelne, komma-getrennte oder 'all' Typen
    $typesToProcess = [];
    $typeInput = $_POST['type'] ?? '';

    if ($typeInput === 'all') {
        $typesToProcess = array_keys($dirsToScan);
    } else {
        $submittedTypes = explode(',', $typeInput);
        foreach ($submittedTypes as $submittedType) {
            if (array_key_exists($submittedType, $dirsToScan)) {
                $typesToProcess[] = $submittedType;
            }
        }
    }

    if (empty($typesToProcess)) {
        $response['message'] = 'Ungültiger oder kein Typ für Cache-Erstellung angegeben.';
        echo json_encode($response);
        exit;
    }

    $existingCache = file_exists($cachePath) ? json_decode(file_get_contents($cachePath), true) : [];
    if (!is_array($existingCache)) {
        $existingCache = [];
    }

    foreach ($typesToProcess as $currentType) {
        $dirInfo = $dirsToScan[$currentType];
        $foundImages = scanDirectoryForImagesWithCacheBusting($dirInfo['path'], $dirInfo['relativePath']);
        $response['counts'][$currentType] = count($foundImages);

        // Sicherstellen, dass die Keys existieren, bevor darauf zugegriffen wird
        foreach ($foundImages as $comicId => $path) {
            if (!isset($existingCache[$comicId])) {
                $existingCache[$comicId] = [];
            }
            $existingCache[$comicId][$currentType] = $path;
        }
    }

    if (file_put_contents($cachePath, json_encode($existingCache, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
        $response['success'] = true;
        $response['message'] = 'Cache erfolgreich aktualisiert für: ' . implode(', ', $typesToProcess);
    } else {
        $response['message'] = 'Fehler: Die Cache-Datei konnte nicht geschrieben werden.';
        error_log("Fehler beim Schreiben der Cache-Datei: " . $cachePath);
    }

    echo json_encode($response);
    exit;
}
// --- Ende AJAX-Anfrage-Handler ---

ob_end_flush();

// Header-Parameter
$pageTitle = 'Adminbereich - Bild-Cache & Busting Generator';
$pageHeader = 'Bild-Cache & Busting Generator';
$siteDescription = 'Tool zum Erstellen des Bild-Caches mit Cache-Busting-Parametern.';
$robotsContent = 'noindex, nofollow';

include $headerPath;
?>

<article>
    <div class="admin-form-container">
        <header>
            <h1><?php echo htmlspecialchars($pageHeader); ?></h1>
        </header>

        <div class="content-section">
            <h2>Cache inkl. Cache-Busting aktualisieren</h2>
            <p>Dieses Tool scannt die Bildverzeichnisse, hängt den Zeitstempel der letzten Dateiänderung als
                Cache-Busting-Parameter an (<code>?c=...</code>) und speichert das Ergebnis in
                <code>comic_image_cache.json</code>. Dies stellt sicher, dass Browser immer die neuste Version eines
                Bildes laden, nachdem es geändert wurde.
            </p>

            <div id="fixed-buttons-container">
                <button type="button" class="cache-build-button" data-type="thumbnails">Thumbnails</button>
                <button type="button" class="cache-build-button" data-type="lowres">Low-Res</button>
                <button type="button" class="cache-build-button" data-type="hires">High-Res</button>
                <button type="button" class="cache-build-button" data-type="socialmedia">Social Media</button>
                <button type="button" id="build-all-button" class="cache-build-button" data-type="all"><strong>Alle
                        aktualisieren</strong></button>
            </div>

            <div id="loading-spinner" style="display: none; text-align: center; margin-top: 20px;">
                <div class="spinner"></div>
                <p id="progress-text">Aktualisiere Cache...</p>
            </div>

            <div id="generation-results-section" style="margin-top: 20px; display: none;">
                <h2>Ergebnis</h2>
                <p id="overall-status-message" class="status-message"></p>
            </div>
        </div>
    </div>
</article>

<style nonce="<?php echo htmlspecialchars($nonce); ?>">
    #fixed-buttons-container {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 20px;
        margin-bottom: 20px;
    }

    .cache-build-button {
        padding: 8px 15px;
        border: 1px solid #007bff;
        background-color: transparent;
        color: #007bff;
        border-radius: 5px;
        cursor: pointer;
        font-size: 15px;
        transition: all 0.3s ease;
    }

    .cache-build-button:hover,
    .cache-build-button:focus {
        background-color: #007bff;
        color: white;
    }

    .cache-build-button:disabled {
        background-color: #e9ecef;
        color: #6c757d;
        border-color: #ced4da;
        cursor: not-allowed;
    }

    #build-all-button {
        background-color: #007bff;
        color: white;
    }

    #build-all-button:hover {
        background-color: #0056b3;
        border-color: #0056b3;
    }

    body.theme-night .cache-build-button {
        color: #09f;
        border-color: #09f;
    }

    body.theme-night .cache-build-button:hover,
    body.theme-night .cache-build-button:focus {
        background-color: #09f;
        color: #fff;
    }

    body.theme-night #build-all-button {
        background-color: #09f;
        border-color: #09f;
        color: #fff;
    }

    .spinner {
        border: 4px solid rgba(0, 0, 0, 0.1);
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border-left-color: #09f;
        animation: spin 1s ease infinite;
        margin: 0 auto 10px auto;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    .status-message {
        padding: 12px;
        border-radius: 5px;
        margin-top: 15px;
    }

    .status-green {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .status-red {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
</style>

<script nonce="<?php echo htmlspecialchars($nonce); ?>">
    document.addEventListener('DOMContentLoaded', function () {
        const csrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>';
        const buttons = document.querySelectorAll('.cache-build-button');
        const spinner = document.getElementById('loading-spinner');
        const progressText = document.getElementById('progress-text');
        const resultsSection = document.getElementById('generation-results-section');
        const statusMessage = document.getElementById('overall-status-message');

        async function runCacheBuild(type) {
            let typeName = type;
            if (type === 'all') {
                typeName = 'Alle';
            } else if (type === 'lowres,hires') {
                typeName = 'Low-Res & High-Res';
            } else {
                const button = document.querySelector(`.cache-build-button[data-type="${type}"]`);
                if (button) {
                    typeName = button.textContent;
                }
            }

            resultsSection.style.display = 'none';
            statusMessage.innerHTML = '';
            spinner.style.display = 'block';
            progressText.textContent = `Aktualisiere Cache für '${typeName}'... Bitte warten.`;
            buttons.forEach(b => b.disabled = true);

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'build_cache',
                        type: type,
                        csrf_token: csrfToken // CSRF-Token hinzugefügt
                    })
                });

                if (!response.ok) throw new Error(`HTTP-Fehler: Status ${response.status}`);

                const data = await response.json();

                resultsSection.style.display = 'block';
                statusMessage.className = data.success ? 'status-message status-green' : 'status-message status-red';

                let message = data.message;
                if (data.success && data.counts) {
                    const countDetails = Object.entries(data.counts)
                        .map(([key, value]) => `<li><strong>${key}:</strong> ${value} Bilder gefunden</li>`)
                        .join('');
                    message += `<br><br><strong>Details:</strong><ul>${countDetails}</ul>`;
                }
                statusMessage.innerHTML = message;

            } catch (error) {
                resultsSection.style.display = 'block';
                statusMessage.className = 'status-message status-red';
                statusMessage.innerHTML = `Ein unerwarteter Fehler ist aufgetreten: ${error.message}.`;
                console.error('Fehler bei der Cache-Erstellung:', error);
            } finally {
                spinner.style.display = 'none';
                buttons.forEach(b => b.disabled = false);
            }
        }

        buttons.forEach(button => {
            button.addEventListener('click', function () {
                const type = this.dataset.type;
                runCacheBuild(type); // Aufruf der neuen Funktion
            });
        });

        // Autostart-Logik
        const urlParams = new URLSearchParams(window.location.search);
        const autostartType = urlParams.get('autostart');
        if (autostartType) {
            // Gültige Typen prüfen, um sicherzugehen
            const allowedTypes = ['thumbnails', 'lowres,hires', 'socialmedia', 'lowres', 'hires', 'all'];
            const types = autostartType.split(',');
            const isValid = types.every(t => allowedTypes.includes(t));

            if (isValid) {
                runCacheBuild(autostartType);
            }
        }
    });
</script>

<?php include $footerPath; ?>