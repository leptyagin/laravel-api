DC = docker compose
APP_CONT = app
PHP = $(DC) run --rm $(APP_CONT) php
COMPOSER = $(DC) run --rm $(APP_CONT) composer

up:
	$(DC) up -d

down:
	$(DC) down

build:
	$(DC) up -d --build

install:
	$(COMPOSER) install
	$(PHP) artisan key:generate
	$(PHP) artisan migrate --seed

lint:
	docker compose run --rm --entrypoint "" app composer run test:types

test-full:
	$(COMPOSER) run test

test:
	$(PHP) artisan test

test-unit:
	$(PHP) artisan test tests/Unit

test-feat:
	$(PHP) artisan test tests/Feature

phpstan:
	docker compose run --rm --entrypoint "" app composer run test:types

migrate:
	$(PHP) artisan migrate

fresh:
	$(PHP) artisan migrate:fresh --seed

tinker:
	$(PHP) artisan tinker

bash:
	$(DC) exec -it $(APP_CONT) sh

logs:
	$(DC) logs -f

clear:
	$(PHP) artisan cache:clear
	$(PHP) artisan config:clear
	$(PHP) artisan route:clear
