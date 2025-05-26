<?php
$directory = ''; // Hier das Stammverzeichnis angeben, in dem die Dateien liegen

$generators = array(
    'exist_folder' => 'Existieren die Haupordner?',
    'comicpages_generator' => 'Seitengenerator starten',
    'sitemap_generator' => 'Sitemap',
    'comicnames_list_generator' => 'Comicnamenliste',
    'thumbnail_generator_show-text' => 'Thumbnails - Text',
    'thumbnail_generator_show-images' => 'Thumbnails - Bild',
    'archive_generator_start' => 'Archiv',
    'emailliste-auslesen' => 'Emails kopieren',
    'neuesPasswortGenerrieren' => 'Neues Passwort generieren'
);

function getLatestVersion($directory, $generatorName) {
    $latestVersion = '';
    $files = glob($directory . $generatorName . '_v.*.php');
    
    if (!empty($files)) {
        $versions = array();
        foreach ($files as $file) {
            $filename = basename($file);
            $version = preg_replace('/^' . $generatorName . '_v\.(\d+\.\d+\.\d+)\.php$/', '$1', $filename);
            $versions[] = $version;
        }
        
        if (!empty($versions)) {
            rsort($versions, SORT_NATURAL);
            $latestVersion = $generatorName . '_v.' . $versions[0] . '.php';
        }
    }
    
    return $latestVersion;
}

function generateLinks($directory, $generators) {
    foreach ($generators as $generatorKey => $generatorName) {
        $latestVersion = getLatestVersion($directory, $generatorKey);
        if (!empty($latestVersion)) {
            echo '<a href="' . $directory . $latestVersion . '">' . $generatorName . '</a>';
        } else {
            echo 'Keine Version gefunden für ' . $generatorName . '.<br>';
        }
    }
}

// Beispiel für die Verwendung
echo '</br>';
echo '</br>';
echo '</br>';
echo 'Startseite öffnen';
echo '<a href="index.php"><span><!--class="red-text"--><strong>Adminbereich</strong></span></a>';
echo '</br>';
echo '</br>';
echo 'Erststart:';
generateLinks($directory, array('exist_folder' => $generators['exist_folder'], 'comicnames_list_generator' => $generators['comicnames_list_generator']));
echo '</br>';
echo '</br>';
echo 'Seitengenerator starten';
generateLinks($directory, array('comicpages_generator' => $generators['comicpages_generator']));
echo '<br>';
generateLinks($directory, array('thumbnail_generator_show-images' => $generators['thumbnail_generator_show-images'], 'thumbnail_generator_show-text' => $generators['thumbnail_generator_show-text']));
echo '</br>';
generateLinks($directory, array('archive_generator_start' => $generators['archive_generator_start'], 'sitemap_generator' => $generators['sitemap_generator']));
echo '</br>';
echo 'Emails kopieren';
generateLinks($directory, array('emailliste-auslesen' => $generators['emailliste-auslesen']));
echo '</br>';
echo '</br>';
echo 'Neues Passwort generrieren';
generateLinks($directory, array('neuesPasswortGenerrieren' => $generators['neuesPasswortGenerrieren']));
echo '</br>';
echo '</br>';
echo 'Logout';
echo '<a href="logout.php">Logout</a>';





?>