docker-up:
	docker-compose down && docker-compose up -d --build

start:
	php -S localhost:8080 -t public public/index.php
