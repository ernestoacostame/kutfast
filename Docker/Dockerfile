FROM php:8.3-fpm-alpine

# ── Labels ──────────────────────────────────────────────────
LABEL maintainer="KutFast Contributors"
LABEL description="Self-hosted web panel for publishing to KutPod"
LABEL org.opencontainers.image.source="https://github.com/yourusername/kutfast"

# ── System dependencies ──────────────────────────────────────
RUN apk add --no-cache \
    nginx \
    ffmpeg \
    curl \
    bash \
    shadow \
    && rm -rf /var/cache/apk/*

# ── PHP extensions ───────────────────────────────────────────
RUN docker-php-ext-install fileinfo

# ── Create app user (non-root) ───────────────────────────────
RUN addgroup -g 1001 -S kutfast \
    && adduser -u 1001 -S kutfast -G kutfast

# ── Nginx configuration ──────────────────────────────────────
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/default.conf /etc/nginx/http.d/default.conf

# ── PHP-FPM configuration ────────────────────────────────────
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-kutfast.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/kutfast.ini

# ── Copy application files ───────────────────────────────────
WORKDIR /var/www/html

COPY --chown=kutfast:kutfast . .

# ── Set up data directories ──────────────────────────────────
RUN mkdir -p /data \
    && cp /var/www/html/config.example.php /data/config.php \
    && echo '[]' > /data/podcasts.json \
    && echo '{}' > /data/local_drafts.json \
    && echo '[]' > /data/templates.json \
    && mkdir -p /data/tmp \
    && chown -R kutfast:kutfast /data \
    && chmod 700 /data/tmp \
    && chmod 664 /data/podcasts.json /data/local_drafts.json /data/templates.json

# ── Symlink data files to /data volume ───────────────────────
RUN rm -f /var/www/html/config.php \
       /var/www/html/podcasts.json \
       /var/www/html/local_drafts.json \
       /var/www/html/templates.json \
    && rm -rf /var/www/html/tmp \
    && ln -sf /data/config.php        /var/www/html/config.php \
    && ln -sf /data/podcasts.json     /var/www/html/podcasts.json \
    && ln -sf /data/local_drafts.json /var/www/html/local_drafts.json \
    && ln -sf /data/templates.json    /var/www/html/templates.json \
    && ln -sf /data/tmp               /var/www/html/tmp

# ── Nginx log dirs ───────────────────────────────────────────
RUN mkdir -p /var/log/nginx \
    && chown -R kutfast:kutfast /var/log/nginx \
    && chown -R kutfast:kutfast /var/lib/nginx \
    && chown -R kutfast:kutfast /var/www/html

# ── Entrypoint ───────────────────────────────────────────────
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# ── Volumes ──────────────────────────────────────────────────
# Mount /data to persist: config.php, podcasts.json,
# local_drafts.json, templates.json, tmp/
VOLUME ["/data"]

EXPOSE 8080

ENTRYPOINT ["/entrypoint.sh"]
