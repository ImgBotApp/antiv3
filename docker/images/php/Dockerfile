FROM php:5.6-fpm

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update -qq && \
	apt-get install -y locales -qq && \
	echo "nl_NL.UTF-8 UTF-8" >> /etc/locale.gen && \
	locale-gen && \
	dpkg-reconfigure locales && \
	rm -r /var/lib/apt/lists/*

RUN apt-get update -qq && \
	apt-get install -y \
    $PHPIZE_DEPS \
    unzip \
    openssl \
    wget \
    --no-install-recommends && \
    rm -r /var/lib/apt/lists/*

RUN docker-php-source extract && \
    yes | pecl install xdebug \
    && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_autostart=on" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.idekey=xdebug" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_connect_back=0" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_port=9000" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_handler=dbgp" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_host=???" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && docker-php-source delete

RUN apt-get update -qq && \
    apt-get install -y \
    libpng-dev \
    --no-install-recommends && \
    rm -r /var/lib/apt/lists/*

RUN apt-get update -qq && \
    apt-get install -y \
    libxml2-dev \
    --no-install-recommends && \
    rm -r /var/lib/apt/lists/*

RUN apt-get update -qq && \
    apt-get install -y \
    libgmp3-dev \
    --no-install-recommends && \
    rm -r /var/lib/apt/lists/*

RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libmcrypt-dev \
        libpng12-dev \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd

RUN docker-php-ext-install pdo pdo_mysql mysqli mbstring tokenizer xml opcache json dom zip exif fileinfo mcrypt
RUN wget https://raw.githubusercontent.com/composer/getcomposer.org/1b137f8bf6db3e79a38a5bc45324414a6b1f9df2/web/installer \
	-O - -q | php -- --quiet --install-dir=/bin --filename=composer && chmod +x /bin/composer && /bin/composer self-update

COPY set_xdebug.sh /var/www/
RUN chmod +x /var/www/set_xdebug.sh

CMD ["/var/www/set_xdebug.sh"]
