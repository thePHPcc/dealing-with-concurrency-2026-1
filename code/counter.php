<?php declare(strict_types=1);

$count = file_exists('counter.txt') ? (int)file_get_contents('counter.txt') : 0;
$count++;

usleep(1000);

file_put_contents('counter.txt', $count);
file_put_contents('log.txt', $count . PHP_EOL, FILE_APPEND);
echo $count;
