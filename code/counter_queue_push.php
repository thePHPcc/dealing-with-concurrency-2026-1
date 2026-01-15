<?php
$redis = new Redis();
$redis->connect('redis', 6379);

// Wir schieben ein "increment" Kommando in eine Liste (Queue)
$redis->lPush('counter_queue', 'increment');

echo "Enqueued increment command\n";
