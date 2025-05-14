.PHONY: up down restart build logs ps clean

up:
	docker-compose up -d

down:
	docker-compose down

restart:
	docker-compose restart

build:
	docker-compose build

logs:
	docker-compose logs -f

ps:
	docker-compose ps

clean:
	docker-compose down -v --remove-orphans 