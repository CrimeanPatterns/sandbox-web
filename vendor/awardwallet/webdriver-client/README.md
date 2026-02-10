Запуск тестов

```shell
echo LOCAL_USER_ID=`id -u $USER` >.env
docker compose run --rm php
composer install
phpunit tests
```