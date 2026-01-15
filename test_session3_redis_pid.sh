#!/bin/bash
echo "Initialisiere DB..."
curl -s http://localhost:8000/db_setup.php > /dev/null

echo "Reset counter..."
docker-compose exec -T mysql mysql -uroot -psecret concurrency -e "UPDATE counter SET count = 0 WHERE id = 1;" 2>/dev/null

rm -f code/log.txt

echo "Szenario: Ein toter Prozess hat den Lock hinterlassen..."
# Wir setzen einen Lock mit einer PID, die es (wahrscheinlich) nicht gibt
# Im Container php (CLI server) sind PIDs meist niedrig, 999999 existiert sicher nicht.
docker-compose exec -T redis redis-cli set lock:counter 999999 EX 600 > /dev/null

echo "Starte Test mit PID-Check (Ergebnis sollte sofort kommen, ohne 60s zu warten)..."
START=$(date +%s)
# Wir führen nur einen Request aus, der den toten Lock finden sollte
RESULT=$(curl -s http://localhost:8000/counter_redis_pid.php)
END=$(date +%s)
DURATION=$((END - START))

echo "Ergebnis vom Script: $RESULT"
echo "Dauer: $DURATION Sekunden"

if [ "$DURATION" -lt 5 ]; then
    echo "SUCCESS: Lock wurde schnell übernommen (Dauer < 5s)."
else
    echo "FAILURE: Lock wurde nicht schnell genug übernommen (Dauer >= 5s)."
fi

echo -n "Tatsächlicher Zählerstand in DB: "
docker-compose exec -T mysql mysql -uroot -psecret concurrency -s -N -e "SELECT count FROM counter WHERE id = 1;" 2>/dev/null

# Jetzt noch ein Lasttest um sicherzugehen, dass es generell noch funktioniert
echo -e "\nReset counter for load test..."
docker-compose exec -T mysql mysql -uroot -psecret concurrency -e "UPDATE counter SET count = 0 WHERE id = 1;" 2>/dev/null

echo "Starte Lasttest (100 Requests)..."
docker-compose exec -T redis redis-cli del lock:counter > /dev/null
ab -n 100 -c 10 http://localhost:8000/counter_redis_pid.php > /dev/null

echo -n "Finaler Zählerstand in DB: "
docker-compose exec -T mysql mysql -uroot -psecret concurrency -s -N -e "SELECT count FROM counter WHERE id = 1;" 2>/dev/null
