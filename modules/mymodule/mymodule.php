<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(__DIR__ . '/classes/WebhookSender.php');

class MyModule extends Module
{
    public function __construct()
    {
        $this->name = 'mymodule';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Developer';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7',
            'max' => _PS_VERSION_
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Webhook Events Module');
        $this->description = $this->l('Wysyła eventy ze sklepu przez HTTP POST na zewnętrzny endpoint');
        $this->confirmUninstall = $this->l('Czy na pewno chcesz odinstalować moduł?');
    }

    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        return parent::install()
            && $this->registerHook('actionCartSave')
            && $this->registerHook('actionCartUpdateQuantityBefore')
            && $this->registerHook('actionValidateOrder')
            && $this->registerHook('actionOrderStatusUpdate')
            && $this->registerHook('actionCustomerAccountAdd')
            && Configuration::updateValue('MYMODULE_ENDPOINT_URL', '')
            && Configuration::updateValue('MYMODULE_TOKEN', '')
            && Configuration::updateValue('MYMODULE_DEBUG', 0)
            && Configuration::updateValue('MYMODULE_EVENT_CART_UPDATED', 1)
            && Configuration::updateValue('MYMODULE_EVENT_CART_ITEM_ADDED', 1)
            && Configuration::updateValue('MYMODULE_EVENT_ORDER_CREATED', 1)
            && Configuration::updateValue('MYMODULE_EVENT_ORDER_STATUS_CHANGED', 1)
            && Configuration::updateValue('MYMODULE_EVENT_CUSTOMER_CREATED', 1)
            && Configuration::updateValue('MYMODULE_DATA_CONTEXT', 1)
            && Configuration::updateValue('MYMODULE_DATA_CART', 1)
            && Configuration::updateValue('MYMODULE_DATA_ITEMS', 1)
            && Configuration::updateValue('MYMODULE_DATA_ORDER', 1)
            && Configuration::updateValue('MYMODULE_DATA_CUSTOMER', 1)
            && Configuration::updateValue('MYMODULE_DATA_ADDRESS', 0);
    }

    public function uninstall()
    {
        return Configuration::deleteByName('MYMODULE_ENDPOINT_URL')
            && Configuration::deleteByName('MYMODULE_TOKEN')
            && Configuration::deleteByName('MYMODULE_DEBUG')
            && Configuration::deleteByName('MYMODULE_EVENT_CART_UPDATED')
            && Configuration::deleteByName('MYMODULE_EVENT_CART_ITEM_ADDED')
            && Configuration::deleteByName('MYMODULE_EVENT_ORDER_CREATED')
            && Configuration::deleteByName('MYMODULE_EVENT_ORDER_STATUS_CHANGED')
            && Configuration::deleteByName('MYMODULE_EVENT_CUSTOMER_CREATED')
            && Configuration::deleteByName('MYMODULE_DATA_CONTEXT')
            && Configuration::deleteByName('MYMODULE_DATA_CART')
            && Configuration::deleteByName('MYMODULE_DATA_ITEMS')
            && Configuration::deleteByName('MYMODULE_DATA_ORDER')
            && Configuration::deleteByName('MYMODULE_DATA_CUSTOMER')
            && Configuration::deleteByName('MYMODULE_DATA_ADDRESS')
            && parent::uninstall();
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submit' . $this->name)) {
            Configuration::updateValue('MYMODULE_ENDPOINT_URL', Tools::getValue('MYMODULE_ENDPOINT_URL'));
            Configuration::updateValue('MYMODULE_TOKEN', Tools::getValue('MYMODULE_TOKEN'));
            Configuration::updateValue('MYMODULE_DEBUG', (int)Tools::getValue('MYMODULE_DEBUG'));

            Configuration::updateValue('MYMODULE_EVENT_CART_UPDATED', (int)Tools::getValue('MYMODULE_EVENT_CART_UPDATED'));
            Configuration::updateValue('MYMODULE_EVENT_CART_ITEM_ADDED', (int)Tools::getValue('MYMODULE_EVENT_CART_ITEM_ADDED'));
            Configuration::updateValue('MYMODULE_EVENT_ORDER_CREATED', (int)Tools::getValue('MYMODULE_EVENT_ORDER_CREATED'));
            Configuration::updateValue('MYMODULE_EVENT_ORDER_STATUS_CHANGED', (int)Tools::getValue('MYMODULE_EVENT_ORDER_STATUS_CHANGED'));
            Configuration::updateValue('MYMODULE_EVENT_CUSTOMER_CREATED', (int)Tools::getValue('MYMODULE_EVENT_CUSTOMER_CREATED'));

            Configuration::updateValue('MYMODULE_DATA_CONTEXT', (int)Tools::getValue('MYMODULE_DATA_CONTEXT'));
            Configuration::updateValue('MYMODULE_DATA_CART', (int)Tools::getValue('MYMODULE_DATA_CART'));
            Configuration::updateValue('MYMODULE_DATA_ITEMS', (int)Tools::getValue('MYMODULE_DATA_ITEMS'));
            Configuration::updateValue('MYMODULE_DATA_ORDER', (int)Tools::getValue('MYMODULE_DATA_ORDER'));
            Configuration::updateValue('MYMODULE_DATA_CUSTOMER', (int)Tools::getValue('MYMODULE_DATA_CUSTOMER'));
            Configuration::updateValue('MYMODULE_DATA_ADDRESS', (int)Tools::getValue('MYMODULE_DATA_ADDRESS'));

            $output .= $this->displayConfirmation($this->l('Ustawienia zapisane'));
        }

        return $output . $this->displayForm();
    }

    public function displayForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Konfiguracja'),
                    'icon' => 'icon-cogs'
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Endpoint URL'),
                        'name' => 'MYMODULE_ENDPOINT_URL',
                        'size' => 50,
                        'required' => true,
                        'desc' => $this->l('Adres URL, na który będą wysyłane eventy')
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Token'),
                        'name' => 'MYMODULE_TOKEN',
                        'size' => 50,
                        'required' => true,
                        'desc' => $this->l('Token autoryzacyjny (Bearer)')
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Tryb debug'),
                        'name' => 'MYMODULE_DEBUG',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Włączony')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->l('Wyłączony')]
                        ]
                    ],
                    [
                        'type' => 'html',
                        'name' => '',
                        'html_content' => '<hr><h4>' . $this->l('Eventy do wysyłki') . '</h4>'
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('cart.updated'),
                        'name' => 'MYMODULE_EVENT_CART_UPDATED',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'evt1_on', 'value' => 1, 'label' => $this->l('Włączony')],
                            ['id' => 'evt1_off', 'value' => 0, 'label' => $this->l('Wyłączony')]
                        ]
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('cart.item_added'),
                        'name' => 'MYMODULE_EVENT_CART_ITEM_ADDED',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'evt2_on', 'value' => 1, 'label' => $this->l('Włączony')],
                            ['id' => 'evt2_off', 'value' => 0, 'label' => $this->l('Wyłączony')]
                        ]
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('order.created'),
                        'name' => 'MYMODULE_EVENT_ORDER_CREATED',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'evt3_on', 'value' => 1, 'label' => $this->l('Włączony')],
                            ['id' => 'evt3_off', 'value' => 0, 'label' => $this->l('Wyłączony')]
                        ]
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('order.status_changed'),
                        'name' => 'MYMODULE_EVENT_ORDER_STATUS_CHANGED',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'evt4_on', 'value' => 1, 'label' => $this->l('Włączony')],
                            ['id' => 'evt4_off', 'value' => 0, 'label' => $this->l('Wyłączony')]
                        ]
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('customer.created'),
                        'name' => 'MYMODULE_EVENT_CUSTOMER_CREATED',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'evt5_on', 'value' => 1, 'label' => $this->l('Włączony')],
                            ['id' => 'evt5_off', 'value' => 0, 'label' => $this->l('Wyłączony')]
                        ]
                    ],
                    [
                        'type' => 'html',
                        'name' => '',
                        'html_content' => '<hr><h4>' . $this->l('Dane do wysyłki') . '</h4>'
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Context (język, waluta, shop_id)'),
                        'name' => 'MYMODULE_DATA_CONTEXT',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'data1_on', 'value' => 1, 'label' => $this->l('Włączony')],
                            ['id' => 'data1_off', 'value' => 0, 'label' => $this->l('Wyłączony')]
                        ]
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Cart (dane koszyka)'),
                        'name' => 'MYMODULE_DATA_CART',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'data2_on', 'value' => 1, 'label' => $this->l('Włączony')],
                            ['id' => 'data2_off', 'value' => 0, 'label' => $this->l('Wyłączony')]
                        ]
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Items (produkty / pozycje)'),
                        'name' => 'MYMODULE_DATA_ITEMS',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'data3_on', 'value' => 1, 'label' => $this->l('Włączony')],
                            ['id' => 'data3_off', 'value' => 0, 'label' => $this->l('Wyłączony')]
                        ]
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Order (dane zamówienia)'),
                        'name' => 'MYMODULE_DATA_ORDER',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'data4_on', 'value' => 1, 'label' => $this->l('Włączony')],
                            ['id' => 'data4_off', 'value' => 0, 'label' => $this->l('Wyłączony')]
                        ]
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Customer (dane klienta)'),
                        'name' => 'MYMODULE_DATA_CUSTOMER',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'data5_on', 'value' => 1, 'label' => $this->l('Włączony')],
                            ['id' => 'data5_off', 'value' => 0, 'label' => $this->l('Wyłączony')]
                        ]
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Address (dane adresowe)'),
                        'name' => 'MYMODULE_DATA_ADDRESS',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'data6_on', 'value' => 1, 'label' => $this->l('Włączony')],
                            ['id' => 'data6_off', 'value' => 0, 'label' => $this->l('Wyłączony')]
                        ]
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Zapisz'),
                    'class' => 'btn btn-default pull-right'
                ]
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ?: 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submit' . $this->name;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => [
                'MYMODULE_ENDPOINT_URL' => Configuration::get('MYMODULE_ENDPOINT_URL'),
                'MYMODULE_TOKEN' => Configuration::get('MYMODULE_TOKEN'),
                'MYMODULE_DEBUG' => Configuration::get('MYMODULE_DEBUG'),
                'MYMODULE_EVENT_CART_UPDATED' => Configuration::get('MYMODULE_EVENT_CART_UPDATED'),
                'MYMODULE_EVENT_CART_ITEM_ADDED' => Configuration::get('MYMODULE_EVENT_CART_ITEM_ADDED'),
                'MYMODULE_EVENT_ORDER_CREATED' => Configuration::get('MYMODULE_EVENT_ORDER_CREATED'),
                'MYMODULE_EVENT_ORDER_STATUS_CHANGED' => Configuration::get('MYMODULE_EVENT_ORDER_STATUS_CHANGED'),
                'MYMODULE_EVENT_CUSTOMER_CREATED' => Configuration::get('MYMODULE_EVENT_CUSTOMER_CREATED'),
                'MYMODULE_DATA_CONTEXT' => Configuration::get('MYMODULE_DATA_CONTEXT'),
                'MYMODULE_DATA_CART' => Configuration::get('MYMODULE_DATA_CART'),
                'MYMODULE_DATA_ITEMS' => Configuration::get('MYMODULE_DATA_ITEMS'),
                'MYMODULE_DATA_ORDER' => Configuration::get('MYMODULE_DATA_ORDER'),
                'MYMODULE_DATA_CUSTOMER' => Configuration::get('MYMODULE_DATA_CUSTOMER'),
                'MYMODULE_DATA_ADDRESS' => Configuration::get('MYMODULE_DATA_ADDRESS'),
            ],
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$fields_form]);
    }

    // Hook: actionCartSave - koszyk zapisany
    public function hookActionCartSave($params)
    {
        if (!Configuration::get('MYMODULE_EVENT_CART_UPDATED')) {
            return;
        }

        $cart = $params['cart'];
        $sender = new WebhookSender($this);
        $sender->sendEvent('cart.updated', ['cart' => $cart]);
    }

    // Hook: actionCartUpdateQuantityBefore - dodanie produktu do koszyka
    public function hookActionCartUpdateQuantityBefore($params)
    {
        if (!Configuration::get('MYMODULE_EVENT_CART_ITEM_ADDED')) {
            return;
        }

        $sender = new WebhookSender($this);
        $sender->sendEvent('cart.item_added', [
            'cart' => $params['cart'],
            'product' => $params['product']
        ]);
    }

    // Hook: actionValidateOrder - utworzenie zamówienia
    public function hookActionValidateOrder($params)
    {
        if (!Configuration::get('MYMODULE_EVENT_ORDER_CREATED')) {
            return;
        }

        $order = $params['order'];
        $sender = new WebhookSender($this);
        $sender->sendEvent('order.created', ['order' => $order]);
    }

    // Hook: actionOrderStatusUpdate - zmiana statusu zamówienia
    public function hookActionOrderStatusUpdate($params)
    {
        if (!Configuration::get('MYMODULE_EVENT_ORDER_STATUS_CHANGED')) {
            return;
        }

        $sender = new WebhookSender($this);
        $sender->sendEvent('order.status_changed', [
            'order' => new Order($params['id_order']),
            'newOrderStatus' => $params['newOrderStatus']
        ]);
    }

    // Hook: actionCustomerAccountAdd - utworzenie konta klienta
    public function hookActionCustomerAccountAdd($params)
    {
        if (!Configuration::get('MYMODULE_EVENT_CUSTOMER_CREATED')) {
            return;
        }

        $customer = $params['newCustomer'];
        $sender = new WebhookSender($this);
        $sender->sendEvent('customer.created', ['customer' => $customer]);
    }
}
