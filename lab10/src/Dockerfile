# syntax=docker/dockerfile:1
FROM alpine:3.22
RUN --mount=type=secret,id=proxy_ca \
    if [ -f /run/secrets/proxy_ca ]; then export SSL_CERT_FILE=/run/secrets/proxy_ca; fi; \
    apk add --no-cache php84 php84-pdo_pgsql php84-mbstring php84-pecl-redis php84-curl
WORKDIR /app
CMD ["php84", "-S", "0.0.0.0:8000", "-t", "public", "public/index.php"]
