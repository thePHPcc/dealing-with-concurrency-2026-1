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
        $pdo->query("SELECT 1 FROM counter LIMIT 1");
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

$redis = new Redis();
$redis->connect('redis', 6379);

echo "Worker started, waiting for commands...\n";

while (true) {
    // blockierendes Pop: wartet bis ein Element in der Liste ist (timeout 30s)
    $result = $redis->brPop(['counter_queue'], 30);

    if (!$result) {
        usleep(1000);
        continue;
    }

    $command = $result[1];

    if ($command === 'increment') {
        // Da dieser Worker der einzige ist, der schreibt, gibt es kein Race Condition Risiko
        // bzgl. anderer Worker. Aber wir müssen trotzdem korrekt updaten.
        $pdo->exec("UPDATE counter SET count = count + 1 WHERE id = 1");
        echo "Counter incremented\n";
    }

    sleep(10);
}
