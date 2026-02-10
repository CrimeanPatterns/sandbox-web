FROM php:7.2-cli
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
RUN apt-get update && apt-get install -y git
RUN apt-get update && apt-get install -y libzip-dev \
	&& pecl install zip \
	&& docker-php-ext-enable zip