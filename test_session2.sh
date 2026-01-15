#!/bin/bash
echo "Initialisiere DB..."
curl -s http://localhost:8000/db_setup.php > /dev/null
# Reset counter
docker-compose exec -T mysql mysql -uroot -psecret concurrency -e "UPDATE counter SET count = 0 WHERE id = 1;"

echo "Starte Session 2 Demo (Pessimistic Locking)..."
ab -n 100 -c 10 http://localhost:8000/counter_pessimistic.php

echo -e "\nErwartetes Ergebnis: 100"
echo -n "Tatsächliches Ergebnis (DB): "
docker-compose exec -T mysql mysql -uroot -psecret concurrency -s -N -e "SELECT count FROM counter WHERE id = 1;"
