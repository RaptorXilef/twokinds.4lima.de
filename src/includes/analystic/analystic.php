<?php
// src/includes/analystic/analystic.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Funktion, um die IP-Adresse des Benutzers zu erhalten
function get_client_ip() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

$analysticUserIP = get_client_ip();
$request_uri = $_SERVER['REQUEST_URI'];
$access_time = date('Y-m-d H:i:s');
$session_id = session_id();

// Log-Datei definieren (außerhalb des Webroots für Sicherheit)
// Annahme: Es gibt ein Verzeichnis /logs/ außerhalb des Webroots oder ein anderes sicheres Verzeichnis
// Für dieses Beispiel lege ich es in src/data/logs ab. Du solltest dies überprüfen und anpassen!
$log_file_path = DATA_DIR . '/logs/access.log'; // Pfad angepasst

// Sicherstellen, dass das Log-Verzeichnis existiert
if (!is_dir(dirname($log_file_path))) {
    mkdir(dirname($log_file_path), 0755, true);
}

// Daten in die Log-Datei schreiben
$log_entry = "IP: $analysticUserIP | URI: $request_uri | Time: $access_time | SessionID: $session_id\n";
file_put_contents($log_file_path, $log_entry, FILE_APPEND);

// Setze hier die Variable, die im Footer verwendet wird
$analysticUserIP = get_client_ip();
?>