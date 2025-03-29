<?php
/*Configurazioni per la connessione con il DataBase*/

$HOSTNAME = getenv('DB_HOST') ?: 'localhost';
$USERNAME = getenv('DB_USER') ?: 'root';
$PASSWORD = getenv('DB_PASS') ?: '123456';
$DBNAME = getenv('DB_NAME') ?: 'CarbonQuestDB';

?>