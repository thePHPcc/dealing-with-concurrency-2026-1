#!/bin/bash
echo "Initialisiere DB..."
curl -s http://localhost:8000/db_setup.php > /dev/null

echo "Reset counter..."
docker-compose exec -T mysql mysql -uroot -psecret concurrency -e "UPDATE counter SET count = 0 WHERE id = 1;" 2>/dev/null
docker-compose exec -T redis redis-cli del lock:counter > /dev/null

echo "Starte Session 3 Demo (Redis Locks)..."
ab -n 100 -c 10 http://localhost:8000/counter_redis.php

echo -e "\nErgebnis in der DB muss 100 sein, da wir auf den Lock warten."
echo -n "Tatsächliches Ergebnis (DB): "
docker-compose exec -T mysql mysql -uroot -psecret concurrency -s -N -e "SELECT count FROM counter WHERE id = 1;" 2>/dev/null
