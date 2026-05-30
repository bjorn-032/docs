<?php
// Shared session helper for the typst editor.
// Call requireAuth() from pages, requireAuthApi() from JSON API endpoints.

function _startSession() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_set_cookie_params(['samesite' => 'Lax', 'secure' => true, 'httponly' => true]);
        session_start();
    }
}

function requireAuth(): array {
    _startSession();
    if (empty($_SESSION['user_sub'])) {
        header('Location: /auth/login.php');
        exit;
    }
    return ['sub' => $_SESSION['user_sub'], 'name' => $_SESSION['user_name']];
}

function requireAuthApi(): array {
    _startSession();
    if (empty($_SESSION['user_sub'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'unauthenticated']);
        exit;
    }
    return ['sub' => $_SESSION['user_sub'], 'name' => $_SESSION['user_name']];
}
