FROM composer:2 AS deps
WORKDIR /app
COPY composer.json .
RUN composer install --no-dev --no-interaction --ignore-platform-reqs

FROM php:8.4-cli
ARG RELEASE=dev
ENV RELEASE=${RELEASE}
WORKDIR /app
COPY --from=deps /app/vendor vendor
COPY index.php .
EXPOSE 8080
CMD ["php", "-S", "0.0.0.0:8080", "index.php"]
