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

// Looks up a share token from POST or GET, validates it against the
// document ID in the request, and returns the document owner's user row.
// Returns null if no token present; exits with 403 if token is invalid or
// the wrong access level.
function _getShareTokenUser(bool $requireEdit): ?array {
    $token = preg_replace('/[^a-f0-9]/', '', $_POST['share_token'] ?? $_GET['share_token'] ?? '');
    if ($token === '') return null;

    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($db->connect_error) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'DB error']);
        exit;
    }

    $stmt = $db->prepare(
        "SELECT td.owner, ds.document_id, ds.access
         FROM document_shares ds
         JOIN typst_documents td ON td.id = ds.document_id
         WHERE ds.token = ?"
    );
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $db->close();

    if (!$row) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Invalid share link']);
        exit;
    }

    // Validate the requested document matches what the token grants access to
    $reqDocId = (int)($_POST['document_id'] ?? $_POST['id'] ?? $_GET['document_id'] ?? $_GET['id'] ?? 0);
    if ($reqDocId !== 0 && $reqDocId !== (int)$row['document_id']) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Token not valid for this document']);
        exit;
    }

    if ($requireEdit && $row['access'] !== 'edit') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'This link is view-only']);
        exit;
    }

    return ['sub' => $row['owner'], 'name' => 'Guest'];
}

// $requireEdit: pass true in write API endpoints so view-only tokens are rejected.
function requireAuthApi(bool $requireEdit = false): array {
    _startSession();
    if (!empty($_SESSION['user_sub'])) {
        return ['sub' => $_SESSION['user_sub'], 'name' => $_SESSION['user_name']];
    }
    $tokenUser = _getShareTokenUser($requireEdit);
    if ($tokenUser !== null) return $tokenUser;

    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'unauthenticated']);
    exit;
}
