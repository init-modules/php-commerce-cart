FROM dunglas/frankenphp:php8.4 AS base

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN sed -i 's/deb.debian.org/ftp.de.debian.org/g' /etc/apt/sources.list.d/debian.sources
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip \
    libpq-dev libzip-dev libicu-dev sudo \
    && \
    MAKEFLAGS="-j$(nproc)" install-php-extensions \
    pdo_pgsql \
    intl \
    bcmath \
    pcntl \
    zip \
    exif \
    redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ARG UID=1000
ARG GID=1000
ARG USER=laravel

RUN groupadd -g $GID $USER && \
    useradd -u $UID -g $GID -m -s /bin/bash $USER && \
    echo "$USER ALL=(ALL) NOPASSWD:ALL" > /etc/sudoers.d/$USER && \
    chmod 0440 /etc/sudoers.d/$USER

RUN [ -f /usr/local/bin/frankenphp ] || ln -s /usr/bin/frankenphp /usr/local/bin/frankenphp
RUN echo "export PS1='\[\033[01;32m\]\u\[\033[00m\] ➜ \[\033[01;34m\]\w\[\033[00m\] \$(git branch 2>/dev/null | grep \"^*\" | sed \"s/^* //\" | xargs -I {} echo \"({})\") $ '" >> /home/$USER/.bashrc

WORKDIR /var/www
RUN chown -R $USER:$USER /var/www

FROM base AS development
ARG USER=laravel
USER $USER
CMD ["sh", "-c", "trap 'exit 0' TERM INT; sleep infinity & wait"]
