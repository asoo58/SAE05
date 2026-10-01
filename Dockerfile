FROM php:8.4-cli-alpine

RUN apk add --no-cache bash icu-dev libpq-dev git unzip \
    && docker-php-ext-install intl pdo pdo_pgsql opcache

WORKDIR /app

EXPOSE 8080
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public/"]