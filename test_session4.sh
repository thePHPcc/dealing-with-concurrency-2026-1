#!/bin/bash
echo "Reset counter..."
docker-compose exec -T mysql mysql -uroot -psecret concurrency -e "UPDATE counter SET count = 0, version = 0 WHERE id = 1;"

echo "Starte Session 4 Demo (Optimistic Locking)..."
ab -n 100 -c 10 http://localhost:8000/counter_optimistic.php

echo -e "\nErwartetes Ergebnis: 100 (oder leicht darunter, falls Retries ausgeschöpft sind)"
echo -n "Tatsächliches Ergebnis (DB): "
docker-compose exec -T mysql mysql -uroot -psecret concurrency -s -N -e "SELECT count FROM counter WHERE id = 1;"
