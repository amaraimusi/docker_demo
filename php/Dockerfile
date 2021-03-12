FROM php:7.4.13-apache-buster

RUN apt-get update \
    && docker-php-ext-install pdo_mysql \
    && a2enmod rewrite

RUN /bin/sh -c a2enmod rewrite


# rewrite パッケージはcakephpで必要