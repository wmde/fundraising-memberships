ARG PHP_VERSION

FROM php:${PHP_VERSION}-cli

RUN \
	apt-get update && \
	# for intl
	apt-get install -y libicu-dev && \
	docker-php-ext-install -j$(nproc) intl
