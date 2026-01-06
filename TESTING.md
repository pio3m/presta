# Instrukcja testowania webhooków

## 📋 SZYBKI START - Testowanie krok po kroku

### **KROK 1: Uruchom webhook receiver**

```bash
docker-compose up -d --build webhook-receiver
```

Sprawdź czy działa:
```bash
docker-compose ps
```

### **KROK 2: Przetestuj czy webhook receiver odpowiada**

```bash
curl http://localhost:9000/health
```

Powinno zwrócić: `{"status":"ok"}`

### **KROK 3: Obserwuj logi webhook receivera** (zostaw w osobnym terminalu)

```bash
docker-compose logs -f webhook-receiver
```

### **KROK 4: Zmień konfigurację modułu w PrestaShop**

1. Otwórz: http://localhost:8080/admin4577
2. Zaloguj się
3. Idź do: **Modules > Module Manager**
4. Znajdź **"Webhook Events Module"** i kliknij **Configure**
5. **WAŻNE - Zmień URL na**: `http://webhook-receiver:9000`
   - ❌ **NIE**: `http://localhost:9000`
   - ✅ **TAK**: `http://webhook-receiver:9000`
6. Włącz **Tryb debug**
7. Kliknij **Zapisz**

### **KROK 5: Wywołaj event w sklepie**

Otwórz sklep: http://localhost:8080
- Dodaj produkt do koszyka
- Zarejestruj konto
- Złóż zamówienie

### **KROK 6: Sprawdź czy webhook przyszedł**

Patrz na terminal z logami (Krok 3) - powinny pojawić się:
```
=== WEBHOOK RECEIVED ===
Timestamp: ...
Body: {"event":"cart.updated", ...}
```

### **KROK 7: Sprawdź logi modułu**

```bash
docker exec prestashop cat /var/www/html/modules/mymodule/logs/webhook.log
```

Powinno być "sukces" zamiast "błąd"!

---

### 🧪 BONUS: Szybki test skryptem

```bash
./test-webhook.sh
```

Ten skrypt automatycznie przetestuje wszystkie połączenia.

---

## Problem który został rozwiązany

**Błąd**: "Failed to connect to localhost port 9000: Couldn't connect to server"

**Przyczyna**: W kontenerze Docker `localhost` oznacza SAM KONTENER, nie host ani inne kontenery.

**Rozwiązanie**: Używamy nazwy serwisu Docker jako hostname: `webhook-receiver:9000`

## Krok 1: Uruchomienie środowiska

```bash
# Zatrzymaj wszystkie kontenery
docker-compose down

# Zbuduj i uruchom wszystko od nowa
docker-compose up -d --build

# Sprawdź czy wszystko działa
docker-compose ps
```

Powinny działać 3 kontenery:
- `prestashop` (port 8080)
- `some-mysql` (port 3306)
- `webhook-receiver` (port 9000)

## Krok 2: Konfiguracja modułu w PrestaShop

1. Otwórz PrestaShop: http://localhost:8080
2. Zaloguj się do panelu administracyjnego: http://localhost:8080/admin4577
3. Przejdź do: **Modules > Module Manager**
4. Znajdź moduł **"Webhook Events Module"** i kliknij **Configure**
5. Ustaw:
   - **Endpoint URL**: `http://webhook-receiver:9000`
   - **Token**: `test-token` (lub zostaw puste)
   - **Tryb debug**: **Włączony** ✓
   - Włącz eventy które chcesz testować
6. Kliknij **Zapisz**

## Krok 3: Testowanie webhooków

### Test 1: Sprawdź czy webhook receiver działa

```bash
# Health check
curl http://localhost:9000/health

# Powinno zwrócić: {"status":"ok"}
```

### Test 2: Wyślij testowy webhook z hosta

```bash
curl -X POST http://localhost:9000 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer test-token" \
  -d '{
    "event": "test.manual",
    "occurred_at": "2026-01-06T10:00:00+01:00",
    "data": {
      "message": "Test z curl"
    }
  }'
```

### Test 3: Obserwuj logi webhook receivera

```bash
# W osobnym terminalu
docker-compose logs -f webhook-receiver
```

### Test 4: Wywołaj eventy z PrestaShop

1. **Event: cart.updated / cart.item_added**
   - Otwórz sklep: http://localhost:8080
   - Dodaj produkt do koszyka
   - Sprawdź logi webhook receivera

2. **Event: customer.created**
   - Zarejestruj nowe konto klienta
   - Sprawdź logi

3. **Event: order.created**
   - Złóż zamówienie
   - Sprawdź logi

### Test 5: Sprawdź logi modułu

```bash
# Logi modułu (z kontenera PrestaShop)
cat modules/mymodule/logs/webhook.log

# Lub z hosta
docker exec prestashop cat /var/www/html/modules/mymodule/logs/webhook.log
```

## Debugowanie

### Problem: Webhook receiver nie odpowiada

```bash
# Sprawdź status kontenerów
docker-compose ps

# Sprawdź logi webhook receivera
docker-compose logs webhook-receiver

# Zrestartuj webhook receiver
docker-compose restart webhook-receiver
```

### Problem: PrestaShop nadal nie może połączyć się

```bash
# Sprawdź czy kontenery są w tej samej sieci
docker network inspect prestahop_prestashop_network

# Test połączenia z wnętrza kontenera PrestaShop
docker exec prestashop curl -v http://webhook-receiver:9000/health
```

### Problem: "curl: command not found" w kontenerze

```bash
# Zainstaluj curl w kontenerze PrestaShop
docker exec -u root prestashop apt-get update
docker exec -u root prestashop apt-get install -y curl

# Lub użyj PHP
docker exec prestashop php -r "echo file_get_contents('http://webhook-receiver:9000/health');"
```

## Testowanie z zewnętrznych narzędzi

### Postman / Insomnia

- **URL**: `http://localhost:9000`
- **Method**: POST
- **Headers**:
  - `Content-Type: application/json`
  - `Authorization: Bearer test-token`
- **Body** (JSON):
```json
{
  "event": "test.postman",
  "occurred_at": "2026-01-06T10:00:00+01:00",
  "data": {
    "test": true
  }
}
```

### Webhook.site (testowanie z internetu)

1. Otwórz https://webhook.site
2. Skopiuj unikalny URL
3. W konfiguracji modułu PrestaShop ustaw ten URL jako Endpoint
4. Wykonaj akcje w sklepie
5. Zobacz webhoki na webhook.site

**UWAGA**: To zadziała tylko jeśli Twój PrestaShop ma dostęp do internetu!

## Monitoring na żywo

```bash
# Terminal 1: Logi webhook receivera
docker-compose logs -f webhook-receiver

# Terminal 2: Logi PrestaShop (opcjonalnie)
docker-compose logs -f prestashop

# Terminal 3: Logi modułu
tail -f modules/mymodule/logs/webhook.log
```

## Architektura sieci Docker

```
Host (Twój komputer)
  └─ localhost:8080 → prestashop:80
  └─ localhost:9000 → webhook-receiver:9000

Docker Network (prestashop_network)
  ├─ prestashop (kontener)
  │   └─ może łączyć się z: webhook-receiver:9000
  ├─ webhook-receiver (kontener)
  │   └─ nasłuchuje na: 0.0.0.0:9000
  └─ some-mysql (kontener)
      └─ nasłuchuje na: 3306
```

## Najczęstsze błędy

| Błąd | Rozwiązanie |
|------|-------------|
| `localhost:9000` nie działa | Użyj `webhook-receiver:9000` w konfiguracji modułu |
| Kontenery nie widzą się | Sprawdź czy są w tej samej sieci Docker |
| Port już zajęty | Zmień port w docker-compose.yml (np. `9001:9000`) |
| Brak logów | Włącz "Tryb debug" w konfiguracji modułu |

## Czyszczenie środowiska

```bash
# Zatrzymaj wszystko
docker-compose down

# Usuń volumes (UWAGA: usunie dane z bazy!)
docker-compose down -v

# Usuń obrazy
docker-compose down --rmi all
```
