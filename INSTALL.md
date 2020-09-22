Развертывание проекта
=====================================
LINUX (Ubuntu)
-------------------------------------
### Перед началом работы
Необходимо установить следующие компоненты системы:
+ Docker 
    > https://www.digitalocean.com/community/tutorials/how-to-install-and-use-docker-on-ubuntu-20-04-ru
+ Docker compose 
    > https://www.digitalocean.com/community/tutorials/how-to-install-and-use-docker-compose-on-ubuntu-20-04-ru\
+ Git 
    ```
    sudo apt-get install git
    git config --global user.name "Your Name"
    git config --global user.email your_email@example.com
    ```
### Запуск проекта
Клонируем репозиторий в удобный для Вас каталог:
```
mkdir ~/projects
cd ~/projects
git clone https://github.com/Edzoku/creative_test.git
```
Переходим в каталог проекта и запускаем docker-compose (флаг -d используется для запуска в фоне):
```
cd creative_test
docker-compose up -d
```
Проверяем статус запущенных контейнеров:
```
docker-compose ps
```
Результат должен быть примерно следующим:
```
         Name                       Command               State                 Ports              
---------------------------------------------------------------------------------------------------
5e4d4aae7dbc_webserver   /docker-entrypoint.sh ngin ...   Up      0.0.0.0:80->80/tcp               
app                      docker-php-entrypoint php-fpm    Up      0.0.0.0:8080->8080/tcp, 9000/tcp 
database                 docker-entrypoint.sh mysqld      Up      0.0.0.0:3306->3306/tcp, 33060/tcp
```
Подтягиваем зависимости, обращаясь к контейнеру app:
```
docker-compose exec app composer install
```
Правим права на кэш:
```
docker-compose exec app chown -R nobody:nobody /var/www/app/var
```
Создаем таблицы в базе данных:
```
docker-compose exec app php /var/www/app/bin/console orm:schema-tool:create
```
### Работа с приложением
Приложение по-умолчанию доступно по адресу:
> http://localhost:80

Для импорта фильмов из Apple Trailers необходимо выполнить команду:
```
docker-compose exec app php /var/www/app/bin/console fetch:trailers
```