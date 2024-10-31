FROM php:8.3-cli-alpine

RUN docker-php-ext-install pcntl

COPY . /srv/app
WORKDIR /srv/app
ENTRYPOINT ["php", "/srv/app/artisan"]
CMD ["list"]
