
## Cel
Celem modułu jest **wysyłanie eventów ze sklepu PrestaShop** (koszyk, zamówienia, klienci itd.) **metodą HTTP POST** na **zewnętrzny endpoint**, który jest konfigurowany w panelu administracyjnym modułu.

Moduł ma być:
- maksymalnie prosty w rozwoju i utrzymaniu,
- szybki do uruchomienia w środowisku developerskim,
- łatwy do rozszerzania o kolejne eventy i pola danych,
- oparty o **tokenową autoryzację** (bez OAuth i skomplikowanych flow).

---

## Główne założenia projektowe

- jeden endpoint HTTP (POST)
- jeden token autoryzacyjny
- jeden wspólny format payloadu (JSON)
- eventy włączane/wyłączane checkboxami
- dane wysyłane selektywnie (checkboxy)
- minimalna logika, czytelny kod, szybki development

---

## Autoryzacja

Moduł używa **prostej autoryzacji tokenowej**.

### Sposób autoryzacji
- token ustawiany w panelu modułu
- token wysyłany w nagłówku HTTP:

```http
Authorization: Bearer <TOKEN>
````

### Założenia

* brak wygasania tokenu po stronie modułu
* brak refresh tokenów
* walidacja tokenu po stronie odbiorcy webhooka
* rozwiązanie przyjazne dla developmentu i testów

---

## Panel administracyjny modułu (Back Office)

Moduł posiada **prosty panel konfiguracyjny** dostępny w Back Office PrestaShop.

### Sekcja 1: Konfiguracja połączenia

* **Endpoint URL**
  Adres, na który wysyłane są eventy (POST), np.
  `https://api.example.com/webhook/prestashop`
* **Token**
  Sekret używany do autoryzacji
* **Tryb debug**
  Włącza logowanie requestów i odpowiedzi (status HTTP, błędy)

---

### Sekcja 2: Wybór eventów do wysyłki

Administrator może wybrać, **które eventy mają być wysyłane**.

Przykładowe eventy:

* `cart.updated`
* `cart.item_added`
* `order.created`
* `order.status_changed`
* `customer.created`

Każdy event:

* osobny checkbox (ON / OFF)
* brak eventu = brak wysyłki
* łatwe dodawanie nowych eventów w przyszłości

---

### Sekcja 3: Wybór danych do wysyłki

Administrator może określić, **jakie sekcje danych** mają być dołączane do payloadu.

Przykładowe flagi danych:

* `context` – kontekst sklepu (język, waluta, shop_id)
* `cart` – dane koszyka
* `items` – produkty / pozycje
* `order` – dane zamówienia
* `customer` – dane klienta
* `address` – dane adresowe (opcjonalnie)

Dzięki temu:

* można wysyłać tylko minimalne dane (np. same ID),
* można ograniczyć dane wrażliwe (RODO),
* payload jest dopasowany do potrzeb odbiorcy.

---

## Logika działania modułu

1. W sklepie występuje zdarzenie (np. dodanie produktu do koszyka).
2. PrestaShop wywołuje odpowiedni hook.
3. Moduł:

   * mapuje hook → nazwę eventu,
   * sprawdza, czy event jest włączony w konfiguracji,
   * sprawdza, jakie dane są włączone.
4. Moduł buduje payload JSON.
5. Moduł wysyła `POST` na endpoint z tokenem w nagłówku.
6. W trybie debug zapisywany jest log (sukces / błąd).

---

## Format payloadu (JSON)

### Wspólny format dla wszystkich eventów

```json
{
  "event": "order.created",
  "occurred_at": "2026-01-03T12:34:56+01:00",
  "data": {
    "...": "dane zależne od eventu i konfiguracji"
  }
}
```

### Przykład: `cart.item_added`

```json
{
  "event": "cart.item_added",
  "occurred_at": "2026-01-03T12:10:00+01:00",
  "data": {
    "cart": { "id": 123, "total": 199.99 },
    "item": { "product_id": 456, "qty": 1 },
    "customer": { "id": 789 }
  }
}
```

### Przykład: `order.created`

```json
{
  "event": "order.created",
  "occurred_at": "2026-01-03T12:40:00+01:00",
  "data": {
    "order": { "id": 1001, "total_paid": 299.99, "currency": "PLN" },
    "items": [
      { "product_id": 456, "qty": 1, "price": 299.99 }
    ],
    "customer": { "id": 789 }
  }
}
```

---

## Wymagania niefunkcjonalne

* moduł działa na PrestaShop 8.x
* brak zauważalnego wpływu na wydajność checkoutu
* brak zewnętrznych zależności
* łatwe debugowanie w Dockerze
* kod prosty do modyfikacji i rozbudowy

---

## Kierunki dalszego rozwoju (opcjonalnie)

* retry / backoff przy błędach sieci
* kolejkowanie eventów (async)
* podpis HMAC payloadu
* anonimizacja danych (RODO)
* eksport / import konfiguracji
* mapowanie eventów do standardów (GA4, Segment)

---

## Podsumowanie

Moduł ma być:

* prosty,
* przewidywalny,
* łatwy w rozwijaniu,
* idealny pod integracje webhookowe.

Bez nadmiarowej abstrakcji: **hook → event → POST JSON**.
