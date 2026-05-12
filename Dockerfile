FROM dunglas/frankenphp:php8.2-bookworm

# Install pdo_mysql and intl extensions
RUN install-php-extensions pdo_mysql intl mbstring zip gd opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction

COPY . .

RUN APP_ENV=prod php bin/console cache:clear

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
