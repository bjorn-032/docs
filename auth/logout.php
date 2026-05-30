<?php
require __DIR__ . '/../config.php';
require __DIR__ . '/session.php';
_startSession();

session_destroy();

// Redirect to PocketID's end-session endpoint so the SSO session is also cleared
$params = http_build_query([
    'post_logout_redirect_uri' => 'https://fireants.dev/auth/login.php',
    'client_id'                => OIDC_CLIENT_ID,
]);
header('Location: ' . OIDC_LOGOUT_ENDPOINT . '?' . $params);
exit;
