<?php
$maxRetries = 10;
$retryCount = 0;
$pdo = null;

while ($retryCount < $maxRetries) {
    try {
        $pdo = new PDO("mysql:host=mysql;dbname=concurrency", "root", "secret", [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        // Also check if the table exists
        $pdo->query("SELECT 1 FROM events LIMIT 1");
        break;
    } catch (PDOException $e) {
        $retryCount++;
        echo "Waiting for MySQL and tables... (Attempt $retryCount)\n";
        sleep(2);
    }
}

if (!$pdo) {
    die("Could not connect to MySQL after $maxRetries attempts.\n");
}

echo "Event Worker started...\n";

while (true) {
    // Start transaction to ensure atomicity
    $pdo->beginTransaction();

    // Select one unprocessed event and lock it for update
    $stmt = $pdo->query("SELECT id FROM events WHERE processed = 0 LIMIT 1 FOR UPDATE");
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($event) {
        // Mark as processed
        $pdo->exec("UPDATE events SET processed = 1 WHERE id = " . $event['id']);

        // Update counter
        $pdo->exec("UPDATE counter SET count = count + 1 WHERE id = 1");

        $pdo->commit();
        echo "Processed event ID: " . $event['id'] . "\n";
    } else {
        $pdo->rollBack();
    }

    // Wait a bit before checking again
    usleep(100000); // 100ms
}
