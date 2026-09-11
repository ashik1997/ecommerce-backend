FROM php:8.2-cli

WORKDIR /var/www/html

# System deps + PHP extensions commonly needed by Laravel
RUN apt-get update \
  && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libonig-dev \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j"$(nproc)" \
    pdo_mysql \
    mbstring \
    zip \
    bcmath \
    pcntl \
    exif \
    gd \
  && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

EXPOSE 8002

CMD ["bash", "-lc", "composer install --no-interaction --prefer-dist && php artisan serve --host=0.0.0.0 --port=8002"]
