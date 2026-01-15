<?php

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$pdo = new PDO("mysql:host=mysql;dbname=concurrency", "root", "secret", $options);

function incrementCounter($pdo, $maxRetries = 5) {
    for ($i = 0; $i < $maxRetries; $i++) {
        // 1. Lesen (ohne Lock!)
        $stmt = $pdo->query("SELECT count, version FROM counter WHERE id = 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $count = $row['count'];
        $version = $row['version'];

        // 2. Modifizieren
        $count++;
        usleep(rand(1000, 50000)); // Simuliert Arbeit

        // 3. Schreiben - nur wenn Version noch stimmt!
        $stmt = $pdo->prepare("
            UPDATE counter 
            SET count = ?, version = version + 1 
            WHERE id = 1 AND version = ?
        ");
        $stmt->execute([$count, $version]);

        // 4. Prüfen ob Update geklappt hat
        if ($stmt->rowCount() > 0) {
            return $count; // Erfolg!
        }

        // Retry mit random backoff
        usleep(rand(10000, 100000));
    }

    header("HTTP/1.1 409 Conflict");
    throw new Exception("Could not update counter after $maxRetries retries");
}

try {
    echo incrementCounter($pdo);
} catch (Exception $e) {
    echo $e->getMessage();
}
