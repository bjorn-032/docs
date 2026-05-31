<?php
// OIDC and database configuration - set via environment variables.
// See .env.example for the full list of required variables.
define('OIDC_CLIENT_ID',         getenv('OIDC_CLIENT_ID'));
define('OIDC_CLIENT_SECRET',     getenv('OIDC_CLIENT_SECRET'));
define('OIDC_REDIRECT_URI',      getenv('OIDC_REDIRECT_URI'));
define('OIDC_ISSUER',            getenv('OIDC_ISSUER'));
define('OIDC_AUTH_ENDPOINT',     getenv('OIDC_AUTH_ENDPOINT'));
define('OIDC_TOKEN_ENDPOINT',    getenv('OIDC_TOKEN_ENDPOINT'));
define('OIDC_USERINFO_ENDPOINT', getenv('OIDC_USERINFO_ENDPOINT'));
define('OIDC_LOGOUT_ENDPOINT',   getenv('OIDC_LOGOUT_ENDPOINT'));

define('DB_HOST', '127.0.0.1');
define('DB_USER', 'docs');
define('DB_PASS', 'NSjY9bHbtkxsjyWuepo8uPud');
define('DB_NAME', 'documents');
