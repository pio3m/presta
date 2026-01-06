<?php

class WebhookSender
{
    private $module;

    public function __construct($module)
    {
        $this->module = $module;
    }

    public function sendEvent($eventName, $eventData)
    {
        $endpoint = trim(Configuration::get('MYMODULE_ENDPOINT_URL'));
        $token = trim(Configuration::get('MYMODULE_TOKEN'));
        $debug = Configuration::get('MYMODULE_DEBUG');

        if (empty($endpoint)) {
            $this->log('Brak skonfigurowanego endpointu');
            return false;
        }

        $payload = $this->buildPayload($eventName, $eventData);

        if ($debug) {
            $this->log('Wysyłanie eventu: ' . $eventName . ' na ' . $endpoint);
            $this->log('Payload: ' . json_encode($payload));
        }

        $result = $this->sendHttpPost($endpoint, $token, $payload);

        if ($debug) {
            $this->log('Wynik: ' . ($result['success'] ? 'sukces' : 'błąd'));
            $this->log('Status HTTP: ' . $result['http_code']);
            if (!$result['success']) {
                $this->log('Błąd: ' . $result['error']);
            }
        }

        return $result['success'];
    }

    private function buildPayload($eventName, $eventData)
    {
        $payload = [
            'event' => $eventName,
            'occurred_at' => date('c'),
            'data' => []
        ];

        // Dodaj context jeśli włączone
        if (Configuration::get('MYMODULE_DATA_CONTEXT')) {
            $context = Context::getContext();
            $payload['data']['context'] = [
                'shop_id' => (int)$context->shop->id,
                'language' => $context->language->iso_code,
                'currency' => $context->currency->iso_code
            ];
        }

        // Obsługa różnych typów eventów
        if (isset($eventData['cart']) && Configuration::get('MYMODULE_DATA_CART')) {
            $cart = $eventData['cart'];
            $payload['data']['cart'] = $this->getCartData($cart);
        }

        if (isset($eventData['product']) && Configuration::get('MYMODULE_DATA_ITEMS')) {
            $product = $eventData['product'];
            $payload['data']['item'] = [
                'product_id' => (int)$product->id,
                'name' => $product->name,
                'price' => (float)$product->price
            ];
        }

        if (isset($eventData['order']) && Configuration::get('MYMODULE_DATA_ORDER')) {
            $order = $eventData['order'];
            $payload['data']['order'] = $this->getOrderData($order);

            // Dodaj items z zamówienia
            if (Configuration::get('MYMODULE_DATA_ITEMS')) {
                $payload['data']['items'] = $this->getOrderItems($order);
            }
        }

        if (isset($eventData['customer']) && Configuration::get('MYMODULE_DATA_CUSTOMER')) {
            $customer = $eventData['customer'];
            $payload['data']['customer'] = $this->getCustomerData($customer);
        }

        if (isset($eventData['newOrderStatus'])) {
            $payload['data']['new_status'] = [
                'id' => (int)$eventData['newOrderStatus']->id,
                'name' => $eventData['newOrderStatus']->name
            ];
        }

        // Dodaj address jeśli włączone
        if (Configuration::get('MYMODULE_DATA_ADDRESS')) {
            if (isset($eventData['order'])) {
                $order = $eventData['order'];
                $payload['data']['address'] = $this->getAddressData($order->id_address_delivery);
            }
        }

        return $payload;
    }

    private function getCartData($cart)
    {
        if (is_object($cart)) {
            return [
                'id' => (int)$cart->id,
                'total' => (float)$cart->getOrderTotal(true, Cart::BOTH),
                'products_count' => (int)$cart->nbProducts()
            ];
        }
        return ['id' => (int)$cart];
    }

    private function getOrderData($order)
    {
        return [
            'id' => (int)$order->id,
            'reference' => $order->reference,
            'total_paid' => (float)$order->total_paid,
            'total_products' => (float)$order->total_products,
            'currency' => $order->id_currency,
            'payment' => $order->payment,
            'current_state' => (int)$order->current_state
        ];
    }

    private function getOrderItems($order)
    {
        $items = [];
        $products = $order->getProducts();

        foreach ($products as $product) {
            $items[] = [
                'product_id' => (int)$product['product_id'],
                'product_name' => $product['product_name'],
                'quantity' => (int)$product['product_quantity'],
                'price' => (float)$product['product_price'],
                'total' => (float)$product['total_price_tax_incl']
            ];
        }

        return $items;
    }

    private function getCustomerData($customer)
    {
        return [
            'id' => (int)$customer->id,
            'email' => $customer->email,
            'firstname' => $customer->firstname,
            'lastname' => $customer->lastname
        ];
    }

    private function getAddressData($addressId)
    {
        $address = new Address($addressId);
        return [
            'id' => (int)$address->id,
            'alias' => $address->alias,
            'address1' => $address->address1,
            'address2' => $address->address2,
            'postcode' => $address->postcode,
            'city' => $address->city,
            'country' => Country::getNameById(Configuration::get('PS_LANG_DEFAULT'), $address->id_country)
        ];
    }

    private function sendHttpPost($url, $token, $payload)
    {
        $ch = curl_init($url);

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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        $success = ($httpCode >= 200 && $httpCode < 300);

        return [
            'success' => $success,
            'http_code' => $httpCode,
            'response' => $response,
            'error' => $error
        ];
    }

    private function log($message)
    {
        $logFile = _PS_MODULE_DIR_ . 'mymodule/logs/webhook.log';
        $logDir = dirname($logFile);

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    }
}
