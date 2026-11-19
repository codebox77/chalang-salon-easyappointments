#!/bin/sh
# Container start: write config.php from the environment, make sure storage/ is writable, then run Apache.
set -eu

php /usr/local/lib/crcr/render-config.php

# storage/ is a volume: create the sub folders on a fresh volume and give them to the web server user.
for dir in backups cache logs sessions uploads; do
    mkdir -p "/var/www/html/storage/$dir"
done
chown -R www-data:www-data /var/www/html/storage

exec docker-php-entrypoint "$@"
