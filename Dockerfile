FROM php:8.3-fpm-alpine

# System packages
RUN apk add --no-cache nginx git openssh-client curl libzip-dev mariadb mariadb-client

# PHP extensions
RUN docker-php-ext-install mysqli zip

# Install typst
RUN ARCH="$(uname -m)" && \
    curl -fsSL "https://github.com/typst/typst/releases/download/v0.14.2/typst-${ARCH}-unknown-linux-musl.tar.xz" \
    | tar -xJ --strip-components=1 -C /usr/local/bin "typst-${ARCH}-unknown-linux-musl/typst" && \
    ln -s /usr/local/bin/typst /bin/typst

# MariaDB config (enable TCP networking)
COPY docker/mariadb.cnf /etc/my.cnf.d/network.cnf

# nginx config
COPY docker/nginx.conf /etc/nginx/nginx.conf

# php-fpm pool config (replaces default www.conf)
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Application code
COPY --chown=www-data:www-data . /var/www/html

# Entrypoint
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
