<?php
echo "Skript startet jetzt!\n"; // DIESE ZEILE WURDE HINZUGEFÜGT, UM DEN START ZU PRÜFEN.
flush(); // Sofortige Ausgabe erzwingen

/**
 * PHP-Skript zum Extrahieren von Comic-Informationen von twokinds.keenspot.com
 * und Speichern in einer JSON-Datei.
 *
 * Autor: Felix Maywald
 * Datum: 2025-07-27
 */

// --- Konfiguration ---
const BASE_URL = 'https://twokinds.keenspot.com/comic/';
const DEFAULT_RETRY_AFTER_SECONDS = 60; // Standardwartezeit bei 429-Fehler, wenn kein Retry-After-Header vorhanden ist
const DELAY_BETWEEN_REQUESTS = 1; // Wartezeit in Sekunden zwischen erfolgreichen Anfragen

// --- ANPASSBARE VARIABLEN FÜR START- UND ENDNUMMER ---
// Ändere diese Werte, um den Bereich der zu scrapenden Comics festzulegen.
const START_COMIC_NUMBER = 1;   // Die erste Comic-Nummer, die gescraped werden soll
const END_COMIC_NUMBER = 1264;    // Die letzte Comic-Nummer, die gescraped werden soll

// --- DEBUG-MODUS ---
// Setze auf 'true', um detaillierte Debug-Ausgaben in der Konsole zu sehen.
// Setze auf 'false', für eine weniger ausführliche Ausgabe.
const DEBUG_MODE = false;

// --- Initialisierung für Browser-Ausgabe (optional, primär für Konsole gedacht) ---
// Deaktiviere Output-Buffering und aktiviere implizites Flushing für sofortige Ausgabe im Browser
// Dies hilft, die Fortschrittsanzeige sichtbar zu machen, kann aber je nach Serverkonfiguration variieren.
@ob_implicit_flush();
@ob_end_clean(); // Leert und beendet alle aktiven Output-Buffer

echo "Willkommen zum Comic-Scraper für twokinds.keenspot.com!\n";
echo "Überprüfe erforderliche PHP-Erweiterungen...\n";
flush(); // Ausgabe sofort an den Browser senden

// Prüfe, ob die cURL-Erweiterung aktiviert ist
if (!extension_loaded('curl')) {
    echo "\nFEHLER: Die 'curl'-Erweiterung ist nicht aktiviert.\n";
    echo "Bitte aktiviere sie in deiner php.ini-Datei, indem du die Zeile 'extension=curl' (oder 'extension=php_curl.dll' unter Windows) einkommentierst (Semikolon am Anfang entfernen).\n";
    echo "Starte danach deinen Webserver neu.\n";
    exit(1); // Skript beenden mit Fehlercode
}

// Prüfe, ob die DOM-Erweiterung aktiviert ist
if (!extension_loaded('dom')) {
    echo "\nFEHLER: Die 'dom'-Erweiterung ist nicht aktiviert.\n";
    echo "Bitte aktiviere sie in deiner php.ini-Datei, indem du die Zeile 'extension=dom' (oder 'extension=php_dom.dll' unter Windows) einkommentierst (Semikolon am Anfang entfernen).\n";
    echo "Starte danach deinen Webserver neu.\n";
    exit(1); // Skript beenden mit Fehlercode
}

echo "Erforderliche Erweiterungen sind aktiv. Fahre fort...\n\n";
flush();

// --- Funktionen ---

/**
 * Konvertiert ein englisches Datumsformat (z.B. "July 25, 2025") in das Format YYYYMMDD.
 *
 * @param string $dateString Das Datum als String.
 * @return string Das Datum im Format YYYYMMDD oder ein leerer String, wenn die Konvertierung fehlschlägt.
 */
function convertDateToYYYYMMDD(string $dateString): string
{
    if (DEBUG_MODE) {
        echo "  [DEBUG] Versuche Datum zu konvertieren: '{$dateString}'\n";
        flush();
    }
    // Versuche, das Datum zu parsen
    $dateTime = DateTime::createFromFormat('F j, Y', $dateString);
    if ($dateTime) {
        $formattedDate = $dateTime->format('Ymd');
        if (DEBUG_MODE) {
            echo "  [DEBUG] Datum konvertiert zu: '{$formattedDate}'\n";
            flush();
        }
        return $formattedDate;
    }
    if (DEBUG_MODE) {
        echo "  [DEBUG] Datumskonvertierung fehlgeschlagen für: '{$dateString}'\n";
        flush();
    }
    return ''; // Gib leeren String zurück, wenn die Konvertierung fehlschlägt
}

/**
 * Holt den HTML-Inhalt einer URL mittels cURL.
 * Behandelt HTTP-Fehler und gibt den Inhalt sowie den HTTP-Statuscode zurück.
 *
 * @param string $url Die URL, die abgerufen werden soll.
 * @param array $headers Optional: Array für die Rückgabe der Antwort-Header.
 * @return array Ein assoziatives Array mit 'content' (HTML-Inhalt) und 'http_code' (HTTP-Statuscode).
 */
function fetchUrlContent(string $url, &$headers = []): array
{
    if (DEBUG_MODE) {
        echo "  [DEBUG] Rufe URL ab: {$url}\n";
        flush();
    }
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Rückgabe des Inhalts als String
    curl_setopt($ch, CURLOPT_HEADER, true); // Header in den Output einschließen
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Weiterleitung folgen
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Timeout nach 30 Sekunden
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36'); // User-Agent setzen, um nicht als Bot erkannt zu werden

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $content = substr($response, $headerSize);

    if (curl_errno($ch)) {
        $errorMsg = "cURL-Fehler beim Abrufen von {$url}: " . curl_error($ch);
        error_log($errorMsg);
        if (DEBUG_MODE) {
            echo "  [DEBUG] {$errorMsg}\n";
            flush();
        }
        return ['content' => '', 'http_code' => 0]; // 0 als Indikator für cURL-Fehler
    }

    curl_close($ch);
    if (DEBUG_MODE) {
        echo "  [DEBUG] HTTP-Statuscode: {$httpCode}\n";
        flush();
    }
    return ['content' => $content, 'http_code' => $httpCode];
}

/**
 * Extrahiert den Wert eines bestimmten Headers aus einem String von Headern.
 *
 * @param string $headersString Der String mit allen Headern.
 * @param string $headerName Der Name des zu suchenden Headers (z.B. 'Retry-After').
 * @return string|null Der Wert des Headers oder null, wenn nicht gefunden.
 */
function getHeaderValue(string $headersString, string $headerName): ?string
{
    $lines = explode("\n", $headersString);
    foreach ($lines as $line) {
        if (stripos($line, $headerName . ':') === 0) {
            $value = trim(substr($line, strlen($headerName) + 1));
            if (DEBUG_MODE) {
                echo "  [DEBUG] Header '{$headerName}' gefunden mit Wert: '{$value}'\n";
                flush();
            }
            return $value;
        }
    }
    if (DEBUG_MODE) {
        echo "  [DEBUG] Header '{$headerName}' nicht gefunden.\n";
        flush();
    }
    return null;
}

// --- Hauptlogik ---

// Bestimme den absoluten Pfad des Skripts und des Ausgabeordners
$scriptPath = __DIR__; // __DIR__ gibt den Pfad des aktuellen Skripts zurück
$outputFullPath = $scriptPath . DIRECTORY_SEPARATOR . 'scrape_comics' . DIRECTORY_SEPARATOR;

echo "Starte Datensammlung...\n";
echo "Start: " . START_COMIC_NUMBER . "   Ende: " . END_COMIC_NUMBER . "\n";
echo "Ausgabeordner (absoluter Pfad): " . $outputFullPath . "\n"; // Zeigt den absoluten Pfad an
flush();

// Dateinamen für JSON und Fehlerprotokoll generieren
$currentTime = date('Y-m-d_H-i-s');
$outputFileName = $outputFullPath . "comic_var_{$currentTime}.json"; // Verwende absoluten Pfad
$errorLogFileName = $outputFullPath . "comic_var_ERROR_{$currentTime}.log"; // Verwende absoluten Pfad

// Erstelle den Ausgabeordner, falls er nicht existiert
if (!is_dir($outputFullPath)) {
    if (DEBUG_MODE) {
        echo "  [DEBUG] Erstelle Ausgabeordner: " . $outputFullPath . "\n";
        flush();
    }
    // Versuche, den Ordner mit vollen Berechtigungen zu erstellen
    if (!mkdir($outputFullPath, 0777, true)) {
        echo "\nFEHLER: Der Ausgabeordner '" . $outputFullPath . "' konnte nicht erstellt werden. Bitte Berechtigungen prüfen.\n";
        exit(1);
    } else {
        if (DEBUG_MODE) {
            echo "  [DEBUG] Ausgabeordner erfolgreich erstellt.\n";
            flush();
        }
    }
}

$comicData = [];
$errorLog = [];

for ($i = START_COMIC_NUMBER; $i <= END_COMIC_NUMBER; $i++) {
    // Fortschrittsanzeige
    echo "\rAktuell in Verarbeitung: {$i} von " . END_COMIC_NUMBER . " (" . round(($i - START_COMIC_NUMBER + 1) / (END_COMIC_NUMBER - START_COMIC_NUMBER + 1) * 100, 2) . "%)";
    flush(); // Ausgabe sofort an den Browser senden

    $url = BASE_URL . $i . '/';
    $headers = [];
    $response = fetchUrlContent($url, $headers);
    $htmlContent = $response['content'];
    $httpCode = $response['http_code'];

    // Fehlerbehandlung
    if ($httpCode === 0) { // cURL-Fehler
        $errorLog[] = "Fehler beim Abrufen von Comic {$i}: cURL-Fehler.";
        if (DEBUG_MODE) {
            echo "\n  [DEBUG] Fehler: cURL-Fehler für Comic {$i}.\n";
            flush();
        }
        sleep(DELAY_BETWEEN_REQUESTS); // Trotz Fehler eine kleine Pause, um den Server nicht zu überlasten
        continue;
    } elseif ($httpCode === 404) {
        $errorLog[] = "Comic {$i} nicht gefunden (404 Fehler).";
        if (DEBUG_MODE) {
            echo "\n  [DEBUG] Fehler: 404 für Comic {$i}.\n";
            flush();
        }
        sleep(DELAY_BETWEEN_REQUESTS);
        continue;
    } elseif ($httpCode === 429) {
        $retryAfter = getHeaderValue($headers, 'Retry-After');
        $waitTime = (int) $retryAfter > 0 ? (int) $retryAfter : DEFAULT_RETRY_AFTER_SECONDS;
        $errorLog[] = "Zu viele Anfragen für Comic {$i} (429 Fehler). Warte {$waitTime} Sekunden.";
        echo "\nZu viele Anfragen (429). Warte {$waitTime} Sekunden...\n";
        flush();
        sleep($waitTime);
        // Nach dem Warten versuchen wir die aktuelle Seite erneut
        $i--; // Zähler zurücksetzen, um die aktuelle Seite erneut zu versuchen
        continue;
    } elseif ($httpCode >= 400) { // Andere Client- oder Serverfehler
        $errorLog[] = "Fehler beim Abrufen von Comic {$i}: HTTP-Statuscode {$httpCode}.";
        if (DEBUG_MODE) {
            echo "\n  [DEBUG] Fehler: HTTP-Statuscode {$httpCode} für Comic {$i}.\n";
            flush();
        }
        sleep(DELAY_BETWEEN_REQUESTS);
        continue;
    }

    // HTML-Parsing
    $dom = new DOMDocument();
    // @ unterdrückt Warnungen bei fehlerhaftem HTML
    @$dom->loadHTML($htmlContent);
    $xpath = new DOMXPath($dom);

    $type = '';
    $date = ''; // Initialisiere Datum als leeren String
    $name = '';
    $transcript = '';

    // Extrahiere Type, Datum und Name aus dem H1-Tag
    $h1Node = $xpath->query('//article[@class="comic"]/header/h1')->item(0);
    if ($h1Node) {
        $h1Text = $h1Node->textContent;
        if (DEBUG_MODE) {
            echo "\n  [DEBUG] H1-Text gefunden: '{$h1Text}'\n";
            flush();
        }

        // Type ermitteln
        if (strpos($h1Text, 'Comic for') === 0) {
            $type = 'Comicseite';
            $prefixLength = strlen('Comic for ');
        } elseif (strpos($h1Text, 'Filler for') === 0) {
            $type = 'Lückenfüller';
            $prefixLength = strlen('Filler for ');
        } else {
            $type = ''; // Unbekannter Typ
            $prefixLength = 0;
        }
        if (DEBUG_MODE) {
            echo "  [DEBUG] Typ ermittelt: '{$type}'\n";
            flush();
        }

        // Den Teil nach dem "Comic for " oder "Filler for " extrahieren
        $contentAfterPrefix = substr($h1Text, $prefixLength);

        // Prüfen, ob ein Doppelpunkt im Rest des Strings vorhanden ist
        $colonPos = strpos($contentAfterPrefix, ':');

        if ($colonPos !== false) {
            // Doppelpunkt gefunden: Datum ist vor dem Doppelpunkt, Name danach
            $rawDate = trim(substr($contentAfterPrefix, 0, $colonPos));
            $name = trim(substr($contentAfterPrefix, $colonPos + 1));
        } else {
            // Kein Doppelpunkt gefunden: Der gesamte Rest ist das Datum, Name ist leer
            $rawDate = trim($contentAfterPrefix);
            $name = '';
        }

        $date = convertDateToYYYYMMDD($rawDate);

        if (DEBUG_MODE) {
            echo "  [DEBUG] Rohdatum aus H1 (extrahiert): '{$rawDate}'\n";
            echo "  [DEBUG] Datum nach Konvertierung: '{$date}'\n";
            echo "  [DEBUG] Name ermittelt: '{$name}'\n";
            flush();
        }

    } else {
        if (DEBUG_MODE) {
            echo "\n  [DEBUG] H1-Tag nicht gefunden für Comic {$i}.\n";
            flush();
        }
    }

    // Extrahiere Transkript
    $transcriptDiv = $xpath->query('//aside[@class="transcript"]/div[@class="transcript-content"]')->item(0);
    if ($transcriptDiv) {
        // Hole den inneren HTML-Inhalt des transcript-content Divs
        $innerHtml = '';
        foreach ($transcriptDiv->childNodes as $node) {
            $innerHtml .= $dom->saveHTML($node);
        }
        $transcript = trim($innerHtml);
        if (DEBUG_MODE) {
            echo "  [DEBUG] Transkript gefunden (Teilansicht): " . substr($transcript, 0, 100) . "...\n";
            flush();
        }
    } else {
        if (DEBUG_MODE) {
            echo "  [DEBUG] Transkript-Div nicht gefunden für Comic {$i}.\n";
            flush();
        }
    }

    // Daten für die JSON-Datei vorbereiten
    $comicEntry = [
        'type' => $type,
        'name' => $name,
        'transcript' => $transcript,
        'chapter' => '', // Bleibt leer
        'datum' => $date // Das extrahierte Datum im YYYYMMDD-Format als separates Feld
    ];

    // --- HIER IST DIE WICHTIGE ÄNDERUNG FÜR DEN SCHLÜSSEL ---
    // Verwende das extrahierte Datum als Schlüssel.
    // Wenn das Datum leer ist (was nach der neuen Logik seltener der Fall sein sollte),
    // verwende die Comic-Nummer als Fallback.
    $key = !empty($date) ? $date : (string) $i;
    $comicData[$key] = $comicEntry;

    if (DEBUG_MODE) {
        echo "  [DEBUG] Daten für Comic {$i} gesammelt:\n";
        echo "    Type: '{$type}'\n";
        echo "    Datum (Key): '{$key}'\n"; // Zeigt den verwendeten Schlüssel an
        echo "    Name: '{$name}'\n";
        echo "    Transkript Länge: " . strlen($transcript) . " Zeichen\n";
        echo "    Datum (Feld): '{$comicEntry['datum']}'\n"; // Zeigt den Wert des neuen 'datum'-Feldes
        flush();
    }

    // Wartezeit nach erfolgreicher Verarbeitung
    sleep(DELAY_BETWEEN_REQUESTS);
}

echo "\n\nDatensammlung abgeschlossen.\n";
flush();

// Speichere die gesammelten Daten in der JSON-Datei
$jsonContent = json_encode($comicData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($jsonContent === false) {
    $errorMsg = "Fehler beim Kodieren der JSON-Daten: " . json_last_error_msg();
    error_log($errorMsg);
    echo "FEHLER: Beim Kodieren der JSON-Daten ist ein Problem aufgetreten. Details im PHP-Fehlerprotokoll.\n";
    if (DEBUG_MODE) {
        echo "  [DEBUG] {$errorMsg}\n";
        flush();
    }
} else {
    echo "Versuche, Daten in '{$outputFileName}' zu speichern...\n";
    flush();
    if (file_put_contents($outputFileName, $jsonContent) === false) {
        $errorMsg = "Fehler beim Schreiben der JSON-Datei: {$outputFileName}";
        error_log($errorMsg);
        echo "FEHLER: Daten konnten NICHT in '{$outputFileName}' gespeichert werden. Bitte Berechtigungen und Pfad prüfen.\n";
        if (DEBUG_MODE) {
            echo "  [DEBUG] {$errorMsg}\n";
            flush();
        }
    } else {
        echo "Daten erfolgreich in '{$outputFileName}' gespeichert.\n";
        // Zusätzliche Prüfung, ob die Datei wirklich existiert
        if (file_exists($outputFileName)) {
            echo "Bestätigung: Datei '{$outputFileName}' existiert nach dem Speichern.\n";
            if (DEBUG_MODE) {
                echo "  [DEBUG] Dateigröße von '{$outputFileName}': " . filesize($outputFileName) . " Bytes.\n";
                flush();
            }
        } else {
            echo "WARNUNG: Datei '{$outputFileName}' existiert NICHT, obwohl file_put_contents erfolgreich gemeldet wurde.\n";
        }
    }
}
flush();

// Speichere das Fehlerprotokoll
if (!empty($errorLog)) {
    echo "Versuche, Fehlerprotokoll in '{$errorLogFileName}' zu speichern...\n";
    flush();
    $errorLogContent = implode("\n", $errorLog);
    if (file_put_contents($errorLogFileName, $errorLogContent) === false) {
        $errorMsg = "Fehler beim Schreiben des Fehlerprotokolls: {$errorLogFileName}";
        error_log($errorMsg);
        echo "FEHLER: Fehlerprotokoll konnte NICHT in '{$errorLogFileName}' gespeichert werden. Bitte Berechtigungen und Pfad prüfen.\n";
        if (DEBUG_MODE) {
            echo "  [DEBUG] {$errorMsg}\n";
            flush();
        }
    } else {
        echo "Fehlerprotokoll in '{$errorLogFileName}' gespeichert.\n";
        if (file_exists($errorLogFileName)) {
            echo "Bestätigung: Datei '{$errorLogFileName}' existiert nach dem Speichern.\n";
        } else {
            echo "WARNUNG: Datei '{$errorLogFileName}' existiert NICHT, obwohl file_put_contents erfolgreich gemeldet wurde.\n";
        }
    }
} else {
    echo "Keine Fehler aufgetreten. Es wurde kein Fehlerprotokoll erstellt.\n";
}
flush();

echo "Skript beendet.\n";
flush();

?>