FROM php:8.2-fpm

# Use the reliable PHP extension installer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# Install system dependencies
# nginx terminates client connections; gettext-base provides envsubst, used by
# the entrypoint to render the nginx config with Railway's $PORT.
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    supervisor \
    nginx \
    gettext-base \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions (pcntl is required by Reverb for signal handling)
RUN install-php-extensions \
    imagick \
    pdo_mysql \
    mbstring \
    xml \
    ctype \
    fileinfo \
    zip \
    bcmath \
    gmp \
    pcntl \
    sockets \
    opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Bun
RUN curl -fsSL https://bun.sh/install | bash
ENV PATH="/root/.bun/bin:$PATH"

WORKDIR /app

# Copy composer files first — changes here bust only the composer cache layer
COPY composer.json composer.lock ./

# Create required directories before composer install (package:discover needs bootstrap/cache)
RUN mkdir -p bootstrap/cache \
        storage/logs \
        storage/framework/sessions \
        storage/framework/views \
        storage/framework/cache \
    && chmod -R 775 storage bootstrap/cache

# Install PHP dependencies (cached unless composer.json/lock changes)
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy the rest of the application
COPY . .

# Run post-install scripts now that the full app is present
RUN composer run-script post-autoload-dump

# Build args — Railway must pass these at build time so Vite can bake them into the JS bundle
ARG VITE_REVERB_APP_KEY
ARG VITE_REVERB_HOST
ARG VITE_REVERB_PORT
ARG VITE_REVERB_SCHEME
ENV VITE_REVERB_APP_KEY=$VITE_REVERB_APP_KEY
ENV VITE_REVERB_HOST=$VITE_REVERB_HOST
ENV VITE_REVERB_PORT=$VITE_REVERB_PORT
ENV VITE_REVERB_SCHEME=$VITE_REVERB_SCHEME

# Install and build frontend
RUN bun install && bun run build

# Runtime configuration
COPY docker/php.ini /usr/local/etc/php/php.ini
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.conf
COPY docker/nginx.conf.template /etc/nginx/nginx.conf.template
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

# The bundled pool config would otherwise be picked up alongside ours
RUN rm -f /usr/local/etc/php-fpm.d/*.conf \
    && chmod +x /usr/local/bin/entrypoint.sh

# FPM children run as www-data and must be able to write caches, logs and sessions
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache

EXPOSE ${PORT:-8000}

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
