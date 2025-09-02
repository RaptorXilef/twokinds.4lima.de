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

// === DEBUG-MODUS STEUERUNG ===
// Setze auf true, um DEBUG-Meldungen zu aktivieren, auf false, um sie zu deaktivieren.
$debugMode = false;

if ($debugMode)
    error_log("DEBUG: generator_rss.php wird geladen.");

// Starte den Output Buffer als ALLERERSTE Zeile, um wirklich jede Ausgabe abzufangen.
ob_start();
if ($debugMode)
    error_log("DEBUG: Output Buffer in generator_rss.php gestartet.");

// Setzt das maximale Ausführungszeitlimit für das Skript.
set_time_limit(300);
if ($debugMode)
    error_log("DEBUG: Maximales Ausführungszeitlimit auf 300 Sekunden gesetzt.");

// Starte die PHP-Sitzung, falls noch keine aktiv ist.
if (session_status() === PHP_SESSION_NONE) {
    session_start();

    // NEU: Binde die zentrale Sicherheits- und Sitzungsüberprüfung ein.
    require_once __DIR__ . '/../src/components/security_check.php';

    if ($debugMode)
        error_log("DEBUG: Session gestartet in generator_rss.php.");
} else {
    if ($debugMode)
        error_log("DEBUG: Session bereits aktiv in generator_rss.php.");
}

// SICHERHEITSCHECK: Nur für angemeldete Administratoren zugänglich.
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    if ($debugMode)
        error_log("DEBUG: Nicht angemeldet, Weiterleitung zur Login-Seite von generator_rss.php.");
    // Wenn nicht angemeldet, zur Login-Seite weiterleiten.
    ob_end_clean(); // Output Buffer leeren, da wir umleiten
    header('Location: index.php');
    exit;
}
if ($debugMode)
    error_log("DEBUG: Admin in generator_rss.php angemeldet.");


// === Dynamische Basis-URL Bestimmung für die gesamte Anwendung ===
// Diese Logik ist notwendig, um korrekte absolute URLs im RSS-Feed zu generieren.
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
// Korrektur: appRootAbsPath muss das tatsächliche Anwendungs-Root-Verzeichnis sein.
// Wenn generator_rss.php in /app-root/admin/ liegt, dann ist dirname(__FILE__) /app-root/admin
// und dirname(dirname(__FILE__)) ist /app-root.
$appRootAbsPath = str_replace('\\', '/', dirname(dirname(__FILE__)));
$documentRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'], '/\\'));
$subfolderPath = str_replace($documentRoot, '', $appRootAbsPath);
if (!empty($subfolderPath) && $subfolderPath !== '/') {
    $subfolderPath = '/' . trim($subfolderPath, '/') . '/';
} elseif (empty($subfolderPath)) {
    $subfolderPath = '/';
}
$baseUrl = $protocol . $host . $subfolderPath;

if ($debugMode)
    error_log("DEBUG: Basis-URL bestimmt: " . $baseUrl);

// Pfade zu den JSON-Konfigurationsdateien
$comicVarJsonPath = __DIR__ . '/../src/config/comic_var.json';
$rssConfigJsonPath = __DIR__ . '/../src/config/rss_config.json';
$rssOutputPath = __DIR__ . '/../rss.xml'; // Speicherort der generierten rss.xml
if ($debugMode) {
    error_log("DEBUG: comic_var.json Pfad: " . $comicVarJsonPath);
    error_log("DEBUG: rss_config.json Pfad: " . $rssConfigJsonPath);
    error_log("DEBUG: rss.xml Output Pfad: " . $rssOutputPath);
}

// Funktion zum Laden einer JSON-Datei
function loadJsonFile($filePath, $debugMode) // $debugMode als Parameter übergeben
{
    if ($debugMode)
        error_log("DEBUG: loadJsonFile() aufgerufen für: " . basename($filePath));

    if (!file_exists($filePath)) {
        if ($debugMode)
            error_log("DEBUG: Datei nicht gefunden: " . $filePath);
        return ['status' => 'error', 'message' => "Datei nicht gefunden: " . basename($filePath), 'data' => null];
    }
    $content = file_get_contents($filePath);
    if ($content === false) {
        if ($debugMode)
            error_log("DEBUG: Fehler beim Lesen des Inhalts von: " . $filePath);
        return ['status' => 'error', 'message' => "Fehler beim Lesen des Inhalts von: " . basename($filePath), 'data' => null];
    }
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        if ($debugMode)
            error_log("DEBUG: Fehler beim Parsen von JSON in " . basename($filePath) . ": " . json_last_error_msg());
        return ['status' => 'error', 'message' => "Fehler beim Parsen von JSON in " . basename($filePath) . ": " . json_last_error_msg(), 'data' => null];
    }
    if ($debugMode)
        error_log("DEBUG: Datei erfolgreich geladen und geparst: " . basename($filePath));
    return ['status' => 'success', 'message' => "Datei erfolgreich geladen: " . basename($filePath), 'data' => $data];
}

// Überprüfe, ob der RSS-Generierungs-Request gesendet wurde (AJAX-Call)
if (isset($_POST['action']) && $_POST['action'] === 'generate_rss') {
    if ($debugMode)
        error_log("DEBUG: AJAX-Anfrage 'generate_rss' erkannt.");
    // WICHTIG: KEINE AUSGABE VOR DIESER ZEILE, wenn JSON gesendet wird!
    // Output Buffer leeren, um sicherzustellen, dass nur JSON gesendet wird
    ob_end_clean();
    header('Content-Type: application/json');

    try {
        // Lade die JSON-Dateien
        $comicDataResult = loadJsonFile($comicVarJsonPath, $debugMode);
        $rssConfigResult = loadJsonFile($rssConfigJsonPath, $debugMode);

        if ($comicDataResult['status'] !== 'success') {
            if ($debugMode)
                error_log("DEBUG: Fehler beim Laden von comic_var.json: " . $comicDataResult['message']);
            echo json_encode(['success' => false, 'message' => 'Fehler: comic_var.json konnte nicht geladen werden. ' . $comicDataResult['message']]);
            exit;
        }

        // Standardwerte für RSS-Konfiguration
        $rssConfig = [
            'max_items' => 10,
            'feed_title' => 'Twokinds in deutsch - Comic-Feed',
            'feed_description' => 'Der offizielle RSS-Feed für die neuesten deutschen Übersetzungen von Twokinds.',
            'feed_author_name' => 'Felix Maywald', // Feed-Autor
            'comic_author_name' => 'Thomas J. Fischbach', // Comic-Autor
            'comic_translator_name' => 'Felix Maywald', // Comic-Übersetzer
            'homepage_url' => $baseUrl, // URL der Homepage
            'contact_info' => $baseUrl . 'impressum.php' // Kontaktmöglichkeit (E-Mail, URL, Tel. etc.)
        ];

        // Korrektur: Nur array_merge aufrufen, wenn $rssConfigResult['data'] ein Array und nicht null ist
        if (
            $rssConfigResult['status'] === 'success' &&
            isset($rssConfigResult['data']) &&
            is_array($rssConfigResult['data']) &&
            $rssConfigResult['data'] !== null
        ) {
            $rssConfig = array_merge($rssConfig, $rssConfigResult['data']);
            if ($debugMode)
                error_log("DEBUG: rss_config.json erfolgreich geladen und Standardwerte überschrieben.");
        } else {
            if ($debugMode)
                error_log("DEBUG: rss_config.json konnte nicht geladen werden oder Daten sind kein Array oder null. Verwende Standardwerte. Meldung: " . $rssConfigResult['message']);
        }

        $comicData = $comicDataResult['data'];
        $maxItems = $rssConfig['max_items'];
        if ($debugMode)
            error_log("DEBUG: Max. RSS-Items: " . $maxItems);


        // Finde alle Comic-PHP-Dateien im comic-Verzeichnis
        // Pfad von admin/ zu comic/
        $comicFiles = glob(__DIR__ . '/../comic/*.php');
        if ($comicFiles === false) {
            if ($debugMode)
                error_log("DEBUG: Fehler beim Lesen des comic-Verzeichnisses.");
            echo json_encode(['success' => false, 'message' => 'Fehler beim Zugriff auf das Comic-Verzeichnis.']);
            exit;
        }
        if ($debugMode)
            error_log("DEBUG: " . count($comicFiles) . " Comic-Dateien gefunden.");

        // Sortiere die Dateien alphabetisch (entspricht chronologisch bei YYYYMMDD.php)
        rsort($comicFiles); // Absteigend sortieren, um die neuesten zuerst zu haben
        if ($debugMode)
            error_log("DEBUG: Comic-Dateien absteigend sortiert.");

        $rssItems = [];
        $processedCount = 0;

        foreach ($comicFiles as $filePath) {
            if ($processedCount >= $maxItems) {
                if ($debugMode)
                    error_log("DEBUG: Maximale Anzahl an RSS-Items (" . $maxItems . ") erreicht. Schleife beendet.");
                break; // Maximale Anzahl an Items erreicht
            }

            $filename = basename($filePath);
            // Extrahiere die ID (YYYYMMDD) aus dem Dateinamen
            if (preg_match('/^(\d{8})\.php$/', $filename, $matches)) {
                $comicId = $matches[1];
                if ($debugMode)
                    error_log("DEBUG: Verarbeite Comic-Datei: " . $filename . ", ID: " . $comicId);

                if (is_array($comicData) && isset($comicData[$comicId])) {
                    $comicInfo = $comicData[$comicId];

                    // Prüfe auf "Comicseite" Typ und nicht-leere "name" und "transcript"
                    if (
                        isset($comicInfo['type']) && $comicInfo['type'] === 'Comicseite' &&
                        !empty($comicInfo['name']) &&
                        isset($comicInfo['transcript']) && trim(strip_tags($comicInfo['transcript'])) !== ''
                    ) { // strip_tags entfernt HTML, trim entfernt Leerzeichen

                        // *** ÄNDERUNG START ***
                        // Zuerst prüfen, ob ein Bild existiert. Nur dann den Eintrag erstellen.
                        $imageExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.webp'];
                        $actualImageFileName = '';

                        foreach ($imageExtensions as $ext) {
                            $imageFileName = $comicId . $ext;
                            // NEUER PFAD: /assets/comic_lowres/ statt /assets/comic_thumbnails/
                            $imageFsPath = __DIR__ . '/../assets/comic_lowres/' . $imageFileName;
                            if (file_exists($imageFsPath)) {
                                $actualImageFileName = $imageFileName;
                                break; // Bild gefunden, Schleife beenden
                            }
                        }

                        // Nur wenn ein Bild gefunden wurde (!empty), fahren wir fort und erstellen den RSS-Eintrag.
                        if (!empty($actualImageFileName)) {
                            if ($debugMode) {
                                error_log("DEBUG: Comic-Bild gefunden für ID " . $comicId . ". Erstelle RSS-Eintrag.");
                            }

                            $comicLink = htmlspecialchars($baseUrl . 'comic/' . $filename);
                            $pubDate = date(DATE_RSS, strtotime($comicId)); // Datum im RSS-Format

                            // NEUER PFAD: /assets/comic_lowres/
                            $imageUrl = htmlspecialchars($baseUrl . 'assets/comic_lowres/' . $actualImageFileName);
                            $imageHtml = '<p><img src="' . $imageUrl . '" alt="' . htmlspecialchars($comicInfo['name']) . '" style="max-width: 100%; height: auto; display: block; margin-bottom: 10px;" /></p>';

                            $descriptionWithImage = $imageHtml . '<p>' . htmlspecialchars($comicInfo['transcript']) . '</p>';

                            $rssItems[] = [
                                'title' => htmlspecialchars($comicInfo['name']),
                                'link' => $comicLink,
                                'guid' => $comicLink,
                                'description' => $descriptionWithImage,
                                'pubDate' => $pubDate
                            ];
                            $processedCount++;
                            if ($debugMode)
                                error_log("DEBUG: Comic-Item hinzugefügt: " . $comicInfo['name'] . " (Verarbeitet: " . $processedCount . ")");

                        } else {
                            // Wenn kein Bild gefunden wurde, wird dieser Comic übersprungen.
                            if ($debugMode) {
                                error_log("DEBUG: Comic-ID " . $comicId . " übersprungen: Kein Bild im Ordner /assets/comic_lowres/ gefunden.");
                            }
                        }
                        // *** ÄNDERUNG ENDE ***

                    } else {
                        if ($debugMode)
                            error_log("DEBUG: Comic-ID " . $comicId . " übersprungen: Typ nicht 'Comicseite' oder Name/Transkript leer.");
                    }
                } else {
                    if ($debugMode)
                        error_log("DEBUG: Comic-ID " . $comicId . " nicht in comic_var.json gefunden. Überspringe.");
                }
            } else {
                if ($debugMode)
                    error_log("DEBUG: Dateiname " . $filename . " entspricht nicht dem YYYYMMDD.php-Format. Überspringe.");
            }
        }
        if ($debugMode)
            error_log("DEBUG: Insgesamt " . count($rssItems) . " RSS-Items zur Generierung vorbereitet.");


        // Erstelle den RSS-XML-Inhalt
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"></rss>');
        $channel = $xml->addChild('channel');
        $channel->addChild('title', $rssConfig['feed_title']);
        $channel->addChild('link', htmlspecialchars($rssConfig['homepage_url'])); // Link zur Hauptseite
        // ÄNDERUNG: Zusätzliche Informationen zur Channel-Beschreibung hinzufügen
        $fullDescription = $rssConfig['feed_description'];
        if (!empty($rssConfig['comic_author_name'])) {
            $fullDescription .= '<br/><br/>Original-Comic-Autor: ' . htmlspecialchars($rssConfig['comic_author_name']);
        }
        if (!empty($rssConfig['comic_translator_name'])) {
            $fullDescription .= '<br/>Übersetzer: ' . htmlspecialchars($rssConfig['comic_translator_name']);
        }
        if (!empty($rssConfig['contact_info'])) {
            $fullDescription .= '<br/>Kontakt: ' . htmlspecialchars($rssConfig['contact_info']);
        }
        $channel->addChild('description', $fullDescription);
        $channel->addChild('language', 'de-de'); // Sprache des Feeds
        $channel->addChild('lastBuildDate', date(DATE_RSS)); // Letztes Build-Datum
        $channel->addChild('generator', 'Custom RSS Generator by Felix');
        // Feed-Autor ohne Klammern und E-Mail
        $channel->addChild('managingEditor', htmlspecialchars($rssConfig['feed_author_name']));
        $channel->addChild('webMaster', htmlspecialchars($rssConfig['feed_author_name']));

        // Optional: Hinzufügen von Informationen über Comic-Autor, Übersetzer und Kontakt als benutzerdefinierte Elemente
        // Dies erfordert einen Namespace, um valide zu sein, oder man fügt es in die Description ein.
        // Für RSS 2.0 ohne Namespace-Erweiterung ist die Description der beste Ort.
        // Die Informationen sind aber in $rssConfig verfügbar.

        foreach ($rssItems as $item) {
            $rssItem = $channel->addChild('item');
            $rssItem->addChild('title', $item['title']);
            $rssItem->addChild('link', $item['link']);
            $rssItem->addChild('guid', $item['guid']);
            $rssItem->addChild('description', $item['description']);
            $rssItem->addChild('pubDate', $item['pubDate']);
            if ($debugMode)
                error_log("DEBUG: RSS-Item '" . $item['title'] . "' zum Channel hinzugefügt.");
        }

        // Prüfen, ob die Datei bereits existiert
        $fileExists = file_exists($rssOutputPath);
        if ($debugMode)
            error_log("DEBUG: RSS-Output-Datei existiert bereits: " . ($fileExists ? 'Ja' : 'Nein'));

        // Speichere die XML-Datei
        if (file_put_contents($rssOutputPath, $xml->asXML()) !== false) {
            $rssFileUrl = htmlspecialchars($baseUrl) . 'rss.xml';
            $message = $fileExists ? 'RSS-Feed erfolgreich aktualisiert.' : 'RSS-Feed erfolgreich generiert.';
            if ($debugMode)
                error_log("DEBUG: " . $message . " URL: " . $rssFileUrl);
            echo json_encode(['success' => true, 'message' => $message, 'rssUrl' => $rssFileUrl]);
        } else {
            if ($debugMode)
                error_log("DEBUG: Fehler beim Speichern der rss.xml Datei: " . $rssOutputPath);
            echo json_encode(['success' => false, 'message' => 'Fehler beim Speichern der rss.xml Datei. Bitte Dateiberechtigungen prüfen.']);
        }
    } catch (Exception $e) {
        // Fange unerwartete Fehler ab und gib sie als JSON zurück
        if ($debugMode)
            error_log("DEBUG: Unerwarteter Fehler aufgetreten: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Ein unerwarteter Fehler ist aufgetreten: ' . $e->getMessage()]);
    }
    exit; // Wichtig, um zu verhindern, dass der Rest der HTML-Seite gerendert wird
} else {
    // Dieser Block wird nur ausgeführt, wenn die Seite normal geladen wird (kein AJAX-Call)
    if ($debugMode)
        error_log("DEBUG: Normale Seitenladung (kein AJAX-Call).");

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
    $headerPath = __DIR__ . "/../src/layout/header.php";
    if (file_exists($headerPath)) {
        include $headerPath;
        if ($debugMode)
            error_log("DEBUG: Header in generator_rss.php eingebunden.");
    } else {
        die('Fehler: Header-Datei nicht gefunden. Pfad: ' . htmlspecialchars($headerPath));
    }

    // Lade die JSON-Dateien erneut für die Anzeige des Status auf der Seite
    $comicDataResult = loadJsonFile($comicVarJsonPath, $debugMode);
    $rssConfigResult = loadJsonFile($rssConfigJsonPath, $debugMode);
    if ($debugMode) {
        error_log("DEBUG: Status comic_var.json für Anzeige: " . $comicDataResult['status'] . " - " . $comicDataResult['message']);
        error_log("DEBUG: Status rss_config.json für Anzeige: " . $rssConfigResult['status'] . " - " . $rssConfigResult['message']);
    }
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
                <?php if ($debugMode)
                    error_log("DEBUG: Fehler in Konfigurationsdateien erkannt, Generierungsbutton deaktiviert."); ?>
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
    $footerPath = __DIR__ . "/../src/layout/footer.php";
    if (file_exists($footerPath)) {
        include $footerPath;
        if ($debugMode)
            error_log("DEBUG: Footer in generator_rss.php eingebunden.");
    } else {
        echo "</body></html>"; // HTML schließen, falls Footer fehlt.
        if ($debugMode)
            error_log("DEBUG: Footer-Datei nicht gefunden, HTML manuell geschlossen.");
    }
} // Ende des else-blocks für die normale Seitenanzeige

ob_end_flush(); // Gebe den Output Buffer am Ende des Skripts aus.
if ($debugMode)
    error_log("DEBUG: Output Buffer in generator_rss.php geleert und ausgegeben.");
?>