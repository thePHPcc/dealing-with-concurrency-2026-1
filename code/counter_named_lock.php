<?php

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$pdo = new PDO("mysql:host=mysql;dbname=concurrency", "root", "secret", $options);

// Lock erwerben (wartet bis zu 10 Sekunden)
$res = $pdo->query("SELECT GET_LOCK('my_counter_lock', 1)");
$lock = $res->fetchColumn();
if ($lock !== '1') {
    throw new RuntimeException('Could not acquire lock');
}

try {
    // Kritischer Bereich
    $stmt = $pdo->query("SELECT count FROM counter WHERE id = 1");
    $count = $stmt->fetchColumn();

    $count++;
    usleep(100000); // Simuliert Arbeit (z.B. API Call)

    $stmt = $pdo->prepare("UPDATE counter SET count = ? WHERE id = 1");
    $stmt->execute([$count]);

    echo $count;
} finally {
    // Lock freigeben
    $pdo->query("SELECT RELEASE_LOCK('my_counter_lock')");
}
