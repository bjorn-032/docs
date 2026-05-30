<?php
// PocketID OIDC configuration — fill in client_id and client_secret after
// registering this app at https://accounts.fireants.dev
define('OIDC_CLIENT_ID',     '***REMOVED***');
define('OIDC_CLIENT_SECRET', '***REMOVED***');
define('OIDC_REDIRECT_URI',  'https://docs.fireants.dev/auth/callback.php');

define('OIDC_ISSUER',        'https://accounts.fireants.dev');
define('OIDC_AUTH_ENDPOINT', 'https://accounts.fireants.dev/authorize');
define('OIDC_TOKEN_ENDPOINT','https://accounts.fireants.dev/api/oidc/token');
define('OIDC_USERINFO_ENDPOINT', 'https://accounts.fireants.dev/api/oidc/userinfo');
define('OIDC_LOGOUT_ENDPOINT',  'https://accounts.fireants.dev/api/oidc/end-session');
