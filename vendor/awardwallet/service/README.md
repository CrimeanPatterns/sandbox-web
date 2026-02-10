Common lib for awardwallet projects
-----------------------------------

Console only

Настройте маппинг пользователей
```bash
echo LOCAL_USER_ID=`id -u $USER` >.env
```
Запустите тесты
```bash
docker-compose up -d mysql
docker-compose run --rm php
composer install
vendor/bin/phpunit tests
```
