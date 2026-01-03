#!/bin/bash

echo "=== Quick Fix - Usuwanie install i zmiana admin ==="
echo
echo "Ten skrypt działa bezpośrednio w kontenerze (bez kopiowania plików)"
echo

# Sprawdź czy kontener działa
if ! docker ps | grep -q "prestahop_plugin-prestashop-1"; then
    echo "✗ Kontener PrestaShop nie działa!"
    echo "  Uruchom: docker compose up -d"
    exit 1
fi

echo "✓ Kontener działa"
echo

# Generuj losową nazwę
RANDOM_SUFFIX=$(openssl rand -hex 8 2>/dev/null || echo "$(date +%s)")
NEW_ADMIN_DIR="admin${RANDOM_SUFFIX}"

echo "1. Usuwanie folderu install..."
docker exec prestahop_plugin-prestashop-1 bash -c "
    if [ -d /var/www/html/install ]; then
        rm -rf /var/www/html/install
        echo '  ✓ Folder install usunięty'
    else
        echo '  ℹ Folder install już nie istnieje'
    fi
"

echo

echo "2. Zmiana nazwy folderu admin..."
docker exec prestahop_plugin-prestashop-1 bash -c "
    if [ -d /var/www/html/admin ]; then
        mv /var/www/html/admin /var/www/html/$NEW_ADMIN_DIR
        echo '  ✓ Folder admin zmieniony na: $NEW_ADMIN_DIR'
    else
        echo '  ℹ Folder admin nie znaleziony (może już zmieniono)'
        # Znajdź istniejący folder admin*
        EXISTING=\$(ls -d /var/www/html/admin* 2>/dev/null | head -1)
        if [ -n "\$EXISTING" ]; then
            NEW_ADMIN_DIR=\$(basename "\$EXISTING")
            echo "  ✓ Znaleziono: \$NEW_ADMIN_DIR"
        fi
    fi
"

# Zapisz nazwę
echo "$NEW_ADMIN_DIR" > .admin_folder_name

echo

echo "=== Gotowe! ==="
echo
echo "🔐 Panel admin:"
echo "   URL: http://localhost:8080/$NEW_ADMIN_DIR"
echo "   Login: admin@prestashop.com"
echo "   Hasło: Admin123"
echo
echo "Frontend: http://localhost:8080"
echo

# Sprawdź czy moduł jest obecny
echo "3. Sprawdzanie modułu..."
docker exec prestahop_plugin-prestashop-1 bash -c "
    if [ -f /var/www/html/modules/mymodule/mymodule.php ]; then
        echo '  ✓ Moduł mymodule obecny'
    else
        echo '  ✗ Moduł mymodule NIE ZNALEZIONY'
        echo '  Sprawdź montowanie w docker-compose.yml'
    fi
"

echo
echo "Możesz teraz zalogować się do panelu admin i zainstalować moduł!"
