#!/bin/sh
set -e

expression=${EXPRESSION:-"0 1 * * *"}

export MYSQL_HOST="${MYSQL_HOST:-"localhost"}"
export MYSQL_PORT="${MYSQL_PORT:-"3306"}"
export MYSQL_USER="${MYSQL_USER:-"root"}"
export MYSQL_PASSWORD="${MYSQL_PASSWORD:-""}"

exec crontab -l | grep -v "php /usr/src/backup/mysql.php" | crontab -
exec crontab -l | echo "${EXPRESSION:-"0 1 * * *"} php /usr/src/backup/mysql.php" | crontab -

exec crond &
exec /bin/sh
