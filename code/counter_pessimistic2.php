<?php
$pdo = new PDO("mysql:host=mysql;dbname=concurrency", "root", "secret");

$pdo->beginTransaction();

// SELECT ... FOR UPDATE sperrt die Zeile
$stmt = $pdo->query("SELECT count FROM counter WHERE id = 1 FOR UPDATE");

$count = $_GET['count'];
usleep(rand(0, 100000));

$stmt = $pdo->prepare("UPDATE counter SET count = ? WHERE id = 1");
$stmt->execute([$count]);

$pdo->commit();

echo $count . PHP_EOL;
