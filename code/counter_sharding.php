<?php

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$pdo = new PDO("mysql:host=mysql;dbname=concurrency", "root", "secret", $options);

/**
 * Sharding: We split the counter into multiple "shards" (rows).
 * Each request randomly picks one shard to increment.
 * This significantly reduces lock contention because concurrent requests
 * are likely to hit different shards.
 */
function incrementShardedCounter($pdo, $maxRetries = 5) {
    // 0. Pick a random shard (we initialized 10 shards in db_setup.php)
    $shardId = rand(1, 10);

    for ($i = 0; $i < $maxRetries; $i++) {
        // 1. Read (Optimistic Locking)
        $stmt = $pdo->prepare("SELECT count, version FROM sharded_counter WHERE id = ?");
        $stmt->execute([$shardId]);
        $row = $stmt->fetch();

        $count = $row['count'];
        $version = $row['version'];

        // 2. Modify
        $count++;
        usleep(rand(1000, 50000)); // Simulate work

        // 3. Write - only if version still matches
        $stmt = $pdo->prepare("
            UPDATE sharded_counter 
            SET count = ?, version = version + 1 
            WHERE id = ? AND version = ?
        ");
        $stmt->execute([$count, $shardId, $version]);

        // 4. Check if update was successful
        if ($stmt->rowCount() > 0) {
            return ["shard" => $shardId, "count" => $count];
        }

        // Retry with random backoff if there was a collision on this specific shard
        usleep(rand(10000, 100000));
    }

    header("HTTP/1.1 409 Conflict");
    throw new Exception("Could not update shard $shardId after $maxRetries retries");
}

try {
    $result = incrementShardedCounter($pdo);
    echo "Shard " . $result['shard'] . " incremented to " . $result['count'];
} catch (Exception $e) {
    echo $e->getMessage();
}
