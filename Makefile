.PHONY: build setup test up down ensure-composer-local ensure-composer-test

ensure-composer-local:
	@if [ ! -f composer.local.json ] && [ -f composer.local.json.example ]; then \
		cp composer.local.json.example composer.local.json; \
	fi

ensure-composer-test: ensure-composer-local
	docker compose run --rm --no-deps app php -r '$$base = json_decode(file_get_contents("composer.json"), true, 512, JSON_THROW_ON_ERROR); $$overlay = file_exists("composer.local.json") ? json_decode(file_get_contents("composer.local.json"), true, 512, JSON_THROW_ON_ERROR) : []; $$base["repositories"] = array_merge($$overlay["repositories"] ?? [], $$base["repositories"] ?? []); $$base["require"] = array_merge($$base["require"] ?? [], $$overlay["require"] ?? []); $$base["require-dev"] = array_merge($$base["require-dev"] ?? [], $$overlay["require-dev"] ?? []); file_put_contents("composer.test.json", json_encode($$base, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);'

build:
	docker compose build

setup: ensure-composer-test
	docker compose up -d
	docker compose run --rm -e COMPOSER=composer.test.json app composer install
	docker compose down --remove-orphans

up:
	docker compose up -d

down:
	docker compose down --remove-orphans

test: ensure-composer-test
	docker compose run --rm app vendor/bin/pest
