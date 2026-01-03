<?php
/**
 * Prosty serwer odbierający webhooki
 *
 * Użycie:
 * php -S localhost:9000 test_receiver.php
 */

$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['REQUEST_URI'];

// Logowanie requestu
$logEntry = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $method,
    'path' => $path,
    'headers' => getallheaders(),
    'body' => null
];

echo "=== Otrzymano request ===\n";
echo "Czas: " . $logEntry['timestamp'] . "\n";
echo "Metoda: $method\n";
echo "Ścieżka: $path\n";
echo "\nNagłówki:\n";
foreach ($logEntry['headers'] as $key => $value) {
    echo "  $key: $value\n";
}

// Odczytaj body jeśli jest POST
if ($method === 'POST') {
    $body = file_get_contents('php://input');
    $logEntry['body'] = $body;

    echo "\nBody (raw):\n$body\n";

    // Spróbuj zdekodować JSON
    $data = json_decode($body, true);
    if ($data !== null) {
        echo "\nBody (JSON):\n";
        echo json_encode($data, JSON_PRETTY_PRINT) . "\n";

        // Sprawdź czy to webhook z modułu
        if (isset($data['event']) && isset($data['occurred_at'])) {
            echo "\n*** Wykryto event: " . $data['event'] . " ***\n";
        }

        // Walidacja tokenu
        if (isset($logEntry['headers']['Authorization'])) {
            $auth = $logEntry['headers']['Authorization'];
            if (strpos($auth, 'Bearer ') === 0) {
                $token = substr($auth, 7);
                echo "\n*** Token: $token ***\n";

                // Prosta walidacja (w produkcji użyj bezpiecznej walidacji)
                if ($token === 'test_token_123456') {
                    echo "*** Token poprawny! ***\n";
                } else {
                    echo "*** Token niepoprawny! ***\n";
                    http_response_code(401);
                    echo json_encode(['error' => 'Unauthorized']);
                    exit;
                }
            }
        }
    }
}

echo "\n=========================\n\n";

// Zapisz do pliku log
$logFile = __DIR__ . '/webhook_received.log';
file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND);

// Odpowiedz sukcesem
header('Content-Type: application/json');
http_response_code(200);
echo json_encode([
    'status' => 'success',
    'message' => 'Webhook received',
    'timestamp' => date('c')
]);
