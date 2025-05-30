<?php
/**
 * Copyright Bykovskiy Maxim. Avangard (c) 2019.
 */

namespace Avangard\Methods;

use Avangard\ApiClient;
use Avangard\Lib\Convertor;
use Avangard\Lib\ArrayToXml;
use DOMException;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Trait Orders (register order into bank's payment system (PS))
 * @package Avangard\Methods
 */
trait Orders
{
    /**
     * Object of order
     *
     * @var
     */
    protected $order;

    /**
     * Set order's object
     *
     * @param $order
     */
    protected function setOrder($order)
    {
        if (empty($order)) {
            throw new \InvalidArgumentException(
                'setOrder: order is empty'
            );
        }

        $this->order = $order;
    }

    /**
     * Get order's object
     *
     * @return mixed
     */
    protected function getOrder()
    {
        if (empty($this->order)) {
            throw new \InvalidArgumentException(
                'getOrder: order is empty'
            );
        }

        return $this->order;
    }

    /**
     * Generate signature for order's xml
     *
     * @return string
     */
    protected function signRequest()
    {
        $order = $this->getOrder();

        return strtoupper(
            MD5(
                strtoupper(
                    MD5($this->shop_sign) .
                    MD5(
                        $this->shop_id .
                        $order['ORDER_NUMBER'] .
                        ($order['AMOUNT'])
                    )
                )
            )
        );
    }

    /**
     * Return order by ticket from PS
     *
     * @param $ticket
     * @return mixed[]
     * @throws DOMException
     */
    public function getOrderByTicket($ticket)
    {
        $request = array_merge($this->getOrderAccess(), ['ticket' => $ticket]);

        $xml = ArrayToXml::convert($request, 'get_order_info', false, "UTF-8");

        $url = $this->getRequestUrl() . '/h2h/get_order_info';

        $result = $this->net_client->request('POST', $url, ['body' => 'xml=' . $xml, 'headers' => ['Content-Type' => 'application/x-www-form-urlencoded;charset=utf-8']]);

        $status = $result->getStatusCode();

        if ($status != 200) {
            throw new \InvalidArgumentException(
                "getOrderByTicket: incorrect http code: " . $status, $status
            );
        }

        $response = $result->getBody()->getContents();

        error_reporting(1); 
        $resultObject = Convertor::covertToArray($response);
        error_reporting(E_ALL); 

        if (!isset($resultObject['response_code'])) {
            throw new \InvalidArgumentException(
                "getOrderByTicket: error in xml data"
            );
        }

        if ($status == 200 && $resultObject['response_code'] == 0) {
            unset($resultObject['response_code']);
            unset($resultObject['response_message']);
            unset($resultObject['@root']);
            return $resultObject;
        }

        throw new \InvalidArgumentException(
            "getOrderByTicket: error in PS: " . $resultObject['response_message'], $resultObject['response_code']
        );
    }

    /**
     * Generate form fields
     *
     * @param array $params
     * - ORDER - (array, require) параметры заказа (см. checkAndPrepareOrder())
     * - REQUEST_TYPE - (ApiClient::HOST2HOST, ApiClient::POSTFORM, ApiClient::GETURL) тип запроса. По умолчанию ApiClient::POSTFORM
     * - PAYMENT_TYPE - (ApiClient::PAYMENT_TYPE_CARD, ApiClient::PAYMENT_TYPE_QR, ApiClient::PAYMENT_TYPE_ALL) способ оплаты. По карте, по QR или оба типа. По умолчанию ApiClient::PAYMENT_TYPE_CARD
     *
     * @return array|string
     * @throws DOMException|GuzzleException
     */
    public function prepareForms($params)
    {
        if (empty($params['ORDER'])) {
            throw new \InvalidArgumentException(
                "prepareForms: order is empty"
            );
        }

        $order = $params['ORDER'];

        $requestType = !empty($params['REQUEST_TYPE']) ? $params['REQUEST_TYPE'] : ApiClient::POSTFORM;
        $paymentType = !empty($params['PAYMENT_TYPE']) ? $params['PAYMENT_TYPE'] : ApiClient::PAYMENT_TYPE_CARD;

        switch ($requestType) {
            case ApiClient::HOST2HOST:
                $result = [
                    'URL' => $this->getRequestUrl() . "/pay",
                    'METHOD' => "get",
                ];

                if ($this->isPaymentTypeCard($paymentType)) {
                    $result['INPUTS'] = array_change_key_case($this->orderRegister($order));
                }

                if ($this->isPaymentTypeQr($paymentType)) {
                    $result['INPUTS_QR'] = array_change_key_case(
                        $this->orderRegister(array_merge($order, ['IS_QR' => 1]))
                    );
                }

                return $result;


            case ApiClient::POSTFORM:
                $result = [
                    'URL' => $this->getRequestUrl() . "/post",
                    'METHOD' => "post",
                ];

                if ($this->isPaymentTypeCard($paymentType)) {
                    $result['INPUTS'] = array_change_key_case($this->preparePostFormInputs($order));
                }

                if ($this->isPaymentTypeQr($paymentType)) {
                    $result['INPUTS_QR'] = array_change_key_case(
                        $this->preparePostFormInputs(array_merge($order, ['IS_QR' => 1]))
                    );
                }

                return $result;


            case ApiClient::GETURL:
                $inputs = $this->orderRegister($order);
                return $this->getRequestUrl() . '/pay?' . http_build_query(['ticket' => $inputs['TICKET']]);

            default:
                throw new \InvalidArgumentException(
                    "prepareForms: incorrect request type"
                );
        }
    }

    protected function preparePostFormInputs($order)
    {
        $this->setOrder($order);
        $this->checkAndPrepareOrder();
        $inputs = $this->getOrder();
        $inputs['SIGNATURE'] = $this->signRequest();
        unset($inputs['SHOP_PASSWD']);

        return $inputs;
    }

    /**
     * Register order in PS
     *
     * @param $order
     * @return array
     * @throws DOMException|GuzzleException
     */
    public function orderRegister($order)
    {
        $this->setOrder($order);
        $this->checkAndPrepareOrder();
        $order = $this->getOrder();

        if(!empty($order['BACK_URL'])) {
            $order['BACK_URL'] = ['_cdata' => urlencode($order['BACK_URL'])];
        }
        if(!empty($order['BACK_URL_OK'])) {
            $order['BACK_URL_OK'] = ['_cdata' => urlencode($order['BACK_URL_OK'])];
        }
        if(!empty($order['BACK_URL_FAIL'])) {
            $order['BACK_URL_FAIL'] = ['_cdata' => urlencode($order['BACK_URL_FAIL'])];
        }

        $xml = ArrayToXml::convert($order, 'NEW_ORDER', false, "UTF-8");

        $url = $this->getRequestUrl() . '/h2h/reg';

        $result = $this->net_client->request('POST', $url, ['body' => 'xml=' . $xml, 'headers' => ['Content-Type' => 'application/x-www-form-urlencoded;charset=utf-8']]);

        $status = $result->getStatusCode();

        if ($status != 200) {
            throw new \InvalidArgumentException(
                "orderRegister: incorrect http code: " . $status, $status
            );
        }

        $response = $result->getBody()->getContents();

        error_reporting(1); 
        $resultObject = Convertor::covertToArray($response);
        error_reporting(E_ALL); 

        if (!isset($resultObject['response_code'])) {
            throw new \InvalidArgumentException(
                "orderRegister: error in xml data"
            );
        }

        if ($status == 200 && $resultObject['response_code'] == 0) {
            return [
                'TICKET' => $resultObject['ticket'],
            ];
        }

        throw new \InvalidArgumentException(
            "orderRegister: error in PS: " . $resultObject['response_message'], $resultObject['response_code']
        );
    }

    /**
     * Prepare order for request
     *
     * @param array $order
     * @return array
     */
    protected function prepareOrder($order)
    {
        if (empty($order['LANGUAGE'])) {
            $order['LANGUAGE'] = 'RU';
        }

        if (!empty($order['ORDER_ITEMS'])) {
            $order['ORDER_ITEMS'] = $this->prepareOrderItems($order['ORDER_ITEMS']);
        }

        return array_merge($this->getOrderAccess(), $order);
    }

    /**
     * Validate order
     *
     * @param (array) $order
     * $order exist:
     * - AMOUNT (number, require) сумма к оплате, в копейках
     * - ORDER_NUMBER (string, require) номер заказа в магазине
     * - ORDER_DESCRIPTION (string, require) описание заказа в магазине
     * - LANGUAGE (string, require, default 'RU') язык запроса
     * - BACK_URL (string, require) ссылка безусловного редиректа
     * - BACK_URL_OK (string) ссылка успешного редиректа
     * - BACK_URL_FAIL (string) ссылка НЕуспешного редиректа
     * - CLIENT_NAME (string) имя плательщика
     * - CLIENT_ADDRESS (string) физический адрес плательщика
     * - CLIENT_EMAIL (string) email плательщика
     * - CLIENT_PHONE (string) телефон плательщика
     *      Внимание! Если у вас настроена фискализация, то должно быть обязательно заполнено хотя бы одно поле - CLIENT_EMAIL или CLIENT_PHONE!
     * - CLIENT_IP (string) ip-адрес плательщика
     * - ORDER_ITEMS (array) можно передавать при настроенной фискализации для формирования позиций в чеке
     *      Имеет следующий вид:
     *          [
     *              [
     *                  num (number, require) - позиция в чеке
     *                  name (string, require) - наименование товара
     *                  quantity (number, require) - количество товара
     *                  price (number, require) - цена за единицу товара в рублях
     *                  fullPrice (number, require) - итоговая цена позиции
     *                  isService (0/1) - значение заполняется, если позиция в чеке является доставкой или иной услугой (необходимо для выставления правильного объекта расчёта)
     *              ],
     *              ...
     *          ]
     *      Сумма всех товаров в ORDER_ITEMS должна равняться полю AMOUNT / 100
     */
    protected function checkAndPrepareOrder()
    {
        $order = $this->getOrder();

        if (!empty($order['ORDER_ITEMS'])) {
            try {
                $this->checkOrderItems([
                    'items' => $order['ORDER_ITEMS'],
                    'amount' => $order['AMOUNT'],
                ]);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException(
                    'checkAndPrepareOrder: ' . $e->getMessage()
                );
            }
        }

        $order = $this->prepareOrder($order);

        $arrayOfReq = [
            'SHOP_ID' => "STRING",
            'SHOP_PASSWD' => 'STRING',
            'AMOUNT' => 'NUMERIC',
            'ORDER_NUMBER' => 'STRING',
            'ORDER_DESCRIPTION' => 'STRING',
            'LANGUAGE' => 'STRING',
            'BACK_URL' => 'URL',
        ];

        foreach ($arrayOfReq as $key => $type) {
            if (empty($order[$key])) {
                throw new \InvalidArgumentException(
                    'checkAndPrepareOrder: error in validation: key ' . $key . ' not found'
                );
            }
        }

        $this->setOrder($order);
    }

    /**
     * Validate order items and check order amount
     *
     * @param array $params
     * - amount (number) сумма к оплате, в копейках. Если не передан, то проверка суммы всех товаров с общей суммой заказа не будет проводиться
     * - items (array, require) список товаров
     * @return void
     */
    protected function checkOrderItems($params)
    {
        $orderItemsReqParams = [
            'num' => 'NUMERIC',
            'name' => 'STRING',
            'quantity' => 'NUMERIC',
            'price' => 'NUMERIC',
            'fullPrice' => 'NUMERIC',
        ];

        $orderItemsTotal = 0;
        foreach ($params['items'] as $item) {
            foreach ($orderItemsReqParams as $key => $type) {
                if (empty($item[$key])) {
                    throw new \InvalidArgumentException(
                        'error in validation: order item key ' . $key . ' not found'
                    );
                }

                if ((float)$item['price'] * (float)$item['quantity'] != (float)$item['fullPrice']) {
                    throw new \InvalidArgumentException(
                        'error in validation: item "' . $item['name'] . '" price * quantity not equal to item full price'
                    );
                }

                if (isset($item['isService']) && !in_array($item['isService'], [0, 1])) {
                    throw new \InvalidArgumentException(
                        'error in validation: order item key isService should be 0 or 1'
                    );
                }
            }

            $orderItemsTotal += $item['fullPrice'];
        }

        if (!empty($params['amount'])) {
            $orderAmount = $params['amount'] / 100;
            if ($orderItemsTotal != $orderAmount) {
                throw new \InvalidArgumentException(
                    "error in validation: total items price $orderItemsTotal not equal to order amount $orderAmount"
                );
            }
        }
    }

    /**
     * Prepare order items for request
     *
     * @param array $items
     * @return false|string
     */
    protected function prepareOrderItems($items)
    {
        return json_encode($items);
    }

    protected function isPaymentTypeCard($type)
    {
        return $type == ApiClient::PAYMENT_TYPE_CARD || $type == ApiClient::PAYMENT_TYPE_ALL;
    }

    protected function isPaymentTypeQr($type)
    {
        return $type == ApiClient::PAYMENT_TYPE_QR || $type == ApiClient::PAYMENT_TYPE_ALL;
    }
}
