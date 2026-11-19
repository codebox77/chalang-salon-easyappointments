# Easy!Appointments (찰랑찰랑 customized) - PHP 8.4 + Apache, same versions as the live server.
#
# Build from the project root (the folder that contains index.php):
#   docker build -f docker/Dockerfile -t crcr-booking:latest .
#
# config.php is NOT part of the image: docker/docker-entrypoint.sh writes it from environment variables when the
# container starts (see docker/.env.example), so no password or key is baked into the image.

FROM php:8.4-apache

# PHP extensions used by the app: mysqli (database), gd (captcha / images), intl, zip, opcache.
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends libpng-dev libjpeg62-turbo-dev libfreetype6-dev libicu-dev libzip-dev tzdata; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install -j"$(nproc)" mysqli gd intl zip opcache; \
    apt-get purge -y --auto-remove; \
    rm -rf /var/lib/apt/lists/*

# PHP settings: production php.ini, Korea time, hidden version, bigger uploads (logo upload), opcache.
RUN set -eux; \
    mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"; \
    { \
        echo 'date.timezone = Asia/Seoul'; \
        echo 'expose_php = Off'; \
        echo 'upload_max_filesize = 8M'; \
        echo 'post_max_size = 10M'; \
        echo 'memory_limit = 256M'; \
        echo 'session.cookie_httponly = 1'; \
        echo 'opcache.enable = 1'; \
        echo 'opcache.validate_timestamps = 0'; \
    } > "$PHP_INI_DIR/conf.d/zz-crcr.ini"

# Apache: .htaccess files of the app are honoured; version info hidden; internal folders and dot files blocked.
COPY docker/apache-crcr.conf /etc/apache2/conf-available/crcr.conf
RUN a2enmod rewrite headers && a2enconf crcr

ENV TZ=Asia/Seoul
WORKDIR /var/www/html

COPY --chown=www-data:www-data . /var/www/html

# DEBUG_MODE is off in the container, so the app loads the *.min.js files. Customized scripts were edited as *.js,
# so refresh every *.min.js that is older than its *.js (copies, not minified - correct and still cached).
RUN set -eux; \
    find assets/js -name '*.js' ! -name '*.min.js' | while read -r file; do \
        min="${file%.js}.min.js"; \
        if [ ! -f "$min" ] || [ "$file" -nt "$min" ]; then cp -p "$file" "$min"; fi; \
    done; \
    rm -rf docker config-sample.php; \
    mkdir -p storage/backups storage/cache storage/logs storage/sessions storage/uploads; \
    chown -R www-data:www-data storage

COPY docker/docker-entrypoint.sh /usr/local/bin/crcr-entrypoint
COPY docker/render-config.php /usr/local/lib/crcr/render-config.php
# Strip Windows line endings (the files may be edited on Windows), then make the entry point executable.
RUN sed -i 's/\r$//' /usr/local/bin/crcr-entrypoint && chmod 755 /usr/local/bin/crcr-entrypoint

VOLUME ["/var/www/html/storage"]

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD php -r 'exit(@file_get_contents("http://127.0.0.1/index.php/login") === false ? 1 : 0);'

EXPOSE 80

ENTRYPOINT ["crcr-entrypoint"]
CMD ["apache2-foreground"]
