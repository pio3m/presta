# PrestaShop Webhook Module - Quick Start

## Struktura projektu

```
.
├── docker-compose.yml          # Docker (MySQL 5.6 + PrestaShop)
├── modules/
│   └── mymodule/              # Moduł webhook
│       ├── mymodule.php       # Główny plik modułu
│       ├── config.xml         # Konfiguracja
│       ├── classes/
│       │   └── WebhookSender.php  # Wysyłanie webhooków
│       └── controllers/
│           └── front/
│               └── api.php    # Testowy endpoint
├── test_webhook.php           # Test - wysyła webhooki
├── test_receiver.php          # Test - odbiera webhooki
├── TEST_INSTRUCTIONS.md       # Szczegółowe instrukcje
└── readme.md                  # Specyfikacja funkcjonalna
```

## Uruchomienie (3 kroki)

### 1. Start Docker

```bash
docker-compose up -d
```

PrestaShop: http://localhost:8080
- admin@prestashop.com / Admin123

### 2. Instalacja modułu

1. Panel admin → Moduły → Module Manager
2. Znajdź "Webhook Events Module"
3. Instaluj → Konfiguruj

### 3. Konfiguracja

- **Endpoint URL**: `http://localhost:9000`
- **Token**: `test_token_123456`
- **Debug**: ON
- Zaznacz eventy i dane
- Zapisz

## Test w 2 terminalach

**Terminal 1** - odbiornik:
```bash
php -S localhost:9000 test_receiver.php
```

**Terminal 2** - test:
```bash
php test_webhook.php
```

Zobacz wyniki w terminalu 1.

## Wspierane eventy

- `cart.updated` - aktualizacja koszyka
- `cart.item_added` - dodanie produktu
- `order.created` - nowe zamówienie
- `order.status_changed` - zmiana statusu
- `customer.created` - nowy klient

## Format JSON

```json
{
  "event": "order.created",
  "occurred_at": "2026-01-03T12:34:56+01:00",
  "data": {
    "order": { "id": 1001, "total_paid": 299.99 },
    "customer": { "id": 789, "email": "test@example.com" }
  }
}
```

## Autoryzacja

```
Authorization: Bearer <TOKEN>
```

## Logi debug

```bash
docker exec -it <container> cat /var/www/html/modules/mymodule/logs/webhook.log
```

## Dodawanie nowego eventu

1. Zarejestruj hook w `mymodule.php`:
```php
$this->registerHook('actionNewHook')
```

2. Dodaj obsługę:
```php
public function hookActionNewHook($params) {
    if (!Configuration::get('MYMODULE_EVENT_NEW')) return;
    $sender = new WebhookSender($this);
    $sender->sendEvent('new.event', $params);
}
```

## Szczegóły

Zobacz [TEST_INSTRUCTIONS.md](TEST_INSTRUCTIONS.md) dla pełnej dokumentacji testowania.

Zobacz [readme.md](readme.md) dla specyfikacji funkcjonalnej.

## Technologie

- PrestaShop 1.7+
- PHP 7.2+
- MySQL 5.6
- Docker Compose
