<?php
date_default_timezone_set('PRC');
error_reporting(E_ERROR | E_WARNING | E_PARSE);

$host = getenv("MYSQL_HOST");
$port = getenv("MYSQL_PORT");
$user = getenv("MYSQL_USER");
$password = getenv("MYSQL_PASSWORD");
$database = getenv("MYSQL_DATABASE");
$backupPath = "/usr/src/backup/data/$database/";

define("NUMBER_TYPES", array(
    MYSQLI_TYPE_DECIMAL, MYSQLI_TYPE_TINY, MYSQLI_TYPE_SHORT,
    MYSQLI_TYPE_LONG, MYSQLI_TYPE_FLOAT, MYSQLI_TYPE_DOUBLE,
    MYSQLI_TYPE_LONGLONG, MYSQLI_TYPE_INT24, MYSQLI_TYPE_NEWDECIMAL,
    MYSQLI_TYPE_BIT
));

function tables($link)
{
    $tables = array();
    if ($result = mysqli_query($link, "SHOW TABLES")) {
        while ($table = mysqli_fetch_row($result)) {
            $tables[] = $table[0];
        }
        mysqli_free_result($result);
    }
    return $tables;
}

function tableDDL($link, $tableName)
{
    if ($result = mysqli_query($link, "SHOW CREATE TABLE $tableName")) {
        $row = mysqli_fetch_row($result);
        mysqli_free_result($result);
        return $row[1];
    }
}

function tableFields($select)
{
    $fields = array();;
    while ($desc = mysqli_fetch_field($select)) {
        $fields[$desc->name] = $desc->type;
    }
    return $fields;
}

function hasData($link, $tableName)
{
    $result = mysqli_query($link, "SELECT COUNT(1) FROM $tableName");
    $row = mysqli_fetch_row($result);
    mysqli_free_result($result);
    return $row[0] > 0;
}

function convertValue($type, $value)
{
    if (is_null($value)) {
        return "NULL";
    }
    if (in_array($type, NUMBER_TYPES)) {
        return $value;
    }
    return "'$value'";
}

function insertSQL($row, $fields, $tableName)
{
    $insert = "INSERT INTO $tableName (#FIELD) VALUES (#VALUE);\n";
    $i = 0;
    $c = count($fields);
    foreach ($fields as $key => $value) {
        $val = convertValue($value, $row->{$key});
        if ($i < $c - 1) {
            $insert = str_replace("#FIELD", "'$key', #FIELD", $insert);
            $insert = str_replace("#VALUE", $val . ", #VALUE", $insert);
        } else {
            $insert = str_replace("#FIELD", "'$key'", $insert);
            $insert = str_replace("#VALUE", $val, $insert);
        }
        $i++;
    }
    return $insert;
}

if (!file_exists($backupPath)) {
    mkdir($backupPath, 0777, true);
}
$dbs = explode(",", $database);
foreach ($dbs as $db) {
    $link = mysqli_connect($host, $user, $password, $db, $port);
    if (!$link) {
        die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
    }
    $fileName = $backupPath . $db . "_" . date('YmdHis') . '.sql';
    file_put_contents($fileName, "/*\n", FILE_APPEND);
    file_put_contents($fileName, "MySQL Backup\n", FILE_APPEND);
    file_put_contents($fileName, "Database: $db\n", FILE_APPEND);
    file_put_contents($fileName, "Backup Time: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    file_put_contents($fileName, "*/\n", FILE_APPEND);
    file_put_contents($fileName, "START TRANSACTION;\n", FILE_APPEND);

    foreach ($tables = tables($link) as $tableName) {
        file_put_contents($fileName, "--\n", FILE_APPEND);
        file_put_contents($fileName, "-- Table Structure $tableName\n", FILE_APPEND);
        file_put_contents($fileName, "--\n", FILE_APPEND);
        file_put_contents($fileName, "DROP TABLE IF EXISTS $tableName;\n", FILE_APPEND);
        file_put_contents($fileName, tableDDL($link, $tableName) . "\n", FILE_APPEND);
        if (!hasData($link, $tableName)) {
            continue;
        }
        file_put_contents($fileName, "--\n", FILE_APPEND);
        file_put_contents($fileName, "-- Table Data $tableName\n", FILE_APPEND);
        file_put_contents($fileName, "--\n", FILE_APPEND);

        if ($select = mysqli_query($link, "SELECT * FROM $tableName", MYSQLI_USE_RESULT)) {
            $fields = tableFields($select);
            while ($row = mysqli_fetch_object($select)) {
                $insert = insertSQL($row, $fields, $tableName);
                file_put_contents($fileName, $insert, FILE_APPEND);
            }
            mysqli_free_result($select);
        }
    }
    file_put_contents($fileName, "COMMIT;\n", FILE_APPEND);
    mysqli_close($link);
}