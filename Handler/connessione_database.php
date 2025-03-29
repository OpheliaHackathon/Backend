<?php
require 'DataBase/db_config.php';

$dbHost = $HOSTNAME;
$dbUser = $USERNAME;
$dbPass = $PASSWORD;
$dbName = $DBNAME;
$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

if ($conn->connect_errno) {
    $response = [
        'success' => 'false',
        'message' => 'Errore nella connessione con il database',
    ];
    echo json_encode($response);
    exit();
}
?>