# ── Build arguments ────────────────────────────────────────────────────────────
# Declared before FROM: available as global ARGs passed to the build via --build-arg
ARG PHP_VERSION=8.3
ARG NODE_VERSION=22
ARG YARN_VERSION=1.22.22

FROM php:${PHP_VERSION}-cli-bookworm

# Must be redeclared after FROM for the global ARGs to be usable below
ARG NODE_VERSION
ARG YARN_VERSION

# ── System dependencies ─────────────────────────────────────────────────────────
# --mount=type=cache: .deb files are cached between builds (BuildKit)
# ghostscript: required by Imagick to read PDFs (PdfService, DocumentProvider)
# gnupg: required to verify the NodeSource repository signature (gpg --dearmor)
RUN --mount=type=cache,target=/var/cache/apt,sharing=locked \
    --mount=type=cache,target=/var/lib/apt,sharing=locked \
    apt-get update && apt-get install -y --no-install-recommends \
        git \
        ghostscript \
        curl \
        gnupg \
        libfreetype6-dev \
        libicu-dev \
        libjpeg-dev \
        libmagickwand-dev \
        libpng-dev \
        libxslt1-dev \
        libzip-dev \
        make \
        unzip \
    && curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key \
        | gpg --dearmor -o /usr/share/keyrings/nodesource.gpg \
    && echo "deb [signed-by=/usr/share/keyrings/nodesource.gpg] \
        https://deb.nodesource.com/node_${NODE_VERSION}.x nodistro main" \
        > /etc/apt/sources.list.d/nodesource.list \
    && apt-get update && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /etc/apt/sources.list.d/nodesource.list

# ── ImageMagick policy ─────────────────────────────────────────────────────────
# Debian forbids reading PDFs by default
RUN sed -i 's/rights="none" pattern="PDF"/rights="read|write" pattern="PDF"/' /etc/ImageMagick-6/policy.xml

# ── PHP extensions ──────────────────────────────────────────────────────────────
# Mirrors the preprod extension set (php --ini on vault-pp).
# exif, gd and xsl are hard requirements of vendor packages (composer check-platform-reqs).
# ctype, curl, dom, fileinfo, iconv, mbstring, openssl and sodium are already
# compiled in the official image.
# Xdebug is enabled but defaults to mode off — driven by XDEBUG_MODE (entrypoint).
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        calendar \
        exif \
        gd \
        gettext \
        intl \
        mysqli \
        opcache \
        pdo_mysql \
        sockets \
        xsl \
        zip \
    && pecl install apcu imagick xdebug \
    && docker-php-ext-enable apcu imagick xdebug

# ── PHP config ──────────────────────────────────────────────────────────────────
COPY docker/shared/php/php.ini $PHP_INI_DIR/conf.d/php.ini
COPY docker/shared/php/opcache.ini $PHP_INI_DIR/conf.d/opcache.ini

# ── Composer ────────────────────────────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ── Symfony CLI ─────────────────────────────────────────────────────────────────
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony5/bin/symfony /usr/local/bin/symfony \
    && rm -rf /root/.symfony5

# ── Yarn ────────────────────────────────────────────────────────────────────────
RUN corepack enable \
    && corepack prepare yarn@${YARN_VERSION} --activate

# ── Workdir ─────────────────────────────────────────────────────────────────────
WORKDIR /vault

# ── Git safe.directory ──────────────────────────────────────────────────────────
# Needed when /vault is a mounted volume: Git refuses to operate in a directory
# owned by a different user than the current one
RUN git config --global --add safe.directory /vault

# ── Entrypoint ──────────────────────────────────────────────────────────────────
COPY docker/shared/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
CMD ["tail", "-f", "/dev/null"]
