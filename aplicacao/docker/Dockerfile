FROM php:8.3-apache
RUN apt-get update && apt-get install -y --no-install-recommends libzip-dev libonig-dev \
    && docker-php-ext-install pdo_mysql zip mbstring \
    && rm -rf /var/lib/apt/lists/*
RUN a2enmod headers
RUN printf 'ServerTokens Prod\nServerSignature Off\n' > /etc/apache2/conf-available/security-custom.conf && a2enconf security-custom
