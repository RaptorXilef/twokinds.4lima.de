<?php
/**
 * This script is called via AJAX to keep the PHP session alive.
 * It simply starts the session and updates the 'last_activity' timestamp.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
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