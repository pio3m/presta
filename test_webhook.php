<?php
/**
 * Prosty test wysyłania webhooka
 *
 * Użycie:
 * php test_webhook.php
 */

function sendTestWebhook($endpoint, $token, $eventName, $data)
{
    $payload = [
        'event' => $eventName,
        'occurred_at' => date('c'),
        'data' => $data
    ];

    echo "=== Wysyłanie webhooka ===\n";
    echo "Endpoint: $endpoint\n";
    echo "Event: $eventName\n";
    echo "Payload:\n" . json_encode($payload, JSON_PRETTY_PRINT) . "\n\n";

    $ch = curl_init($endpoint);

    $headers = [
        'Content-Type: application/json',
    ];

    if (!empty($token)) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    curl_close($ch);

    echo "=== Odpowiedź ===\n";
    echo "HTTP Code: $httpCode\n";

    if ($error) {
        echo "Błąd: $error\n";
    } else {
        echo "Odpowiedź: $response\n";
    }

    echo "\n";

    return ($httpCode >= 200 && $httpCode < 300);
}

// Konfiguracja
$endpoint = 'http://localhost:8080/module/mymodule/api';
$token = 'test_token_123456';

// Test 1: cart.item_added
echo "TEST 1: Dodanie produktu do koszyka\n";
echo "=====================================\n";
sendTestWebhook($endpoint, $token, 'cart.item_added', [
    'context' => [
        'shop_id' => 1,
        'language' => 'pl',
        'currency' => 'PLN'
    ],
    'cart' => [
        'id' => 123,
        'total' => 199.99,
        'products_count' => 1
    ],
    'item' => [
        'product_id' => 456,
        'name' => 'Test Product',
        'price' => 199.99
    ],
    'customer' => [
        'id' => 789,
        'email' => 'test@example.com'
    ]
]);

sleep(1);

// Test 2: order.created
echo "TEST 2: Utworzenie zamówienia\n";
echo "==============================\n";
sendTestWebhook($endpoint, $token, 'order.created', [
    'context' => [
        'shop_id' => 1,
        'language' => 'pl',
        'currency' => 'PLN'
    ],
    'order' => [
        'id' => 1001,
        'reference' => 'TEST001',
        'total_paid' => 299.99,
        'total_products' => 299.99,
        'currency' => 1,
        'payment' => 'Przelew',
        'current_state' => 2
    ],
    'items' => [
        [
            'product_id' => 456,
            'product_name' => 'Test Product',
            'quantity' => 1,
            'price' => 299.99,
            'total' => 299.99
        ]
    ],
    'customer' => [
        'id' => 789,
        'email' => 'test@example.com',
        'firstname' => 'Jan',
        'lastname' => 'Kowalski'
    ]
]);

sleep(1);

// Test 3: customer.created
echo "TEST 3: Utworzenie konta klienta\n";
echo "==================================\n";
sendTestWebhook($endpoint, $token, 'customer.created', [
    'context' => [
        'shop_id' => 1,
        'language' => 'pl',
        'currency' => 'PLN'
    ],
    'customer' => [
        'id' => 999,
        'email' => 'newuser@example.com',
        'firstname' => 'Anna',
        'lastname' => 'Nowak'
    ]
]);

echo "\n=== Testy zakończone ===\n";
