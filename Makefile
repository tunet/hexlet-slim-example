docker-up:
	docker-compose down && docker-compose up -d --build

start:
	php -S 0.0.0.0:8080 -t public public/index.php
