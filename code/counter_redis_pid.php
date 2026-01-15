<?php
$redis = new Redis();
$redis->connect('redis', 6379);

$lockKey = 'lock:counter';
$acquired = false;
$attempts = 0;
$myPid = getmypid();

while (!$acquired && $attempts < 200) {
    // NX: Nur setzen, wenn der Key noch nicht existiert
    // EX => 10: Nach 10 Sekunden automatisch ablaufen lassen (TTL)
    // Wir speichern unsere PID als Wert
    $acquired = $redis->set($lockKey, $myPid, ['NX', 'EX' => 10]);

    if (!$acquired) {
        // Schauen, ob der Prozess, der den Lock hält, noch lebt
        $lockPid = $redis->get($lockKey);
        if ($lockPid && !posix_kill((int)$lockPid, 0)) {
            // Prozess scheint nicht mehr zu existieren.
            // Wir versuchen den Lock zu übernehmen.
            // Um Race Conditions beim Stehlen zu vermeiden, nutzen wir GETSET oder ein Lua Script.
            // Hier einfach: Wir löschen den alten Lock, wenn er noch die alte PID hat.
            // Aber Vorsicht: Wenn ein neuer Prozess ihn schon hat, dürfen wir ihn nicht löschen.
            // Ein Lua-Script ist hier am sichersten.
            $script = '
                if redis.call("get", KEYS[1]) == ARGV[1] then
                    return redis.call("del", KEYS[1])
                else
                    return 0
                end
            ';
            $redis->eval($script, [$lockKey, $lockPid], 1);
            // Nach dem Löschen versuchen wir es im nächsten Schleifendurchlauf erneut mit set NX.
            continue;
        }

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

        file_put_contents('log.txt', $count . PHP_EOL, FILE_APPEND);

    } finally {
        // Nur löschen, wenn es immer noch unser Lock ist
        $script = '
            if redis.call("get", KEYS[1]) == ARGV[1] then
                return redis.call("del", KEYS[1])
            else
                return 0;
            end
        ';
        $redis->eval($script, [$lockKey, $myPid], 1);
    }
} else {
    header("HTTP/1.1 503 Service Unavailable");
    echo "Could not acquire lock";
}
