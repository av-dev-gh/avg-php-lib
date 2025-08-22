# AVANGARD PHP Client
Библиотека для интеграции с API V5 банка Авангард. Реализует основные запросы к API банка. Подробное описание API
смотрите в технической документации.

## Установка с помощью composer

1. В корне директории, где собираетесь установить библиотеку, создайте файл <b>composer.json</b> со следующим содержимым:
```json
{
    "require": {
        "avangard/api": "dev-master"
    },
    "repositories": [
        {
            "type": "git",
            "url": "https://github.com/av-dev-gh/avg-php-lib.git"
        }
    ]
}
```

2. В этой же директории выполните команду
```php
composer install
```

## Использование

### Подключение библиотеки к проекту

Чтобы использовать методы библиотеки в своём коде, необходимо подключить скрипт автозагрузки классов и создать объект
класса `ApiClient`
```php
require_once ("vendor/autoload.php");
use Avangard\ApiClient;

$apiClient = new ApiClient($shopId, $shopPassword, $shopSign, $serverSign, $test_mode, $proxy);
```

### Параметры конструктора
- `shopId` - ID интернет-магазина в банковской системе*
- `shopPassword` - пароль интернет-магазина в банковской системе*
- `shopSign` - подпись интернет-магазина в банковской системе*
- `serverSign` - подпись ответов банка*
- `test_mode` - булево значение, включающее тестовый режим (запросы будут отправляться на тестовый URL). По умолчанию `false`
- `proxy` - http URL прокси сервера (если используется). По умолчанию `null`

*указанные параметры выдаются техподдержкой банка при заключении договора на интернет-эквайринг

**ВНИМАНИЕ!**  
Все методы данной библиотеки следует использовать в конструкции try/catch:
```php
try {
    // All methods here...
} catch (\Exception $e) {
    if ($debug) {
        \Avangard\Lib\Logger::log($e);
        // Your custom logging here...
    }
}
```
Метод `\Avangard\Lib\Logger::log` рекомендуется использовать с флагом `$debug` который может, например, 
задаваться в административной панели сайта. Этот метод отсылает отчёты об ошибках в telegram разработчика.

## Заказы и оплата

1. `prepareForms($params)` - подготавливает параметры для формы оплаты.

Параметры:
- ```php
    $params = [
        'REQUEST_TYPE' => (                 // тип запроса. По умолчанию ApiClient::POSTFORM
            ApiClient::HOST2HOST            // регистрирует оплату в интернет-эквайринге и возвращает TICKET-параметр для последующей оплаты заказа
            ApiClient::POSTFORM             // подготавливает параметры для HTML формы оплаты, показываемой на стороне клиента (часто требуется для CMS)
            ApiClient::GETURL               // регистрирует оплату в интернет-эквайринге и возвращает ссылку для последующей оплаты заказа
        ),
        'PAYMENT_TYPE' => (                 // способ оплаты. По умолчанию ApiClient::PAYMENT_TYPE_CARD
            ApiClient::PAYMENT_TYPE_CARD    // возвращает набор полей для оплаты с помощью банковской карты
            ApiClient::PAYMENT_TYPE_QR      // возвращает набор полей для оплаты по QR-коду
            ApiClient::PAYMENT_TYPE_ALL     // возвращает два набора полей - для оплаты по карте и по QR-коду 
        ), 
        'ORDER' => [
            'AMOUNT' => 'number, обязательный',                     // сумма к оплате в копейках
            'ORDER_NUMBER' =>  'string, обязательный',              // номер заказа в интернет-магазине
            'ORDER_DESCRIPTION' => 'string, обязательный',          // описание заказа в интернет-магазине
            'LANGUAGE' => 'string, обязательный, по умолчанию RU',  // язык описания заказа в интернет-магазине
            'BACK_URL' => 'string, обязательный',                   // ссылка безусловного редиректа
            'BACK_URL_OK' => 'string',                              // ссылка успешного редиректа
            'BACK_URL_FAIL' => 'string',                            // ссылка НЕуспешного редиректа
            'CLIENT_NAME' => 'string',                              // имя плательщика
            'CLIENT_ADDRESS' => 'string',                           // физический адрес плательщика
            'CLIENT_EMAIL' => 'string',                             // email плательщика
            'CLIENT_PHONE' => 'string',                             // телефон плательщика
                // Внимание! Если у вас настроена фискализация,
                // то должно быть обязательно заполнено хотя бы 
                // одно поле - CLIENT_EMAIL или CLIENT_PHONE!
            'CLIENT_IP' => 'string',                                // ip-адрес плательщика
            'ORDER_ITEMS' => 'array'                                // можно передавать при настроенной фискализации для формирования позиций в чеке
                [
                    [
                        'num' => 'number, обязательный',            // номер позиции в чеке (1, 2, 3, ...)
                        'name' => 'string, обязательный',           // наименование товара
                        'quantity' => 'number, обязательный',       // количество товара
                        'price' => 'number, обязательный',          // цена за единицу товара в рублях
                        'fullPrice' => 'number, обязательный',      // итоговая цена позиции
                        'isService' => (0/1)                        // значение заполняется, если позиция в чеке является доставкой или иной услугой (необходимо для выставления правильного объекта расчёта)
                    ],
                    ...
                ]
        ],  
    ];
    ```
    
Возвращаемые значения:
- `REQUEST_TYPE = ApiClient::HOST2HOST`:
```php
[
    "URL" => "https://pay.avangard.ru/iacq/pay",
    "METHOD" => "get",
    // если выбрана оплата по карте
    "INPUTS" => [
        "ticket" => "JGceLCtt000012682687LskJXuIpbfmpgeeKgkcj"
    ],
    // если выбрана оплата по QR-коду
    "INPUTS_QR" => [
        "ticket" => "NcQohycZ000026617760GmDUPpIvXfUPevymBTmf"
    ]
]
```
- `REQUEST_TYPE = ApiClient::POSTFORM`:
```php
[
    "URL" => "https://pay.avangard.ru/iacq/post",
    "METHOD" => "post",
    // если выбрана оплата по карте
    "INPUTS" => [
        "shop_id" => "1",
        "amount" => 1000,
        "order_number" => "1",
        "order_description" => "My desc",
        "back_url" => "https://example.ru/",
        "back_url_ok" => "https://example.ru/payments/avangard/?result=success",
        "back_url_fail" => "https://example.ru/payments/avangard/?result=failure",
        "client_name" => "Test Client",
        "client_email" => "test@test.com",
        "client_phone" => "+79991234567"
        "order_items" => '[{"num":1,"name":"Test Product 1","quantity":1,"price":10,"fullPrice":10},{"num":2,"name":"Test Product 2","quantity":2,"price":10,"fullPrice":20},{"num":3,"name":"Delivery","quantity":1,"price":10,"fullPrice":10,"isService":1}]'
        "language" => "RU",
        "signature" => "1EBE4761D9B165D8FF784803686AF511",
      ],
    // если выбрана оплата по QR-коду
    "INPUTS_QR" => [
        "shop_id" => "1",
        "amount" => 1000,
        "order_number" => "1",
        "order_description" => "My desc",
        "back_url" => "https://example.ru/",
        "back_url_ok" => "https://example.ru/payments/avangard/?result=success",
        "back_url_fail" => "https://example.ru/payments/avangard/?result=failure",
        "client_name" => "Test Client",
        "client_email" => "test@test.com",
        "client_phone" => "+79991234567"
        "order_items" => '[{"num":1,"name":"Test Product 1","quantity":1,"price":10,"fullPrice":10},{"num":2,"name":"Test Product 2","quantity":2,"price":10,"fullPrice":20},{"num":3,"name":"Delivery","quantity":1,"price":10,"fullPrice":10,"isService":1}]'
        "is_qr" => 1,
        "language" => "RU",
        "signature" => "1EBE4761D9B165D8FF784803686AF511",
    ]
]
```
- `REQUEST_TYPE = ApiClient::GETURL`:
```php
[
    // если выбрана оплата по карте
    "PAY_URL" => "https://pay.avangard.ru/iacq/pay?ticket=JGceLCtt000012682687LskJXuIpbfmpgeeKgkcj",
    // если выбрана оплата по QR-коду
    "PAY_URL_QR" => "https://pay.avangard.ru/iacq/pay?ticket=NcQohycZ000026617760GmDUPpIvXfUPevymBTmf"
]
```

Пример HOST2HOST/GETURL:
```php
<?php
require_once "vendor/autoload.php";
use Avangard\ApiClient;

$debug = true;

try {
    $apiClient = new ApiClient(
        1,
        'shop password',
        'shop sign',
        'server sign',
    );
    
    $order = [
        'AMOUNT' => 4000,
        'ORDER_NUMBER' => '1',
        'ORDER_DESCRIPTION' => 'My desc',
        'BACK_URL' => 'https://example.ru/payments/avangard/?result=success',
        'ORDER_ITEMS' => [
            [
                'num' => 1,
                'name' => 'Test Product 1',
                'quantity' => 1,
                'price' => 10,
                'fullPrice' => 10,
            ],
            [
                'num' => 2,
                'name' => 'Test Product 2',
                'quantity' => 2,
                'price' => 10,
                'fullPrice' => 20,
            ],
            [
                'num' => 3,
                'name' => 'Delivery',
                'quantity' => 1,
                'price' => 10,
                'fullPrice' => 10,
                'isService' => 1,
            ],
        ],
    ];
    
    $result = $apiClient->request->prepareForms([
        'ORDER' => $order,
        'REQUEST_TYPE' => ApiClient::HOST2HOST,
        'PAYMENT_TYPE' => ApiClient::PAYMENT_TYPE_ALL
    ]);
    
    print_r($result);
} catch (\Exception $e) {
    if ($debug) {
        \Avangard\Lib\Logger::log($e);
    }
}
```

Пример POSTFORM:
```php
<?php
require_once "vendor/autoload.php";

use Avangard\ApiClient;

function getFormData($orderNumber, $orderDescription, $amount, $orderItems)
{
    $debug = true;
    
    try {
        $apiClient = new ApiClient(
            1,
            'shop password',
            'shop sign',
            'server sign'
        );
        
        $order = [
            'AMOUNT' => $amount,
            'ORDER_NUMBER' => $orderNumber,
            'ORDER_DESCRIPTION' => $orderDescription,
            'BACK_URL' => 'https://example.ru/payments/avangard/?result=success',
            'ORDER_ITEMS' => $orderItems,
        ];
        
        $result = $apiClient->request->prepareForms([
            'ORDER' => $order,
            'REQUEST_TYPE' => ApiClient::POSTFORM,
            'PAYMENT_TYPE' => ApiClient::PAYMENT_TYPE_ALL
        ]);
        
        return $result;
    } catch (\Exception $e) {
        if ($debug) {
            \Avangard\Lib\Logger::log($e);
        }
    }
}

$orderNumber = '1';
$orderDescription = 'My desc';
$amount = 4000;
$orderItems = [
    [
        'num' => 1,
        'name' => 'Test Product 1',
        'quantity' => 1,
        'price' => 10,
        'fullPrice' => 10,
    ],
    [
        'num' => 2,
        'name' => 'Test Product 2',
        'quantity' => 2,
        'price' => 10,
        'fullPrice' => 20,
    ],
    [
        'num' => 3,
        'name' => 'Delivery',
        'quantity' => 1,
        'price' => 10,
        'fullPrice' => 10,
        'isService' => 1,
    ],
];

$formData = getFormData($orderNumber, $orderDescription, $amount, $orderItems);
?>

<?php if (!empty($formData['INPUTS'])):?>
    <form id="form" action="<?=$formData['URL'];?>" method="<?=$formData['METHOD'];?>">
        <?php foreach ($formData['INPUTS'] as $name => $value):?>
            <input type="hidden" name="<?=$name;?>" value="<?=htmlspecialchars($value);?>">
        <?php endforeach;?>
        <button type="submit">Перейти к оплате</button>
    </form>
<?php endif;?>

<?php if (!empty($formData['INPUTS_QR'])):?>
    <form id="form" action="<?=$formData['URL'];?>" method="<?=$formData['METHOD'];?>">
        <?php foreach ($formData['INPUTS_QR'] as $name => $value):?>
            <input type="hidden" name="<?=$name;?>" value="<?=htmlspecialchars($value);?>">
        <?php endforeach;?>
        <button type="submit">Перейти к оплате по QR</button>
    </form>
<?php endif;?>
```

2. `orderRegister($order)` - регистрирует оплату в системе интернет-эквайринга и возвращает TICKET-параметр для дальнейшей
оплаты.

Параметры:
```php
$order = [
    'AMOUNT' => 'number, обязательный',                     // сумма к оплате в копейках
    'ORDER_NUMBER' =>  'string, обязательный',              // номер заказа в интернет-магазине
    'ORDER_DESCRIPTION' => 'string, обязательный',          // описание заказа в интернет-магазине
    'LANGUAGE' => 'string, обязательный, по умолчанию RU',  // язык описания заказа в интернет-магазине
    'BACK_URL' => 'string, обязательный',                   // ссылка безусловного редиректа
    'BACK_URL_OK' => 'string',                              // ссылка успешного редиректа
    'BACK_URL_FAIL' => 'string',                            // ссылка НЕуспешного редиректа
    'CLIENT_NAME' => 'string',                              // имя плательщика
    'CLIENT_ADDRESS' => 'string',                           // физический адрес плательщика
    'CLIENT_EMAIL' => 'string',                             // email плательщика
    'CLIENT_PHONE' => 'string',                             // телефон плательщика
        // Внимание! Если у вас настроена фискализация,
        // то должно быть обязательно заполнено хотя бы 
        // одно поле - CLIENT_EMAIL или CLIENT_PHONE!
    'CLIENT_IP' => 'string',                                // ip-адрес плательщика
    'ORDER_ITEMS' => 'array'                                // можно передавать при настроенной фискализации для формирования позиций в чеке
        [
            [
                'num' => 'number, обязательный',            // номер позиции в чеке (1, 2, 3, ...)
                'name' => 'string, обязательный',           // наименование товара
                'quantity' => 'number, обязательный',       // количество товара
                'price' => 'number, обязательный',          // цена за единицу товара в рублях
                'fullPrice' => 'number, обязательный',      // итоговая цена позиции
                'isService' => (0/1)                        // значение заполняется, если позиция в чеке является доставкой или иной услугой (необходимо для выставления правильного объекта расчёта)
            ],
            ...
        ]
];
```

Возвращаемое значение:
```php
[
  "TICKET" => "xQElJQhi000012682701rKuBUpngKsIsUBKPBmfM"
]
```

Пример:
```php
<?php
require_once "vendor/autoload.php";
use Avangard\ApiClient;

$debug = true;
    
try {
    $apiClient = new ApiClient(
        1,
        'shop password',
        'shop sign',
        'server sign'
    );
    
    $order = [
        'AMOUNT' => 1000,
        'ORDER_NUMBER' => '1',
        'ORDER_DESCRIPTION' => 'My desc',
        'BACK_URL' => 'https://example.ru/payments/avangard/?result=success'
    ];
    
    $result = $apiClient->request->orderRegister($order);
    
    print_r($result);
} catch (\Exception $e) {
    if ($debug) {
        \Avangard\Lib\Logger::log($e);
    }
}
```

3. `getOrderByTicket($ticket)` - получить информацию об оплате по TICKET-параметру.

Параметры:
- `string $ticket` - уникальный идентификатор оплаты в системе интернет-эквайринга банка

Пример возвращаемого массива:
```php
[
    'id' => 1234567890,
    'method_name' => 'SCR',
    'auth_code' => 'ABC123',
    'status_code' => 5,
    'status_desc' => 'Авторизация успешно завершена',
    'status_date' => '2012-04-23T12:47:00+04:00',
]
```

Пример:
```php
<?php
require_once "vendor/autoload.php";
use Avangard\ApiClient;

$debug = true;
    
try {
    $apiClient = new ApiClient(
        1,
        'shop password',
        'shop sign',
        'server sign'
    );
    
    $result = $apiClient->request->getOrderByTicket("UWyNLGVh000012669958czZpckkboKNDpUysDhlL");
    
    print_r($result);
} catch (\Exception $e) {
    if ($debug) {
        \Avangard\Lib\Logger::log($e);
    }
}
```

## Callback запросы из банка

1. `isCorrectHash($params)` - проверяет подпись callback запроса из банка.

Параметры:
- `array $params` - массив входящих параметров запроса

Возвращаемые значения:  
`true`, если подпись верна, иначе `false`

Пример:
```php
<?php
require_once "vendor/autoload.php";
use Avangard\ApiClient;

$debug = true;

$_REQUEST = [
    'id' => '12663423',
    'signature' => '07EB5673A9ECD4506C112B3EE3E3AF80',
    'method_name' => 'D3S',
    'shop_id' => '1',
    'ticket' => 'OWXZAkWg000012663423irlhpRKbAevpPsymgoDu',
    'status_code' => '3',
    'auth_code' => '',
    'amount' => '2000',
    'card_num' => '546938******1152',
    'order_number' => '1',
    'status_desc' => 'Исполнен',
    'status_date' => '2019-11-05 10:17:17.0',
    'refund_amount' => '0',
    'exp_mm' => '09',
    'exp_yy' => '22'
];

try {
    $apiClient = new ApiClient(
        1,
        'shop password',
        'shop sign',
        'server sign'
    );
    
    $result = $apiClient->request->isCorrectHash($_REQUEST);
    
    var_dump($result); // true или false
} catch (\Exception $e) {
    if ($debug) {
        \Avangard\Lib\Logger::log($e);
    }
}
```

2. `sendResponse()` - отправляет корректный код состояния ответа на callback запрос из банка, затем завершает выполнение 
скрипта. Если вы реализуете обработку callback запросов из банка, **необходимо всегда** вызывать данный метод после
успешной обработки запроса

Пример:
```php
<?php
require_once "vendor/autoload.php";
use Avangard\ApiClient;

$debug = true;

$_REQUEST = array (
    'id' => '12663423',
    'signature' => '07EB5673A9ECD4506C112B3EE3E3AF80',
    'method_name' => 'D3S',
    'shop_id' => '1',
    'ticket' => 'OWXZAkWg000012663423irlhpRKbAevpPsymgoDu',
    'status_code' => '3',
    'auth_code' => '',
    'amount' => '200',
    'card_num' => '546938******1152',
    'order_number' => '1',
    'status_desc' => 'Исполнен',
    'status_date' => '2019-11-05 10:17:17.0',
    'refund_amount' => '0',
    'exp_mm' => '09',
    'exp_yy' => '22'
);

try {
    $apiClient = new ApiClient(
        1,
        'shop password',
        'shop sign',
        'server sign'
    );
    
    if ($apiClient->request->isCorrectHash($_REQUEST)) {
    
        // Действия при получении callback запроса из банка...
    
        // Отправляем ответ, что callback запрос был успешно обработан
        $apiClient->request->sendResponse();
    }
} catch (\Exception $e) {
    if ($debug) {
        \Avangard\Lib\Logger::log($e);
    }
}
```

## Возврат средств и отмена оплаты

1. `orderRefund($params)` - производит частичное/полное возмещение денежных средств по конкретной оплате.  
Если оплата была совершена по QR коду (с помощью СБП), то после отправки запроса на возмещение денежных средств, метод
производит проверку статуса возврата, т.к. возврат по оплатам, совершённым по QR, производится асинхронно. Всего
осуществляется максимум 8 проверок статуса возврата, задержка между проверками 5 секунд

Параметры:
- ```php
    $params = [
        'TICKET' => 'string, обязательный', // уникальный идентификатор оплаты в системе интернет-эквайринга банка
        'AMOUNT' => 'number',               // сумма к возврату в копейках. Если не передавать данный параметр, то будет произведен полный возврат денежных средств. Для этого ключ AMOUNT в массиве $params должен отсутствовать
        'ORDER_ITEMS' => 'array'            // можно передавать при настроенной фискализации для формирования позиций в чеке
        [
            [
                'num' => 'number, обязательный',        // номер позиции в чеке (1, 2, 3, ...)
                'name' => 'string, обязательный',       // наименование товара
                'quantity' => 'number, обязательный',   // количество товара
                'price' => 'number, обязательный',      // цена за единицу товара в рублях
                'fullPrice' => 'number, обязательный',  // итоговая цена позиции
                'isService' => (0/1)                    // значение заполняется, если позиция в чеке является доставкой или иной услугой (необходимо для выставления правильного объекта расчёта)
            ],
            ...
        ],  
    ];
    ```

Возвращаемое значение:
```php
[
    "transaction_id" => 124665
]
```

Пример:
```php
<?php
require_once "vendor/autoload.php";
use Avangard\ApiClient;

$debug = true;

try {
    $apiClient = new ApiClient(
        1,
        'shop password',
        'shop sign',
        'server sign'
    );

    $result = $apiClient->request->orderRefund([
        'TICKET' => "UWyNLGVh000012669958czZpckkboKNDpUysDhlL",
        'AMOUNT' => 4000,
        'ORDER_ITEMS' => [
            [
                'num' => 1,
                'name' => 'Test Product 1',
                'quantity' => 1,
                'price' => 10,
                'fullPrice' => 10,
            ],
            [
                'num' => 2,
                'name' => 'Test Product 2',
                'quantity' => 2,
                'price' => 10,
                'fullPrice' => 20,
            ],
            [
                'num' => 3,
                'name' => 'Delivery',
                'quantity' => 1,
                'price' => 10,
                'fullPrice' => 10,
                'isService' => 1,
            ],
        ],
    ]);
    
    print_r($result);
} catch (\Exception $e) {
    if ($debug) {
        \Avangard\Lib\Logger::log($e);
    }
}
```

2. `orderCancel($ticket)` - отменяет ранее зарегистрированную, но ещё не оплаченную попытку оплаты. Этот
метод нужно вызывать, если по какой-то причине необходимо запретить пользователю оплату по заказу.
 
Параметры:
- `string $ticket` - уникальный идентификатор оплаты в интернет-эквайринге банка

Возвращаемое значение:  
`true`, если оплата была отменена
 
Пример:
```php
<?php
require_once "vendor/autoload.php";
use Avangard\ApiClient;

$debug = true;

try {
    $apiClient = new ApiClient(
        1,
        'shop password',
        'shop sign',
        'server sign'
    );
    
    $order = [
        'AMOUNT' => 1000,
        'ORDER_NUMBER' => '1',
        'ORDER_DESCRIPTION' => 'My desc',
        'BACK_URL' => 'https://example.ru/payments/avangard/?result=success'
    ];
    
    $registerResult = $apiClient->request->orderRegister($order);

    $cancelResult = $apiClient->request->orderCancel($registerResult['TICKET']);
    
    var_dump($cancelResult);
} catch (\Exception $e) {
    if ($debug) {
        \Avangard\Lib\Logger::log($e);
    }
}
```

## Операции по заказу

1. `getOpersByOrderNumber($order_number)` - получить список операций по номеру заказа в интернет-магазине

Параметры:
- `string $order_number` - номер заказа в интернет-магазине

Пример возвращаемого массива:
```php
[
    [
        'id' => 1054751,
        'ticket' => '1234567890ABCDEABCDE12345678901234567890',
        'order_number' => '1',
        'status_code' => 1,
        'status_desc' => 'Обрабатывается',
        'status_date' => '2013-08-14T10:23:49+04:00',
        'amount' => 10000.0,
    ],
    [
        'id' => 1054752,
        'ticket' => '1234567890ABCDEABCDE12345678901234567811',
        'order_number' => '1',
        'status_code' => 1,
        'status_desc' => 'Обрабатывается',
        'status_date' => '2013-08-14T10:24:00+04:00',
        'amount' => 10000.0,
    ],
    [
        'id' => 1054753,
        'ticket' => '1234567890ABCDEABCDE12345678901234567822',
        'order_number' => '1',
        'method_name' => 'CVV',
        'status_code' => 2,
        'status_desc' => 'Отбракован',
        'status_date' => '2013-08-14T10:27:17+04:00',
        'amount' => 10000.0,
        'refund_amount' => 10000.0,
        'card_num' => '411111******1111',
        'exp_mm' => 12,
        'exp_yy' => 15,
    ]
]
```
 
Пример:
```php
<?php
require_once "vendor/autoload.php";
use Avangard\ApiClient;

$debug = true;

try {
    $apiClient = new ApiClient(
        1,
        'shop password',
        'shop sign',
        'server sign'
    );

    $result = $new->request->getOpersByOrderNumber("1");
    
    print_r($result);
} catch (\Exception $e) {
    if ($debug) {
         \Avangard\Lib\Logger::log($e);
     }
}
```

2. `getTicketByOrderNumber($order_number)` - возвращает TICKET-параметр первой оплаченной операции по номеру заказа в интернет-магазине (может использоваться для возврата денежных средств)

Параметры:
- `string $order_number` - номер заказа в интернет-магазине

Возвращаемое значение:
```php
"UWyNLGVh000012669958czZpckkboKNDpUysDhlL"
```

Пример:
```php
<?php
require_once "vendor/autoload.php";
use Avangard\ApiClient;

$debug = true;

try {
    $apiClient = new ApiClient(
        1,
        'shop password',
        'shop sign',
        'server sign'
    );

    $result = $new->request->getTicketByOrderNumber("1");
    
    print_r($result);
} catch (\Exception $e) {
    if ($debug) {
         \Avangard\Lib\Logger::log($e);
     }
}
```

3. `getOpersByDate($date)` - получить список операций за определённую дату.

Параметры:
 - `string $date` - дата
 
Возвращаемое значение:  
Возвращаемый массив полностью аналогичен методу `getOpersByOrderNumber`
 
Пример:
```php
<?php
require_once "vendor/autoload.php";
use Avangard\ApiClient;

$debug = true;

try {
    $apiClient = new ApiClient(
        1,
        'shop password',
        'shop sign',
        'server sign'
    );

    $result = $apiClient->request->getOpersByDate("2019-11-06");
    
    print_r($result);
} catch (\Exception $e) {
    if ($debug) {
         \Avangard\Lib\Logger::log($e);
     }
}
```
