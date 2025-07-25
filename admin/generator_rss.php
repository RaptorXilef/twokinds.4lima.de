<?php
/**
 * Adminseite zum Generieren des RSS-Feeds für die Comic-Webseite.
 *
 * Diese Seite ermöglicht es, den RSS-Feed basierend auf den neuesten Comic-Seiten
 * und Konfigurationen aus JSON-Dateien zu erstellen.
 *
 * @package Admin
 * @subpackage RSS
 */

// Setzt das maximale Ausführungszeitlimit für das Skript.
set_time_limit(300);

// Starte die PHP-Sitzung, falls noch keine aktiv ist.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// === Dynamische Basis-URL Bestimmung für die gesamte Anwendung ===
// Diese Logik ist notwendig, um korrekte absolute URLs im RSS-Feed zu generieren.
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$scriptName = $_SERVER['SCRIPT_NAME'];
// Ermittle das Basisverzeichnis des Skripts relativ zum Document Root
$scriptDir = rtrim(dirname($scriptName), '/');

// Wenn das Skript im Root-Verzeichnis liegt, ist $scriptDir leer.
// In diesem Fall ist $baseUrl einfach das Protokoll und der Host.
// Andernfalls ist es Protokoll + Host + Skriptverzeichnis.
// Da diese Datei im 'admin' Verzeichnis liegt, muss der baseUrl einen Schritt zurückgehen.
$baseUrl = $protocol . $host . rtrim(dirname($scriptDir), '/') . '/';

// Pfade zu den JSON-Konfigurationsdateien
$comicVarJsonPath = __DIR__ . '/../src/config/comic_var.json';
$rssConfigJsonPath = __DIR__ . '/../src/config/rss_config.json';
$rssOutputPath = __DIR__ . '/../rss.xml'; // Speicherort der generierten rss.xml

// Funktion zum Laden einer JSON-Datei
function loadJsonFile($filePath)
{
    if (!file_exists($filePath)) {
        return ['status' => 'error', 'message' => "Datei nicht gefunden: " . basename($filePath), 'data' => null];
    }
    $content = file_get_contents($filePath);
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['status' => 'error', 'message' => "Fehler beim Parsen von JSON in " . basename($filePath) . ": " . json_last_error_msg(), 'data' => null];
    }
    return ['status' => 'success', 'message' => "Datei erfolgreich geladen: " . basename($filePath), 'data' => $data];
}

// Überprüfe, ob der RSS-Generierungs-Request gesendet wurde (AJAX-Call)
if (isset($_POST['action']) && $_POST['action'] === 'generate_rss') {
    // WICHTIG: KEINE AUSGABE VOR DIESER ZEILE, wenn JSON gesendet wird!
    header('Content-Type: application/json');

    try {
        // Lade die JSON-Dateien
        $comicDataResult = loadJsonFile($comicVarJsonPath);
        $rssConfigResult = loadJsonFile($rssConfigJsonPath);

        if ($comicDataResult['status'] !== 'success') {
            echo json_encode(['success' => false, 'message' => 'Fehler: comic_var.json konnte nicht geladen werden. ' . $comicDataResult['message']]);
            exit;
        }

        // Standardwerte für RSS-Konfiguration, falls die Datei nicht geladen werden kann
        $rssConfig = [
            'max_items' => 10,
            'feed_title' => 'Twokinds Deutsch Comic-Feed',
            'feed_description' => 'Der offizielle RSS-Feed für die neuesten deutschen Übersetzungen von Twokinds.',
            'feed_author_name' => 'Felix Mustermann',
            'feed_author_email' => 'felix.mustermann@example.com'
        ];

        if ($rssConfigResult['status'] === 'success') {
            $rssConfig = array_merge($rssConfig, $rssConfigResult['data']);
        }

        $comicData = $comicDataResult['data'];
        $maxItems = $rssConfig['max_items'];

        // Finde alle Comic-PHP-Dateien im comic-Verzeichnis
        // Pfad von admin/ zu comic/
        $comicFiles = glob(__DIR__ . '/../comic/*.php');

        // Sortiere die Dateien alphabetisch (entspricht chronologisch bei YYYYMMDD.php)
        rsort($comicFiles); // Absteigend sortieren, um die neuesten zuerst zu haben

        $rssItems = [];
        $processedCount = 0;

        foreach ($comicFiles as $filePath) {
            if ($processedCount >= $maxItems) {
                break; // Maximale Anzahl an Items erreicht
            }

            $filename = basename($filePath);
            // Extrahiere die ID (YYYYMMDD) aus dem Dateinamen
            if (preg_match('/^(\d{8})\.php$/', $filename, $matches)) {
                $comicId = $matches[1];

                if (isset($comicData[$comicId])) {
                    $comicInfo = $comicData[$comicId];

                    // Prüfe auf "Comicseite" Typ und nicht-leere "name" und "transcript"
                    if (
                        isset($comicInfo['type']) && $comicInfo['type'] === 'Comicseite' &&
                        !empty($comicInfo['name']) &&
                        isset($comicInfo['transcript']) && trim(strip_tags($comicInfo['transcript'])) !== ''
                    ) { // strip_tags entfernt HTML, trim entfernt Leerzeichen

                        $comicLink = htmlspecialchars($baseUrl . 'comic/' . $filename);
                        $pubDate = date(DATE_RSS, strtotime($comicId)); // Datum im RSS-Format

                        $rssItems[] = [
                            'title' => htmlspecialchars($comicInfo['name']),
                            'link' => $comicLink,
                            'guid' => $comicLink, // GUID sollte eindeutig sein, Link ist hier ausreichend
                            'description' => htmlspecialchars($comicInfo['transcript']),
                            'pubDate' => $pubDate
                        ];
                        $processedCount++;
                    }
                }
            }
        }

        // Erstelle den RSS-XML-Inhalt
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"></rss>');
        $channel = $xml->addChild('channel');
        $channel->addChild('title', $rssConfig['feed_title']);
        $channel->addChild('link', htmlspecialchars($baseUrl)); // Link zur Hauptseite
        $channel->addChild('description', $rssConfig['feed_description']);
        $channel->addChild('language', 'de-de'); // Sprache des Feeds
        $channel->addChild('lastBuildDate', date(DATE_RSS)); // Letztes Build-Datum
        $channel->addChild('generator', 'Custom RSS Generator by Felix');
        $channel->addChild('managingEditor', htmlspecialchars($rssConfig['feed_author_email']) . ' (' . htmlspecialchars($rssConfig['feed_author_name']) . ')');
        $channel->addChild('webMaster', htmlspecialchars($rssConfig['feed_author_email']) . ' (' . htmlspecialchars($rssConfig['feed_author_name']) . ')');

        foreach ($rssItems as $item) {
            $rssItem = $channel->addChild('item');
            $rssItem->addChild('title', $item['title']);
            $rssItem->addChild('link', $item['link']);
            $rssItem->addChild('guid', $item['guid']);
            $rssItem->addChild('description', $item['description']);
            $rssItem->addChild('pubDate', $item['pubDate']);
        }

        // Prüfen, ob die Datei bereits existiert
        $fileExists = file_exists($rssOutputPath);

        // Speichere die XML-Datei
        if (file_put_contents($rssOutputPath, $xml->asXML()) !== false) {
            $message = $fileExists ? 'RSS-Feed erfolgreich aktualisiert und unter ' : 'RSS-Feed erfolgreich generiert und unter ';
            $message .= htmlspecialchars($baseUrl) . 'rss.xml gespeichert.';
            echo json_encode(['success' => true, 'message' => $message]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Fehler beim Speichern der rss.xml Datei. Bitte Dateiberechtigungen prüfen.']);
        }
    } catch (Exception $e) {
        // Fange unerwartete Fehler ab und gib sie als JSON zurück
        echo json_encode(['success' => false, 'message' => 'Ein unerwarteter Fehler ist aufgetreten: ' . $e->getMessage()]);
    }
    exit; // Wichtig, um zu verhindern, dass der Rest der HTML-Seite gerendert wird
} else {
    // Dieser Block wird nur ausgeführt, wenn die Seite normal geladen wird (kein AJAX-Call)

    // Setze Parameter für den Header.
    $pageTitle = 'RSS Generator';
    $pageHeader = 'RSS Feed Generator';
    $bodyClass = ''; // Keine spezielle Klasse für den Body
    // HIER WIRD DIE JAVASCRIPT-DATEI EINGEBUNDEN:
    $additionalScripts = "<script type='text/javascript' src='" . htmlspecialchars($baseUrl) . "admin/js/generator_rss.js?c=" . date('Ymd') . "'></script>";
    $additionalHeadContent = '';
    $viewportContent = 'width=1099'; // Konsistent mit Original für das Design.
    $siteDescription = 'Verwaltung des RSS-Feeds für die Comic-Webseite.';

    // Binde den gemeinsamen Header ein.
    // Pfad von admin/ zu src/layout/
    include __DIR__ . "/../src/layout/header.php";

    // Lade die JSON-Dateien erneut für die Anzeige des Status auf der Seite
    $comicDataResult = loadJsonFile($comicVarJsonPath);
    $rssConfigResult = loadJsonFile($rssConfigJsonPath);
    ?>

    <section>
        <h2 class="page-header">RSS-Feed Generierung</h2>

        <p>Hier kannst du den RSS-Feed für deine Comic-Webseite generieren.</p>

        <div class="status-messages mt-4 p-4 rounded-lg bg-gray-100 border border-gray-200">
            <h3 class="font-semibold text-lg mb-2">Status der Konfigurationsdateien:</h3>
            <ul class="list-disc ml-5">
                <li class="<?php echo ($comicDataResult['status'] === 'success') ? 'text-green-700' : 'text-red-700'; ?>">
                    <!--`comic_var.json`: --><?php echo htmlspecialchars($comicDataResult['message']); ?>
                </li>
                <li class="<?php echo ($rssConfigResult['status'] === 'success') ? 'text-green-700' : 'text-red-700'; ?>">
                    <!--`rss_config.json`: --><?php echo htmlspecialchars($rssConfigResult['message']); ?>
                </li>
            </ul>
            <?php if ($comicDataResult['status'] !== 'success' || $rssConfigResult['status'] !== 'success'): ?>
                <p class="text-red-700 mt-2 font-bold">Bitte beheben Sie die oben genannten Fehler, bevor Sie den RSS-Feed
                    generieren.</p>
            <?php endif; ?>
        </div>

        <div class="generate-button mt-6 text-center">
            <button id="generateRss"
                class="button px-6 py-3 bg-blue-600 text-white rounded-md shadow-md hover:bg-blue-700 transition-colors duration-200
            <?php echo ($comicDataResult['status'] !== 'success' || $rssConfigResult['status'] !== 'success') ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                <?php echo ($comicDataResult['status'] !== 'success' || $rssConfigResult['status'] !== 'success') ? 'disabled' : ''; ?>>
                RSS-Feed generieren
            </button>
            <p id="generationMessage" class="mt-4 text-center font-semibold"></p>
        </div>

    </section>

    <?php
    // Binde den gemeinsamen Footer ein.
    // Pfad von admin/ zu src/layout/
    include __DIR__ . "/../src/layout/footer.php";
} // Ende des else-Blocks für die normale Seitenanzeige
?>