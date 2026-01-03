#!/bin/bash

echo "=== PrestaShop Post-Install Script ==="
echo

PS_DIR="./prestashop_files"

if [ ! -d "$PS_DIR" ]; then
    echo "✗ Folder $PS_DIR nie istnieje!"
    exit 1
fi

echo "1. Sprawdzanie statusu..."
if [ -d "$PS_DIR/install" ]; then
    echo "  ✓ Znaleziono folder install"
else
    echo "  ✓ Folder install już nie istnieje"
fi

if [ -d "$PS_DIR/admin" ]; then
    echo "  ✓ Znaleziono folder admin"
else
    echo "  ⚠ Folder admin nie znaleziony (może już zmieniono nazwę)"
fi

echo

# Generuj losową nazwę dla folderu admin
RANDOM_SUFFIX=$(openssl rand -hex 8 2>/dev/null || echo "$(date +%s)$(shuf -i 1000-9999 -n 1)")
NEW_ADMIN_DIR="admin${RANDOM_SUFFIX}"

echo "2. Usuwanie folderu install..."
if [ -d "$PS_DIR/install" ]; then
    rm -rf "$PS_DIR/install"
    echo "  ✓ Folder install usunięty"
else
    echo "  ℹ Folder install już nie istnieje"
fi

echo

echo "3. Zmiana nazwy folderu admin..."
if [ -d "$PS_DIR/admin" ]; then
    mv "$PS_DIR/admin" "$PS_DIR/$NEW_ADMIN_DIR"
    echo "  ✓ Folder admin zmieniony na: $NEW_ADMIN_DIR"
    echo
    echo "  📋 WAŻNE! Nowy URL panelu admin:"
    echo "     http://localhost:8080/$NEW_ADMIN_DIR"
    echo

    # Zapisz nazwę folderu do pliku
    echo "$NEW_ADMIN_DIR" > .admin_folder_name
    echo "  ✓ Nazwa zapisana w pliku .admin_folder_name"
else
    echo "  ℹ Folder admin nie znaleziony"
    # Sprawdź czy jest już jakiś folder admin*
    EXISTING_ADMIN=$(find "$PS_DIR" -maxdepth 1 -type d -name "admin*" | head -1)
    if [ -n "$EXISTING_ADMIN" ]; then
        ADMIN_NAME=$(basename "$EXISTING_ADMIN")
        echo "  ✓ Znaleziono istniejący folder: $ADMIN_NAME"
        echo "$ADMIN_NAME" > .admin_folder_name
        echo
        echo "  📋 URL panelu admin:"
        echo "     http://localhost:8080/$ADMIN_NAME"
    fi
fi

echo

echo "4. Ustawianie uprawnień..."
chmod -R 755 "$PS_DIR"
chmod -R 777 "$PS_DIR/var" 2>/dev/null || true
chmod -R 777 "$PS_DIR/app/cache" 2>/dev/null || true
chmod -R 777 "$PS_DIR/app/logs" 2>/dev/null || true
chmod -R 777 "$PS_DIR/img" 2>/dev/null || true
chmod -R 777 "$PS_DIR/modules" 2>/dev/null || true
chmod -R 777 "$PS_DIR/themes" 2>/dev/null || true
chmod -R 777 "$PS_DIR/config" 2>/dev/null || true
echo "  ✓ Uprawnienia ustawione"

echo

echo "=== Gotowe! ==="
echo
echo "Następne kroki:"
echo "1. Zatrzymaj kontenery: docker compose down"
echo "2. Docker używa teraz lokalnego folderu prestashop_files/"
echo "3. Uruchom ponownie: docker compose up -d"
echo "4. Zaloguj się do panelu admin"
echo

if [ -f .admin_folder_name ]; then
    ADMIN_NAME=$(cat .admin_folder_name)
    echo "🔐 Panel admin:"
    echo "   URL: http://localhost:8080/$ADMIN_NAME"
    echo "   Login: admin@prestashop.com"
    echo "   Hasło: Admin123"
fi
