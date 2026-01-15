#!/bin/bash
echo "Initializing database..."
docker-compose exec -T php php /code/db_setup.php

echo "Ensuring event worker is running..."
docker-compose up -d event_worker

echo "Reset counter and events..."
docker-compose exec -T mysql mysql -uroot -psecret concurrency -e "UPDATE counter SET count = 0 WHERE id = 1;"
docker-compose exec -T mysql mysql -uroot -psecret concurrency -e "DELETE FROM events;"

echo "Starte Session 6 Demo (Event Store)..."
echo "Sending 100 requests to record events..."
ab -n 100 -c 10 http://localhost:8000/counter_event_push.php

echo "Warte kurz bis Worker fertig ist..."
# Give it some time to process 100 events, 0.1s sleep in worker means it might take ~10 seconds if processed sequentially one by one without new events,
# but here it processes as fast as it can when events are available.
sleep 5

echo -e "\nErwartetes Ergebnis: 100"
echo -n "Tatsächliches Ergebnis (DB): "
docker-compose exec -T mysql mysql -uroot -psecret concurrency -s -N -e "SELECT count FROM counter WHERE id = 1;"
echo -n "Anzahl verarbeiteter Events: "
docker-compose exec -T mysql mysql -uroot -psecret concurrency -s -N -e "SELECT COUNT(*) FROM events WHERE processed = 1;"
