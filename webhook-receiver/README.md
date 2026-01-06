# Webhook Receiver - Testowy serwer do odbierania webhooków

Prosty serwer Node.js do testowania webhooków z PrestaShop.

## Uruchomienie

Serwer uruchamia się automatycznie przez docker-compose:

```bash
docker-compose up -d webhook-receiver
```

## Sprawdzenie czy działa

```bash
# Test health check
curl http://localhost:9000/health

# Test webhook z poziomu hosta
curl -X POST http://localhost:9000 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer test-token" \
  -d '{"event":"test","data":{"message":"hello"}}'
```

## Oglądanie logów

```bash
docker-compose logs -f webhook-receiver
```

## Konfiguracja w PrestaShop

W panelu administracyjnym PrestaShop, w konfiguracji modułu "mymodule", ustaw:

**Endpoint URL**: `http://webhook-receiver:9000`

**Token**: możesz ustawić dowolny lub zostawić puste

**Tryb debug**: Włączony (aby widzieć logi w modules/mymodule/logs/webhook.log)

## Jak to działa

- PrestaShop kontener łączy się z `webhook-receiver:9000` (nazwa kontenera w sieci Docker)
- Webhook receiver nasłuchuje na porcie 9000
- Wszystkie otrzymane webhoki są logowane w konsoli
- Port 9000 jest też dostępny z hosta na `localhost:9000` dla testów ręcznych
