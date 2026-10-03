FROM php:8-cli

# Official PHP 8 CLI builds pdo_sqlite into the binary (--with-pdo-sqlite).
RUN php -r 'exit(extension_loaded("pdo_sqlite") ? 0 : 1);'

WORKDIR /app

COPY . /app

RUN mkdir -p /app/data && chmod 0777 /app/data

ENV PORT=8000

EXPOSE 8000

CMD ["sh", "-c", "php -S 0.0.0.0:$PORT router.php"]
