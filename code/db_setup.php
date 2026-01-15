<?php
$host = 'mysql';
$db   = 'concurrency';
$user = 'root';
$pass = 'secret';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Tabellen erstellen
$pdo->exec("CREATE TABLE IF NOT EXISTS counter (
    id INT PRIMARY KEY,
    count INT DEFAULT 0,
    version INT DEFAULT 0
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS sharded_counter (
    id INT PRIMARY KEY,
    count INT DEFAULT 0,
    version INT DEFAULT 0
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(255) NOT NULL,
    processed TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Counter initialisieren falls nicht vorhanden
$stmt = $pdo->prepare("SELECT COUNT(*) FROM counter WHERE id = 1");
$stmt->execute();
if ($stmt->fetchColumn() == 0) {
    $pdo->exec("INSERT INTO counter (id, count, version) VALUES (1, 0, 0)");
}

// Shards initialisieren (z.B. 10 Shards)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM sharded_counter");
$stmt->execute();
if ($stmt->fetchColumn() == 0) {
    for ($i = 1; $i <= 10; $i++) {
        $pdo->exec("INSERT INTO sharded_counter (id, count, version) VALUES ($i, 0, 0)");
    }
}

echo "Datenbank-Setup erfolgreich abgeschlossen.\n";
