<?php

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$pdo = new PDO("mysql:host=mysql;dbname=concurrency", "root", "secret", $options);

$stmt = $pdo->query("SELECT SUM(count) FROM sharded_counter");
$total = $stmt->fetchColumn();

echo "Total count across all shards: " . ($total ?: 0) . PHP_EOL;

$stmt = $pdo->query("SELECT id, count FROM sharded_counter ORDER BY id");
while ($row = $stmt->fetch()) {
    echo "Shard " . $row['id'] . ": " . $row['count'] . PHP_EOL;
}
