# Finalizacja Setupu PrestaShop

## Status

✅ Docker działa (MySQL 5.7 + PrestaShop)
✅ .gitignore zaktualizowany
✅ docker-compose.yml zmieniony na lokalny folder
⏳ Kopiowanie plików PrestaShop...

## Krok 1: Poczekaj na skopiowanie plików

Sprawdź czy kopiowanie się zakończyło:

```bash
ps aux | grep "docker cp" | grep -v grep
```

Jeśli nie ma wyniku = kopiowanie zakończone ✓

Lub sprawdź rozmiar:
```bash
du -sh prestashop_files/
```

Powinno być ~200-250 MB.

## Krok 2: Uruchom skrypt post-instalacyjny

```bash
chmod +x post_install.sh
bash post_install.sh
```

Ten skrypt:
- ✓ Usuwa folder `install/`
- ✓ Zmienia nazwę `admin/` → `admin<random>/`
- ✓ Ustawia odpowiednie uprawnienia
- ✓ Zapisuje nazwę admin w `.admin_folder_name`

## Krok 3: Zrestartuj Docker

```bash
# Zatrzymaj
docker compose down

# Uruchom z nowym volume
docker compose up -d
```

## Krok 4: Zaloguj się do panelu admin

Sprawdź nazwę folderu admin:
```bash
cat .admin_folder_name
```

Panel admin będzie pod:
```
http://localhost:8080/<nazwa_z_pliku>
```

Login:
- Email: `admin@prestashop.com`
- Hasło: `Admin123`

## Krok 5: Zainstaluj moduł

1. W panelu admin → **Moduły** → **Module Manager**
2. Znajdź "Webhook Events Module"
3. Kliknij **Instaluj**
4. Kliknij **Konfiguruj**
5. Ustaw:
   - Endpoint URL: `http://localhost:9000`
   - Token: `test_token_123456`
   - Debug: ON
   - Zaznacz eventy i dane
6. Zapisz

## Krok 6: Testuj webhooki

Terminal 1 - odbiornik:
```bash
php -S localhost:9000 test_receiver.php
```

Terminal 2 - wysyłka testowa:
```bash
php test_webhook.php
```

Powinieneś zobaczyć 3 webhooki w terminalu odbiornika!

## Co znajduje się w .gitignore

```
prestashop_files/     # Cała instalacja PrestaShop
.admin_folder_name    # Nazwa folderu admin
*.log                 # Logi
db_data/              # Volume MySQL
```

Czyli repozytorium zawiera tylko:
- Moduł (`modules/mymodule/`)
- Konfigurację Docker
- Skrypty testowe
- Dokumentację

## Troubleshooting

### Kopiowanie trwa bardzo długo (>5 minut)

Możesz zatrzymać i pobrać pliki ręcznie:
```bash
# Zatrzymaj kopiowanie
killall docker

# Alternatywa: skopiuj tylko niezbędne pliki
docker compose down
docker compose up -d
docker exec prestahop_plugin-prestashop-1 rm -rf /var/www/html/install
docker exec prestahop_plugin-prestashop-1 mv /var/www/html/admin /var/www/html/admin123
```

Wtedy możesz ominąć `prestashop_files/` i pracować bezpośrednio w kontenerze.

### Błąd "Cannot access prestashop_files"

```bash
# Ustaw uprawnienia
chmod -R 755 prestashop_files/
```

### Moduł nie jest widoczny po restarcie

```bash
# Upewnij się że moduł jest w prestashop_files
ls -la prestashop_files/modules/mymodule/

# Jeśli nie ma, skopiuj
cp -r modules/mymodule prestashop_files/modules/
```

## Szybka ścieżka (jeśli kopiowanie nie działa)

1. Zatrzymaj Docker i usuń prestashop_files:
```bash
docker compose down
rm -rf prestashop_files
```

2. Przywróć stary docker-compose.yml (volume):
```yaml
volumes:
  - ps_data:/var/www/html
  - ./modules/mymodule:/var/www/html/modules/mymodule
```

3. Usuń install i zmień admin w kontenerze:
```bash
docker compose up -d
docker exec prestahop_plugin-prestashop-1 rm -rf /var/www/html/install
docker exec prestahop_plugin-prestashop-1 mv /var/www/html/admin /var/www/html/admin999
```

4. Admin będzie pod: http://localhost:8080/admin999

## Zalecana konfiguracja

**Dla developmentu**: Użyj lokalnego folderu (`prestashop_files/`)
- Łatwiejsza edycja plików
- Pełna kontrola

**Dla szybkich testów**: Użyj volume + montowanie tylko modułu
- Szybsze uruchomienie
- Mniej miejsca na dysku

Oba podejścia działają!
