current_user   := $(shell id -u)
current_group  := $(shell id -g)
BUILD_DIR      := $(PWD)
DOCKER_FLAGS   := --interactive --tty
DOCKER_IMAGE   := registry.gitlab.com/fun-tech/fundraising-frontend-docker
COVERAGE_FLAGS := --coverage-html coverage

install-php:
	docker run --rm $(DOCKER_FLAGS) --volume $(BUILD_DIR):/app -w /app --volume ~/.composer:/composer --user $(current_user):$(current_group) $(DOCKER_IMAGE):composer composer install $(COMPOSER_FLAGS)

update-php:
	docker run --rm $(DOCKER_FLAGS) --volume $(BUILD_DIR):/app -w /app --volume ~/.composer:/composer --user $(current_user):$(current_group) $(DOCKER_IMAGE):composer composer update $(COMPOSER_FLAGS)

ci: phpunit cs stan

ci-with-coverage: phpunit-with-coverage cs stan

test: phpunit

phpunit:
	docker-compose run --rm --no-deps app ./vendor/bin/phpunit

phpunit-with-coverage:
	docker-compose -f docker-compose.yml -f docker-compose.debug.yml run --rm --no-deps -e XDEBUG_MODE=coverage app_debug ./vendor/bin/phpunit $(COVERAGE_FLAGS)

cs:
	docker-compose run --rm --no-deps app ./vendor/bin/phpcs

fix-cs:
	docker-compose run --rm --no-deps app ./vendor/bin/phpcbf

stan:
	docker-compose run --rm --no-deps app php -d memory_limit=1G vendor/bin/phpstan analyse --level=9 -c phpstan.test.neon --no-progress src/ tests/

setup: install-php

.PHONY: install-php update-php ci ci-with-coverage test phpunit cs fix-cs stan setup
