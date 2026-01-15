#!/bin/bash
echo 0 > code/counter.txt
rm code/log.txt
echo "Starte Session 1 Demo (Naiver Counter)..."
ab -n 100 -c 10 http://localhost:8000/counter.php
echo -e "\nErwartetes Ergebnis: 100"
echo -n "Tatsächliches Ergebnis: "
cat code/counter.txt
echo
