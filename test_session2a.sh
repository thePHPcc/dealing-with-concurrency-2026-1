#!/bin/bash
echo "Initialisiere DB..."
curl -s http://localhost:8000/db_setup.php > /dev/null
# Reset counter
docker-compose exec -T mysql mysql -uroot -psecret concurrency -e "UPDATE counter SET count = 0 WHERE id = 1;" 2>/dev/null
rm -f code/log.txt

for i in `seq 1 100` ; do curl "http://localhost:8000/counter_pessimistic2.php?count=$i" & done

wait

echo -e "\nErwartetes Ergebnis: 100"
echo -n "Tatsächliches Ergebnis (DB): "
docker-compose exec -T mysql mysql -uroot -psecret concurrency -s -N -e "SELECT count FROM counter WHERE id = 1;" 2>/dev/null
