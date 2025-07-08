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

// Lade die Comic-Daten
require_once __DIR__ . '/../src/components/load_comic_data.php';

// Pfade definieren
$archiveFilePath = __DIR__ . '/../archive.php'; // Zielpfad für die generierte Archivdatei
$comicThumbnailsDirForArchive = 'assets/comic_thumbnails/'; // Relativ zur generierten archive.php
$comicPhpFileDirForArchive = 'comic/'; // Relativ zur generierten archive.php
$archiveChaptersJsonPath = __DIR__ . '/../src/components/archive_chapters.json'; // Neuer Pfad für Kapitel-Metadaten

$message = '';
$messageType = ''; // 'success' oder 'error'

/**
 * Lädt Kapitel-Metadaten aus einer JSON-Datei.
 * @param string $path Der Pfad zur JSON-Datei.
 * @return array Die dekodierten Daten als assoziatives Array (chapterId => data) oder ein leeres Array im Fehlerfall.
 */
function loadArchiveChapters(string $path): array {
    if (!file_exists($path)) {
        return [];
    }
    $content = file_get_contents($path);
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Fehler beim Dekodieren von archive_chapters.json: " . json_last_error_msg());
        return [];
    }
    // Konvertiere das Array von Objekten in ein assoziatives Array für einfachen Zugriff
    $indexedData = [];
    if (is_array($data)) {
        foreach ($data as $chapter) {
            if (isset($chapter['chapterId'])) {
                $indexedData[$chapter['chapterId']] = $chapter;
            }
        }
    }
    return $indexedData;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_archive'])) {
    try {
        // Lade die comic_var.json Daten
        if (empty($comicData)) {
            throw new Exception("Keine Comic-Daten gefunden. Bitte überprüfen Sie comic_var.json.");
        }

        // Lade die benutzerdefinierten Kapitel-Metadaten
        $customChapterData = loadArchiveChapters($archiveChaptersJsonPath);

        // Comics nach Kapitel gruppieren und innerhalb jedes Kapitels nach Datum sortieren
        $chapters = [];
        foreach ($comicData as $comicId => $data) {
            $chapter = $data['chapter'];
            if (!isset($chapters[$chapter])) {
                $chapters[$chapter] = [];
            }
            $chapters[$chapter][$comicId] = $data;
        }

        // Kapitelnummern sortieren
        ksort($chapters); // Sortiert Kapitel nach ihren IDs (numerisch)

        $archiveContentHtml = '';

        foreach ($chapters as $chapterId => $comicsInChapter) {
            // Comics innerhalb des Kapitels nach Datum sortieren (älteste zuerst)
            uksort($comicsInChapter, function($a, $b) {
                return strtotime($a) - strtotime($b);
            });

            // Standardwerte für Titel und Beschreibung
            $chapterTitle = "Kapitel {$chapterId}";
            $chapterDescription = '<p>Keine Beschreibung für dieses Kapitel vorhanden.</p>';

            // Überschreibe mit benutzerdefinierten Daten, falls vorhanden
            if (isset($customChapterData[$chapterId])) {
                $chapterTitle = htmlspecialchars($customChapterData[$chapterId]['title']);
                $chapterDescription = '<p>' . htmlspecialchars($customChapterData[$chapterId]['description']) . '</p>';
            } else {
                // Wenn keine benutzerdefinierten Daten, füge den Datumsbereich hinzu
                $firstComicId = key($comicsInChapter);
                end($comicsInChapter);
                $lastComicId = key($comicsInChapter);

                $firstComicDate = strtotime($firstComicId);
                $lastComicDate = strtotime($lastComicId);

                $dateRange = '';
                if ($firstComicId !== $lastComicId) {
                    $dateRange = ' (' . date('d.m.Y', $firstComicDate) . ' - ' . date('d.m.Y', $lastComicDate) . ')';
                } else {
                    $dateRange = ' (' . date('d.m.Y', $firstComicDate) . ')';
                }
                $chapterTitle .= $dateRange;
            }


            $imageLinksHtml = '';
            foreach ($comicsInChapter as $comicId => $data) {
                $thumbnailPath = $comicThumbnailsDirForArchive . $comicId . '.jpg'; // Annahme: JPG-Thumbnails
                $comicPagePath = $comicPhpFileDirForArchive . $comicId . '.php'; // Pfad zur einzelnen Comic-Seite
                $comicNameEscaped = htmlspecialchars($data['name']);

                // Lazy loading: src ist ein 1x1 GIF, data-src enthält den echten Pfad
                $imageLinksHtml .= '
                <a href="' . htmlspecialchars($comicPagePath) . '">
                    <img src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==" data-src="' . htmlspecialchars($thumbnailPath) . '" title="' . $comicNameEscaped . '" alt="' . $comicNameEscaped . '">
                </a>';
            }

            $archiveContentHtml .= '
            <section class="chapter" data-ch-id="' . $chapterId . '">
                <h2>' . $chapterTitle . '<span class="arrow-left jsdep"></span></h2>
                ' . $chapterDescription . '
                <aside class="chapter-links">
                    ' . $imageLinksHtml . '
                </aside>
            </section>';
        }

        // --- Generiere die komplette archive.php Datei ---
        ob_start(); // Starte einen neuen Output Buffer für die archive.php

        // Setze Parameter für den Header der generierten archive.php
        $pageTitle = 'Archiv';
        $pageHeader = 'TwoKinds Archiv';
        $siteDescription = 'Das Archiv der TwoKinds Comics, fanübersetzt auf Deutsch.';
        $robotsContent = 'index, follow'; // Archiv soll von Suchmaschinen indexiert werden

        // jQuery und archive.js müssen für die generierte Seite geladen werden
        $additionalScripts = '
            <script src="https://code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
            <script type="text/javascript" src="https://cdn.twokinds.keenspot.com/js/archive.js?c=20201116"></script>';

        // Setze die Basis-URL dynamisch für die generierte archive.php (gleiche Logik wie in index.php)
        $isLocal = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
        if ($isLocal) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $host = $_SERVER['HTTP_HOST'];
            $pathParts = explode('/', $_SERVER['SCRIPT_NAME']);
            // Von /admin/generate_archive.php zu /
            array_pop($pathParts); // Entfernt 'generate_archive.php'
            array_pop($pathParts); // Entfernt 'admin'
            $basePath = implode('/', $pathParts);
            $baseUrl = $protocol . $host . $basePath . '/';
        } else {
            $baseUrl = 'https://twokinds.4lima.de/';
        }
        $additionalHeadContent = '<link rel="canonical" href="' . $baseUrl . 'archive.php">';
        $viewportContent = 'width=1099'; // Konsistent mit Comic-Seiten

        // Header der generierten archive.php
        include __DIR__ . '/../src/layout/header.php'; // Pfad relativ zur generator-Datei

        echo '<div class="instructions jsdep">Klicken Sie auf eine Kapitelüberschrift, um das Kapitel zu erweitern.</div>';

        echo $archiveContentHtml; // Der dynamisch generierte Archivinhalt

        // Footer der generierten archive.php
        include __DIR__ . '/../src/layout/footer.php'; // Pfad relativ zur generator-Datei

        $finalArchiveContent = ob_get_clean(); // Hole den gesamten Inhalt

        // Speichere den generierten Inhalt in die Datei archive.php
        file_put_contents($archiveFilePath, $finalArchiveContent);

        $message = 'Archivdatei erfolgreich generiert unter: ' . $archiveFilePath;
        $messageType = 'success';

    } catch (Exception $e) {
        $message = 'Fehler beim Generieren der Archivdatei: ' . $e->getMessage();
        $messageType = 'error';
        error_log($message);
    }
}

// Setze Parameter für den Header der Admin-Seite selbst
$pageTitle = 'Archiv Generator';
$pageHeader = 'Archiv Generator';
$siteDescription = 'Seite zum Generieren der Comic-Archivdatei.';
$robotsContent = 'noindex, nofollow'; // Admin-Seite soll nicht indexiert werden
$additionalScripts = ''; // Keine zusätzlichen Skripte für die Generator-Seite selbst
$additionalHeadContent = '
    <style>
        .message-box {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
        }
        .message-box.success {
            background-color: #d4edda; /* Light green */
            color: #155724; /* Dark green */
            border: 1px solid #c3e6cb;
        }
        .message-box.error {
            background-color: #f8d7da; /* Light red */
            color: #721c24; /* Dark red */
            border: 1px solid #f5c6cb;
        }
    </style>';
$viewportContent = 'width=device-width, initial-scale=1.0'; // Standard Viewport für Admin-Bereich

// Binde den gemeinsamen Header ein. Pfad relativ zu admin/
include __DIR__ . '/../src/layout/header.php';
?>

<main id="content" class="content">
    <div class="message-box <?php echo $messageType; ?>" style="<?php echo empty($message) ? 'display: none;' : 'display: block;'; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>

    <h1>Archiv Generator</h1>
    <p>Klicken Sie auf den Button, um die `archive.php`-Datei auf Grundlage der `comic_var.json` und der Bilddateien neu zu generieren.</p>
    <form method="POST">
        <button type="submit" name="generate_archive">Archiv erstellen/aktualisieren</button>
    </form>
</main>

<?php
// Binde den gemeinsamen Footer ein. Pfad relativ zu admin/
include __DIR__ . '/../src/layout/footer.php';
// Flush den Output Buffer
ob_end_flush();
?>
