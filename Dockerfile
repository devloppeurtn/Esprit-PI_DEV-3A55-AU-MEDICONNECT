COPY composer.json composer.lock ./

RUN COMPOSER_ALLOW_SUPERUSER=1 composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts

COPY . .

RUN php bin/console cache:clear --env=prod

CMD ["php", "-S", "0.0.0.0:10000", "-t", "public"]