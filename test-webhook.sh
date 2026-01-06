#!/bin/bash

echo "=== Test webhooków PrestaShop ==="
echo ""

# Kolory
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test 1: Health check
echo -e "${YELLOW}Test 1: Health check webhook receivera${NC}"
response=$(curl -s http://localhost:9000/health)
if [[ $response == *"ok"* ]]; then
    echo -e "${GREEN}✓ Webhook receiver działa${NC}"
    echo "  Response: $response"
else
    echo -e "${RED}✗ Webhook receiver nie odpowiada${NC}"
    echo "  Response: $response"
    exit 1
fi
echo ""

# Test 2: Test webhook z hosta
echo -e "${YELLOW}Test 2: Wysłanie testowego webhoka z hosta${NC}"
response=$(curl -s -X POST http://localhost:9000 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer test-token" \
  -d '{
    "event": "test.manual",
    "occurred_at": "'$(date -Iseconds)'",
    "data": {
      "message": "Test z skryptu test-webhook.sh"
    }
  }')

if [[ $response == *"success"* ]]; then
    echo -e "${GREEN}✓ Webhook wysłany pomyślnie${NC}"
    echo "  Response: $response"
else
    echo -e "${RED}✗ Błąd wysyłania webhoka${NC}"
    echo "  Response: $response"
fi
echo ""

# Test 3: Test z wnętrza kontenera PrestaShop
echo -e "${YELLOW}Test 3: Test połączenia z kontenera PrestaShop${NC}"
docker exec prestashop php -r "
\$ch = curl_init('http://webhook-receiver:9000/health');
curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt(\$ch, CURLOPT_TIMEOUT, 5);
\$response = curl_exec(\$ch);
\$httpCode = curl_getinfo(\$ch, CURLINFO_HTTP_CODE);
\$error = curl_error(\$ch);
curl_close(\$ch);

if (\$httpCode == 200) {
    echo \"✓ PrestaShop kontener może połączyć się z webhook-receiver\n\";
    echo \"  Response: \" . \$response . \"\n\";
} else {
    echo \"✗ PrestaShop kontener NIE MOŻE połączyć się z webhook-receiver\n\";
    echo \"  HTTP Code: \" . \$httpCode . \"\n\";
    echo \"  Error: \" . \$error . \"\n\";
    exit(1);
}
" 2>&1

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Połączenie z kontenera działa${NC}"
else
    echo -e "${RED}✗ Błąd połączenia z kontenera${NC}"
fi
echo ""

# Podsumowanie
echo -e "${GREEN}=== Wszystkie testy zakończone ===${NC}"
echo ""
echo "Następne kroki:"
echo "1. Otwórz PrestaShop: http://localhost:8080/admin4577"
echo "2. Skonfiguruj moduł z URL: http://webhook-receiver:9000"
echo "3. Włącz tryb debug"
echo "4. Obserwuj logi: docker-compose logs -f webhook-receiver"
echo "5. Wykonaj akcje w sklepie (dodaj produkt do koszyka, itp.)"
echo ""
echo "Logi modułu: docker exec prestashop cat /var/www/html/modules/mymodule/logs/webhook.log"
