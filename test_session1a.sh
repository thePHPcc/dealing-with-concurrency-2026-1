#!/bin/bash
echo 0 > code/counter.txt
rm code/log.txt
echo "Starte Session 1 Demo (Naiver Counter)..."
for i in `seq 1 100` ; do curl "localhost:8000/counter2.php?count=$i" & done

wait

echo -e "\nErwartetes Ergebnis: 100"
echo -n "Tatsächliches Ergebnis: "
cat code/counter.txt

echo -e "Sequence:"
cat code/log.txt
echo
