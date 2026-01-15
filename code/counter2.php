<?php declare(strict_types=1);

$fp = fopen('lock.txt', 'a');
flock($fp, LOCK_EX);

$count = $_GET['count'];
usleep(rand(0, 100000));

file_put_contents('counter.txt', $count);
file_put_contents('log.txt', $count . PHP_EOL, FILE_APPEND);
echo $count;

flock($fp, LOCK_UN);
fclose($fp);
