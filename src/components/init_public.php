<?php

/**
 * Zentrales Initialisierungsskript für alle öffentlichen Seiten.
 *
 * Dieses Skript übernimmt grundlegende Aufgaben und implementiert wichtige,
 * universelle Sicherheitsmaßnahmen, die auf jeder Seite der Webseite gelten sollen.
 * - Starten und sicheres Konfigurieren der PHP-Session.
 * - Setzen von strikten HTTP-Sicherheits-Headern.
 * - Generierung einer einmaligen Nonce für die Content-Security-Policy (CSP) zum Schutz vor XSS.
 *
 * @file      ROOT/src/components/public_init.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   2.0.0
 * @since     2.0.0 Umstellung auf die dynamische Path-Helfer-Klasse.
 */

// === DEBUG-MODUS STEUERUNG ===
// Kann in der aufrufenden Datei VOR dem Include gesetzt werden.
$debugMode = $debugMode ?? false;

// Lädt die zentrale Konfiguration, Konstanten und die Path-Klasse.
require_once __DIR__ . '/load_config.php';

// --- 1. Strikte Session-Konfiguration (für alle Seiten) ---
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict'
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- 2. Universelle Sicherheits-Header & CSP mit Nonce ---
$nonce = bin2hex(random_bytes(16));

// Content-Security-Policy (CSP)
$csp = [
    'default-src' => ["'self'"],
    'script-src' => ["'self'", "'nonce-{$nonce}'", "https://code.jquery.com", "https://cdnjs.cloudflare.com", "https://cdn.jsdelivr.net", "https://www.googletagmanager.com", "https://placehold.co", "https://cdn.twokinds.keenspot.com"],
    'style-src' => ["'self'", "'unsafe-inline'", "https://cdnjs.cloudflare.com", "https://cdn.jsdelivr.net", "https://cdn.twokinds.keenspot.com", "https://fonts.googleapis.com"],
    'font-src' => ["'self'", "https://cdnjs.cloudflare.com", "https://fonts.gstatic.com", "https://cdn.twokinds.keenspot.com", "https://cdn.jsdelivr.net", "https://twokinds.4lima.de"],
    'img-src' => ["'self'", "data:", "https://placehold.co", "https://cdn.twokinds.keenspot.com", "https://twokindscomic.com", "https://www.2kinds.com", "https://i.creativecommons.org", "https://licensebuttons.net"],
    'connect-src' => ["'self'", "https://cdn.twokinds.keenspot.com", "https://region1.google-analytics.com", "https://twokindscomic.com", "https://cdn.jsdelivr.net"],
    'object-src' => ["'none'"],
    'frame-ancestors' => ["'self'"],
    'base-uri' => ["'self'"],
    'form-action' => ["'self'"],
];
$cspHeader = '';
foreach ($csp as $directive => $sources) {
    $cspHeader .= $directive . ' ' . implode(' ', $sources) . '; ';
}
header("Content-Security-Policy: " . trim($cspHeader));

// Weitere Sicherheits-Header
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()");
