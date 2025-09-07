<?php
/**
 * This script is called via AJAX to keep the PHP session alive.
 * It simply starts the session and updates the 'last_activity' timestamp.
 * It is now protected against CSRF attacks.
 */

// Strikte Session-Konfiguration, um sicherzugehen
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

// --- CSRF-Token-Überprüfung ---
$token = $_POST['csrf_token'] ?? null;

if ($token === null || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    header('Content-Type: application/json');
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token.']);
    exit;
}

// If the user is logged in, update their last activity time.
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    $_SESSION['last_activity'] = time();
    // Respond with success
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'Session extended.']);
} else {
    // Respond with an error if the user is not logged in
    header('Content-Type: application/json');
    http_response_code(401); // Unauthorized
    echo json_encode(['status' => 'error', 'message' => 'Not logged in.']);
}
?>