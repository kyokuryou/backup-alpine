# https://github.com/kyokuryou/backup-alpine.git

## Maintained by: kyokuryou

命令行运行:

`docker run -it --name my-backup -v $PWD:/usr/src/backup/data -e MYSQL_HOST=mysql -e MYSQL_PORT=3306 -e MYSQL_USER=root -e MYSQL_PASSWORD=123456 -e MYSQL_DATABASE=mysql -e EXPRESSION="*/1 * * * *" -d kyokuryou/backup-alpine:tag`

示例 stack.yml:

```
version: '2'
services:
  backup:
    image: backup-alpine
    container_name: backup
    hostname: 'backup.work'
    restart: always
    stdin_open: true
    tty: true
    links:
      - "mysql"
    depends_on:
      - "mysql"
    volumes:
      - "./data/backup:/usr/src/backup/data"
    environment:
      - MYSQL_HOST=mysql
      - MYSQL_PORT=3306
      - MYSQL_USER=root
      - MYSQL_PASSWORD=123456
      - MYSQL_DATABASE=mysql
      - EXPRESSION=*/1 * * * *
      - TZ=Asia/Shanghai
```
 
**环境变量**

**MYSQL_HOST**
```
MySQL地址，默认：localhost
```
**MYSQL_PORT**
```
MySQL端口号，默认：3306
```
**MYSQL_USER**
```
MySQL用户名，默认：root
```
**MYSQL_PASSWORD**
```
MySQL密码，默认：空
```
**MYSQL_DATABASE**
```
MySQL数据库名，默认：mysql，多个用(,)逗号隔开
```
**EXPRESSION**
```
计划任务表达式，默认：0 1 * * *（每天1点执行）
```