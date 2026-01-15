#!/bin/bash

echo "Initialisiere Datenbank..."
docker-compose exec -T php php db_setup.php

echo "Setze Shards auf 0 zurück..."
docker-compose exec -T php php -r '
    $pdo = new PDO("mysql:host=mysql;dbname=concurrency", "root", "secret");
    $pdo->exec("UPDATE sharded_counter SET count = 0, version = 0");
'

echo "Starte Session 7 Demo (Sharding + Optimistic Locking)..."
echo "Führe 100 Requests mit Concurrent-Faktor 10 aus..."

ab -n 100 -c 10 http://localhost:8000/counter_sharding.php

echo -e "\nErgebnisse:"
docker-compose exec -T php php counter_sharding_total.php
