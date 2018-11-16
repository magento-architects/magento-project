FROM webdevops/php-apache-dev:7.1
RUN apt-get update && apt-get install -y libicu-dev && docker-php-ext-install intl
ADD . /app
RUN chown -R application:application /app/app/etc /app/generated /app/var