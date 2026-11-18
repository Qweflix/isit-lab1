FROM php:8.3-cli-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev libonig-dev \
    && docker-php-ext-install pdo_pgsql mbstring pcntl \
    && rm -rf /var/lib/apt/lists/*
COPY vendor/redis-6.2.0.tgz /tmp/redis.tgz
RUN pecl install /tmp/redis.tgz && docker-php-ext-enable redis && rm /tmp/redis.tgz
WORKDIR /app
