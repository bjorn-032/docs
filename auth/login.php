<?php
require __DIR__ . '/../config.php';
require __DIR__ . '/session.php';
_startSession();

// If already logged in, go straight to the library
if (!empty($_SESSION['user_sub'])) {
    header('Location: /');
    exit;
}

$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

$params = http_build_query([
    'client_id'     => OIDC_CLIENT_ID,
    'redirect_uri'  => OIDC_REDIRECT_URI,
    'response_type' => 'code',
    'scope'         => 'openid profile email',
    'state'         => $state,
]);

header('Location: ' . OIDC_AUTH_ENDPOINT . '?' . $params);
exit;
