FROM php:8.4-apache

WORKDIR /var/www/html

COPY BookManager/ /var/www/html/

EXPOSE 80