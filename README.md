# Docs

Self-hosted web-based [Typst](https://typst.app) document editor. Log in via OIDC, write Typst in a CodeMirror editor, compile to PDF in-browser. Supports file management, asset uploads, share links, and Git integration per document.

## Stack

- PHP 8.3 + nginx (no framework)
- MariaDB
- Typst 0.14.2 (server-side compile)
- CodeMirror 5 editor with custom Typst syntax

## Running with Docker

```bash
cp .env.example .env
# Fill in your OIDC credentials
nano .env

# Change the default DB password (same value in both files)
nano config.php        # DB_PASS constant
nano docker/init.sql   # IDENTIFIED BY clause

docker compose up -d
```

The app listens on port 8023. Put a reverse proxy in front for TLS.

## Environment variables

See `.env.example`. Required variables:

| Variable | Description |
|---|---|
| `OIDC_CLIENT_ID` / `OIDC_CLIENT_SECRET` | Register app at your OIDC provider |
| `OIDC_REDIRECT_URI` | Must be `https://<your-domain>/auth/callback.php` |
| `OIDC_ISSUER` / `OIDC_*_ENDPOINT` | Your OIDC provider's endpoints |

The MariaDB password is hardcoded in `config.php` and `docker/init.sql` — change it to something unique before deploying. The database is not exposed outside the container.

## Data persistence

Document files are stored in `data/{id}/` and SSH keys in `ssh_keys/` - both are Docker named volumes. The MariaDB data is also a named volume.

## Nginx rewrites required

If running without Docker Compose, add these to your vhost:

```nginx
rewrite ^/settings$           /settings.php last;
rewrite ^/editor/(\d+)$       /editor.php   last;
rewrite ^/shared/([a-f0-9]+)$ /shared.php?t=$1 last;
```
