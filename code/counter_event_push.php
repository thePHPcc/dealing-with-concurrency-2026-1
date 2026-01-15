<?php
$pdo = new PDO("mysql:host=mysql;dbname=concurrency", "root", "secret", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$pdo->exec("INSERT INTO events (type) VALUES ('request_received')");

echo "Event stored\n";
