#!/bin/sh
set -e

if [ ! -z "$MYSQL_HOST" ]; then
    export MYSQL_HOST="$MYSQL_HOST"
else
    export MYSQL_HOST="localhost"
fi


if [ ! -z "$MYSQL_PORT" ]; then
    export MYSQL_PORT="$MYSQL_PORT"
else
    export MYSQL_PORT="3306"
fi

if [ ! -z "$MYSQL_USER" ]; then
    export MYSQL_USER="$MYSQL_USER"
else
    export MYSQL_USER="root"
fi

if [ ! -z "$MYSQL_PASSWORD" ]; then
    export MYSQL_PASSWORD="$MYSQL_PASSWORD"
else
    export MYSQL_PASSWORD=""
fi

job="${EXPRESSION:-"0 1 * * *"} php /usr/src/backup/mysql.php";

(crontab -l | grep -v "php /usr/src/backup/mysql.php") | crontab -
(crontab -l | grep -v "$job";echo "$job") | crontab -
crond -f