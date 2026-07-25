<?php
/**
 * Auth Guard for Hotel Manager Panel
 * Validates role='hotel_manager' from users table
 * Returns JSON errors for AJAX/POST requests instead of redirecting
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Helper: detect if the current request is an AJAX/API call
 * that expects a JSON response instead of a redirect.
 */
function _authGuardIsAjax() {
    // Explicit AJAX header
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        return true;
    }
    // POST with Content-Type form-urlencoded or JSON (fetch API)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ct = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
        if (stripos($ct, 'application/x-www-form-urlencoded') !== false ||
            stripos($ct, 'application/json') !== false) {
            return true;
        }
        // POST with an action parameter = AJAX action handler
        if (isset($_POST['action'])) {
            return true;
        }
    }
    // Accept header prefers JSON
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    if (stripos($accept, 'application/json') !== false) {
        return true;
    }
    return false;
}

/**
 * Helper: deny access — return JSON for AJAX, redirect for page loads
 */
function _authGuardDeny($message = 'Authentication required. Please log in.') {
    if (_authGuardIsAjax()) {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => $message]);
        exit();
    }
    // Normal page load — redirect to login
    header('Location: login.php?denied=1');
    exit();
}

if (!isset($_SESSION['hm_id'])) {
    _authGuardDeny('Session expired. Please log in again.');
}

// Verify role is still hotel_manager on protected pages
if (isset($conn)) {
    $uid = (int)$_SESSION['hm_id'];
    $role_res = mysqli_query($conn, "SELECT role FROM users WHERE id = $uid LIMIT 1");
    if ($role_res && mysqli_num_rows($role_res) > 0) {
        $role = mysqli_fetch_assoc($role_res)['role'];
        if ($role !== 'hotel_manager') {
            session_unset();
            session_destroy();
            _authGuardDeny('Access denied. Your role has changed.');
        }
    } else {
        session_unset();
        session_destroy();
        _authGuardDeny('User account not found.');
    }
}
