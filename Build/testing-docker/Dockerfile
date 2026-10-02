ARG php_version=8.4

FROM php:${php_version}-cli

# If the development network routes TLS through a corporate proxy, then root CA
# must be trusted for apt, composer and the tests themselves. Build/Scripts/
# buildTestImages.sh stages the host's additional CA certificates into the
# build context (the directory stays empty on hosts without such CAs). They
# are appended to the trust store directly, because update-ca-certificates
# ignores certificates that /etc/ca-certificates.conf does not know.
COPY build-ca/ /etc/ssl/extra-ca/
RUN set -eux; \
    cd /etc/ssl/certs; \
    for ca in /etc/ssl/extra-ca/*; do \
        [ -f "$ca" ] || continue; \
        cat "$ca" >> ca-certificates.crt; \
        cp "$ca" .; \
    done; \
    c_rehash /etc/ssl/certs; \
    rm -rf /etc/ssl/extra-ca

# Extensions the test matrix needs that the base image does not ship:
# intl (composer requirement), zip (composer requirement),
# pdo_mysql/pdo_pgsql (functional test databases). mbstring, sqlite3,
# pdo_sqlite, fileinfo and simplexml are already part of the base image.
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        libicu-dev \
        libmariadb-dev \
        libpq-dev \
        libzip-dev; \
    rm -rf /var/lib/apt/lists/*; \
    docker-php-ext-install intl zip pdo_mysql pdo_pgsql

RUN set -eux; \
    curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php; \
    php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer --quiet; \
    rm /tmp/composer-setup.php

# Runs as a user matching the host (build args) so the bind-mounted
# repository keeps its ownership. The composer home is pre-created so the
# named volume mounted on it inherits the user's ownership.
ARG USER_UID=1000
ARG USER_GID=1000
ARG USER_NAME=builder
RUN set -eux; \
    groupadd --gid "${USER_GID}" "${USER_NAME}"; \
    useradd --uid "${USER_UID}" --gid "${USER_NAME}" \
        --home-dir "/home/${USER_NAME}" --create-home --shell /bin/bash "${USER_NAME}"; \
    mkdir -p "/home/${USER_NAME}/.composer"; \
    chown -R "${USER_NAME}:${USER_NAME}" "/home/${USER_NAME}"

USER builder
ENV COMPOSER_HOME=/home/builder/.composer
WORKDIR /repo
