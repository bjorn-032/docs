<?php
require __DIR__ . '/../config.php';
require __DIR__ . '/session.php';
_startSession();

// ── State verification ────────────────────────────────────────────────────────
if (empty($_GET['state']) || $_GET['state'] !== ($_SESSION['oauth_state'] ?? '')) {
    http_response_code(400);
    die('OAuth state mismatch. <a href="/auth/login.php">Try again</a>.');
}
unset($_SESSION['oauth_state']);

if (!empty($_GET['error'])) {
    http_response_code(400);
    die('OIDC error: ' . htmlspecialchars($_GET['error_description'] ?? $_GET['error'])
        . ' <a href="/auth/login.php">Try again</a>.');
}

if (empty($_GET['code'])) {
    http_response_code(400);
    die('Missing authorization code.');
}

// ── Exchange code for tokens ──────────────────────────────────────────────────
$ch = curl_init(OIDC_TOKEN_ENDPOINT);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS     => http_build_query([
        'grant_type'    => 'authorization_code',
        'code'          => $_GET['code'],
        'redirect_uri'  => OIDC_REDIRECT_URI,
        'client_id'     => OIDC_CLIENT_ID,
        'client_secret' => OIDC_CLIENT_SECRET,
    ]),
    CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_TIMEOUT        => 10,
]);
$tokenRaw = curl_exec($ch);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    http_response_code(502);
    die('Token endpoint unreachable: ' . htmlspecialchars($curlErr));
}

$token = json_decode($tokenRaw, true);
if (empty($token['access_token'])) {
    http_response_code(502);
    die('Token exchange failed: ' . htmlspecialchars($tokenRaw));
}

// ── Fetch user info ───────────────────────────────────────────────────────────
$ch = curl_init(OIDC_USERINFO_ENDPOINT);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token['access_token']],
    CURLOPT_TIMEOUT        => 10,
]);
$userRaw = curl_exec($ch);
$curlErr = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    http_response_code(502);
    die('Userinfo endpoint unreachable: ' . htmlspecialchars($curlErr));
}

$userinfo = json_decode($userRaw, true);
if (empty($userinfo['sub'])) {
    http_response_code(502);
    die('Userinfo response missing sub: ' . htmlspecialchars($userRaw));
}

// ── Persist session ───────────────────────────────────────────────────────────
session_regenerate_id(true);
$_SESSION['user_sub']  = $userinfo['sub'];
$_SESSION['user_name'] = $userinfo['name']
    ?? $userinfo['preferred_username']
    ?? $userinfo['email']
    ?? $userinfo['sub'];

header('Location: /');
exit;
