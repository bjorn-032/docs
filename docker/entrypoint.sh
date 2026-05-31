#!/bin/sh
set -e

# Prepare directories
mkdir -p /run/mysqld /var/lib/mysql
chown -R mysql:mysql /run/mysqld /var/lib/mysql

# Initialize MariaDB data directory on first run
if [ ! -d /var/lib/mysql/mysql ]; then
    mysql_install_db --user=mysql --datadir=/var/lib/mysql --skip-test-db
fi

# Start MariaDB
mariadbd --user=mysql --datadir=/var/lib/mysql &

# Wait for MariaDB to be ready via socket (30s timeout)
i=0
until mariadb --socket=/run/mysqld/mysqld.sock -e "SELECT 1" >/dev/null 2>&1; do
    i=$((i+1))
    [ $i -ge 30 ] && echo "MariaDB failed to start" && exit 1
    sleep 1
done

# Apply schema (all statements are idempotent)
mariadb --socket=/run/mysqld/mysqld.sock < /var/www/html/docker/init.sql

# Ensure data and ssh_keys directories exist with correct ownership
mkdir -p /var/www/html/data /var/www/html/ssh_keys
chown -R www-data:www-data /var/www/html/data /var/www/html/ssh_keys

# Start php-fpm in background
php-fpm --daemonize

# Start nginx in foreground
exec nginx -g 'daemon off;'
