FROM php:7.1-fpm

RUN apt-get update \
  && apt-get install -y \
        libjpeg-dev \
  		libpng-dev \
  		libpq-dev \
  		curl \
  		zip \
  		unzip \
  		git \
        mysql-client \
        memcached \
        nano \
        vim \
        libmemcached-dev

RUN curl -sS https://getcomposer.org/installer -o composer-setup.php \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer

RUN useradd -m docker  && usermod -u 1000 docker  && groupmod -g 1000 docker \
    && echo 'export PATH="/site/docroot/vendor/bin:$PATH"' >> /home/docker/.bashrc

RUN docker-php-ext-configure gd
RUN docker-php-ext-install gd opcache zip
RUN docker-php-ext-install pdo pdo_mysql


# Install Memcached for php 7
RUN curl -L -o /tmp/memcached.tar.gz "https://github.com/php-memcached-dev/php-memcached/archive/php7.tar.gz" \
    && mkdir -p /usr/src/php/ext/memcached \
    && tar -C /usr/src/php/ext/memcached -zxvf /tmp/memcached.tar.gz --strip 1 \
    && docker-php-ext-configure memcached \
    && docker-php-ext-install memcached \
    && rm /tmp/memcached.tar.gz

WORKDIR /site

EXPOSE 9000