#!/bin/bash

echo "=== Sprawdzanie statusu środowiska ==="
echo

echo "1. Status kontenerów Docker:"
docker compose ps
echo

echo "2. Sprawdzanie czy MySQL jest gotowy:"
docker exec prestahop_plugin-db-1 mysqladmin ping -h localhost -u root -proot 2>/dev/null
if [ $? -eq 0 ]; then
    echo "✓ MySQL działa"
else
    echo "✗ MySQL nie odpowiada"
fi
echo

echo "3. Sprawdzanie czy PrestaShop jest dostępny:"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8080)
if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "302" ]; then
    echo "✓ PrestaShop działa (HTTP $HTTP_CODE)"
    echo "  Frontend: http://localhost:8080"
    echo "  Admin: http://localhost:8080/admin"
else
    echo "✗ PrestaShop nie jest jeszcze gotowy (HTTP $HTTP_CODE)"
    echo "  Instalacja prawdopodobnie trwa..."
fi
echo

echo "4. Ostatnie 10 linii logów PrestaShop:"
docker logs prestahop_plugin-prestashop-1 --tail 10
echo

echo "=== Koniec sprawdzania ==="
echo
echo "Aby zobaczyć pełne logi:"
echo "  docker logs prestahop_plugin-prestashop-1 -f"
