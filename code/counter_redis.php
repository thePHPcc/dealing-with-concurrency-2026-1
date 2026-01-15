<?php
$redis = new Redis();
$redis->connect('redis', 6379);

$lockKey = 'lock:counter';
$acquired = false;
$attempts = 0;
while (!$acquired && $attempts < 200) {
    // NX: Nur setzen, wenn der Key noch nicht existiert
    // EX => 10: Nach 10 Sekunden automatisch ablaufen lassen (TTL)
    $acquired = $redis->set($lockKey, '1', ['NX', 'EX' => 100]);
    if (!$acquired) {
        usleep(100000); // 100ms warten
        $attempts++;
    }
}

if ($acquired) {
    try {
        $pdo = new PDO("mysql:host=mysql;dbname=concurrency", "root", "secret");

        $stmt = $pdo->query("SELECT count FROM counter WHERE id = 1");
        $count = $stmt->fetchColumn();

        $count++;
        usleep(100000);

        $stmt = $pdo->prepare("UPDATE counter SET count = ? WHERE id = 1");
        $stmt->execute([$count]);

        echo $count;
    } finally {
        $redis->del($lockKey);
    }
} else {
    header("HTTP/1.1 503 Service Unavailable");
    echo "Could not acquire lock";
}
