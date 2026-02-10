# Песочница для написания веб парсеров

Проект устанавливается **БЕЗ root'a**

Установка 
================

1. Установите docker: https://www.docker.com  
2. Установите docker compose если вы на linux: https://docs.docker.com/compose/install/
3. Создайте Github Access Token тут: https://github.com/settings/tokens/new?scopes=read:packages,repo&description=parserbox-web

    Увеличьте или уберите время жизни токена.

    Сгенерированный токен понадобится на следующих шагах. Запишите его.
4. Зачекаутите проект используя git:
Если гитхаб запрашивает имя пользователя и пароль - используйте Github Access Token в качестве пароля
```bash
git clone https://github.com/AwardWallet/parserbox-web.git
cd parserbox-web
```
5. Скачивание парсеров (engine)
=============================

Парсеры скачиваются в папку src/engine, из отдельного репозитория.
Если вы здесь первый раз, то скорее всего вам подойдут парсеры для песочницы:

https://github.com/AwardWallet/engine-sandbox

Скачайте их:
```bash
git clone https://github.com/AwardWallet/engine-sandbox.git src/engine
```

Если вы уже научились писать парсеры, и переходите на прод, скачивайте продакшн репозиторий:
```bash
git clone https://github.com/AwardWallet/engine.git src/engine
```
6. Авторизуйтесь на docker.awardwallet.com (http пароль от staging.awardwallet.com, приходит вам на почту зашифрованный gpg):
```bash
docker login docker.awardwallet.com
Username: VPupkin
Password: 
Login Succeeded
```
9. Настройте маппинг пользователей
Узнайте свой локальный user id, командой:
```bash
id -u $USER
```
Создайте файл .env вида
```bash
LOCAL_USER_ID=<ваш user id>
```
11. Запустите docker-compose
```bash
docker compose up -d
```
12. Установите зависимости composer
```bash
docker compose exec php bash
```
Вы должны увидеть запрос командной строки вида:
```bash
user@parserbox-web:/www/awardwallet$
```
12. Установите github access token для composer:
```shell
composer config github-oauth.github.com <ваш GitHub Access Token>
```
13. Залогиньтесь в npm
```bash
npm login --scope=@awardwallet --registry=https://npm.pkg.github.com
Username: <Ваше имя пользователя GitHub в нижнем регистре>
Password: <ваш GitHub Access Token>
Email: (this IS public) <ваш @awardwallet.com email>
Logged in as vsilantyev to scope @awardwallet on https://npm.pkg.github.com/.
```
13. Установите зависимости
```bash
composer install -n
```
<a name="hosts"></a>
14. Для linux/mac пропишите на хостовой машине (не в контейнере) в /etc/hosts
```
127.0.0.1 parserbox-web.awardwallet.docker
```

15. Откройте сайт в браузере
http://parserbox-web.awardwallet.docker:38401/

Селениум для локального парсинга (кроме Apple Silicon)
=====================

Для парсинга программ с использованием селениум установите (кроме Apple Silicon):
https://github.com/AwardWallet/selenium-monitor

Селениум и Apple Silicon
=============
Если у вас arm-based mac (m1, m2), то локально селениум не будет работать, как минимум хром. 
Используйте удаленный селениум:

Запустите проброс портов
```shell
./connect-remote-selenium.sh 
```
Скрипт сработает если нужные ssh ключи для ssh.awardwallet.com прописаны в ~/.ssh/config.
Возможно вам надо будет модифицировать этот скрипт под свою конфигурацию ключей. Либо добавьте настройки в ~/.ssh/config:
```ini
Host ssh.awardwallet.com
User MyUserName
IdentityFile ~/.ssh/myAwardwalletSsh.key
```

Готово. Можно по vnc подключаться к портам 11594 и т.л.
Список портов тут:
https://github.com/AwardWallet/selenium-monitor/blob/ff3ef4974eb45993d3a2edc3d0f84e75c3b1bf8f/docker-compose.yml#L34-L34

Для подключения к селениумам 100-ой версии и выше используйте веб интерфейс:
http://localhost:43765/#/

FAQ
----------

Настройка xdebug
=================

Для docker for mac ничего настраивать не надо.

Для других систем (не тестировалось, возможно уже устарело):

```bash
sudo vim /etc/php/7.2/mods-available/php_aw_debug.ini 
```

В этом файле вам надо поменять параметр xdebug.remote_host, прописать туда свой ip в локальной сети.

После этого выйти из контейнера и сделать
```bash
docker compose restart php
```
Эти изменения придется делать при смене ip, и при обновлении контейнеров.
Для docker под windows есть специальное имя хоста: docker.for.win.localhost (не проверено, отпишите кто попробует), для linux наверно что то аналогичное.

В консоли xdebug по умолчанию отключен, чтобы включить, используйте алиас xdebug, пример:
```bash
xdebug ../vendon/bin/codecept run tests/unit/SomeTest.php
```

Как обновить базу?
==================

```bash
docker compose down
docker compose down -v
docker compose down -v
docker compose pull mysql-data
```

Запускайте снова.
```bash
docker compose up -d
```

Решение проблем с php-cs-fixer на Linux
==================

docker-compose: not found
----------

Проблема возникает по причине отсутствия исполняемого файла docker-compose. В разных linux-дистрибутивах compose плагин докера запускается по-разному: где-то docker compose, где-то docker compose . Если у вас первый вариант, то поможет следующее решение:

1. Создаем файл docker compose и записываем в него следующий текст:
```bash
#!/bin/sh
docker compose $*
```
Например, вот таким образом:
```bash
echo "
#\!/bin/sh
docker compose \$*
" > docker-compose;
```
2. Делаем этот файл исполняемым:
```bash
chmod +x docker-compose;
```
3. Переносим его в любую директорию, которая находится в $PATH, например, в _/usr/local/bin/_:
```bash
sudo mv docker compose /usr/local/bin/;
```
4. Пересоздаем терминал и проверяем командой
```bash
docker-compose
```

the ‘.git/hooks/pre-commit’ was ignored because it`s not set as executable
----------

Ошибка возникает из-за неверных прав доступа к файлу _.git/hooks/pre-commit_. Решается исправлением прав доступа:

```bash
sudo chmod +x .git/hooks/pre-commit;
```