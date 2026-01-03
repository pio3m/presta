# Instrukcje testowania modułu webhook

## Przygotowanie środowiska

### 1. Uruchomienie środowiska Docker

```bash
docker-compose up -d
```

Dostęp do PrestaShop: http://localhost:8080
- Email: admin@prestashop.com
- Hasło: Admin123

### 2. Instalacja modułu

1. Zaloguj się do panelu administracyjnego PrestaShop
2. Przejdź do: **Moduły > Module Manager**
3. Znajdź moduł "Webhook Events Module"
4. Kliknij **Instaluj**

### 3. Konfiguracja modułu

1. Po instalacji kliknij **Konfiguruj**
2. Wypełnij pola:
   - **Endpoint URL**: `http://localhost:9000` (lub inny endpoint testowy)
   - **Token**: `test_token_123456`
   - **Tryb debug**: Włączony
3. Zaznacz eventy które chcesz wysyłać
4. Zaznacz dane które chcesz dołączać
5. Kliknij **Zapisz**

## Testowanie

### Opcja 1: Test z prostym odbiornikiem (zalecaneoption)

#### Krok 1: Uruchom odbiornik webhooka

W osobnym terminalu:

```bash
php -S localhost:9000 test_receiver.php
```

Odbiornik będzie działał na http://localhost:9000 i wyświetlał wszystkie otrzymane requesty.

#### Krok 2: Wyślij testowe webhooki

W drugim terminalu:

```bash
php test_webhook.php
```

To wyśle 3 przykładowe eventy:
- `cart.item_added` - dodanie produktu do koszyka
- `order.created` - utworzenie zamówienia
- `customer.created` - utworzenie konta klienta

#### Krok 3: Zobacz wyniki

W terminalu z odbiornikiem zobaczysz szczegóły otrzymanych requestów.

### Opcja 2: Test z prawdziwym sklepem

1. Zaloguj się do sklepu (http://localhost:8080)
2. Dodaj produkt do koszyka
3. Zarejestruj nowe konto
4. Złóż zamówienie
5. Zmień status zamówienia w panelu admin

Wszystkie te akcje wyślą odpowiednie webhooki na skonfigurowany endpoint.

### Opcja 3: Test z zewnętrznym narzędziem

Użyj narzędzi takich jak:
- **webhook.site** - darmowy odbiornik webhooka
- **RequestBin** - podobne narzędzie
- **ngrok** - tunelowanie lokalnego serwera

Przykład z webhook.site:
1. Przejdź na https://webhook.site
2. Skopiuj wygenerowany URL
3. Wklej jako **Endpoint URL** w konfiguracji modułu
4. Wykonaj akcje w sklepie
5. Zobacz webhooki na webhook.site

## Debugowanie

### Logi modułu

Jeśli tryb debug jest włączony, logi są zapisywane w:

```
modules/mymodule/logs/webhook.log
```

Możesz je podejrzeć:

```bash
docker exec -it prestahop_plugin-prestashop-1 cat /var/www/html/modules/mymodule/logs/webhook.log
```

### Logi odbiornika testowego

Logi odbiornika są zapisywane w:

```
webhook_received.log
```

Możesz je podejrzeć:

```bash
cat webhook_received.log | jq
```

## Przykładowe payloady

### Event: cart.item_added

```json
{
  "event": "cart.item_added",
  "occurred_at": "2026-01-03T12:10:00+01:00",
  "data": {
    "context": {
      "shop_id": 1,
      "language": "pl",
      "currency": "PLN"
    },
    "cart": {
      "id": 123,
      "total": 199.99,
      "products_count": 1
    },
    "item": {
      "product_id": 456,
      "name": "Test Product",
      "price": 199.99
    },
    "customer": {
      "id": 789,
      "email": "test@example.com"
    }
  }
}
```

### Event: order.created

```json
{
  "event": "order.created",
  "occurred_at": "2026-01-03T12:40:00+01:00",
  "data": {
    "context": {
      "shop_id": 1,
      "language": "pl",
      "currency": "PLN"
    },
    "order": {
      "id": 1001,
      "reference": "TEST001",
      "total_paid": 299.99,
      "currency": 1,
      "payment": "Przelew"
    },
    "items": [
      {
        "product_id": 456,
        "product_name": "Test Product",
        "quantity": 1,
        "price": 299.99
      }
    ],
    "customer": {
      "id": 789,
      "email": "test@example.com",
      "firstname": "Jan",
      "lastname": "Kowalski"
    }
  }
}
```

## Rozwiązywanie problemów

### Webhook nie jest wysyłany

1. Sprawdź czy event jest włączony w konfiguracji
2. Sprawdź logi w trybie debug
3. Sprawdź czy endpoint URL jest poprawny
4. Sprawdź czy moduł jest zainstalowany i aktywny

### Błąd 401 Unauthorized

- Sprawdź czy token jest poprawnie skonfigurowany
- Upewnij się że nagłówek `Authorization: Bearer <TOKEN>` jest wysyłany

### Błąd połączenia

- Sprawdź czy odbiornik działa
- Sprawdź czy port jest otwarty
- Jeśli używasz Dockera, sprawdź konfigurację sieci

### Brak danych w payloadzie

- Sprawdź które dane są włączone w sekcji "Dane do wysyłki"
- Włącz odpowiednie checkboxy

## Dalsze kroki

Po pomyślnym teście możesz:
1. Skonfigurować prawdziwy endpoint produkcyjny
2. Dodać więcej eventów (edytując `mymodule.php`)
3. Dostosować format payloadu (edytując `WebhookSender.php`)
4. Dodać retry logic dla błędów sieciowych
5. Zaimplementować kolejkowanie dla wydajności
