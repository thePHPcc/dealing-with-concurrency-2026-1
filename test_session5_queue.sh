#!/bin/bash
echo "Ensuring worker is running..."
docker-compose up -d worker

echo "Initialisiere DB..."
curl -s http://localhost:8000/db_setup.php > /dev/null

echo "Reset counter..."
docker-compose exec -T mysql mysql -uroot -psecret concurrency -e "UPDATE counter SET count = 0 WHERE id = 1;"
docker-compose exec -T redis redis-cli del counter_queue

echo "Starte Session 5 Demo (Command Queue)..."
echo "Sending 100 requests to push commands..."
ab -n 100 -c 10 http://localhost:8000/counter_queue_push.php

echo "Warte kurz bis Worker fertig ist..."
sleep 2

echo -e "\nErwartetes Ergebnis: 100"
echo -n "Tatsächliches Ergebnis (DB): "
docker-compose exec -T mysql mysql -uroot -psecret concurrency -s -N -e "SELECT count FROM counter WHERE id = 1;"
