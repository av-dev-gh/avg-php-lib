<?php
/**
 * Copyright Bykovskiy Maxim. Avangard (c) 2019.
 */

namespace Avangard\Methods;

use Avangard\ApiClient;
use Avangard\Lib\Convertor;
use Avangard\Lib\ArrayToXml;

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
     * @throws \DOMException
     */
    public function getOrderByTicket($ticket)
    {
        $request = array_merge($this->getOrderAccess(), ['ticket' => $ticket]);

        $xml = ArrayToXml::convert($request, 'get_order_info', false, "UTF-8");

        $url = 'https://pay.avangard.ru/iacq/h2h/get_order_info';

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
     * Generate form's field's
     *
     * @param $order
     * @param $type
     * @return array|string
     */
    public function prepareForms($order, $type)
    {
        switch ($type) {
            case ApiClient::HOST2HOST:
                $url = "https://pay.avangard.ru/iacq/pay";
                $method = "get";
                $inputs = $this->orderRegister($order);
                break;
            case ApiClient::POSTFORM:
                $url = "https://pay.avangard.ru/iacq/post";
                $method = "post";
                $this->setOrder($order);
                $this->checkAndPrepareOrder();
                $inputs = $this->getOrder();
                $inputs['SIGNATURE'] = $this->signRequest();
                unset($inputs['SHOP_PASSWD']);
                break;
            case ApiClient::GETURL:
                $inputs = $this->orderRegister($order);
                return 'https://pay.avangard.ru/iacq/pay?' . http_build_query(['ticket' => $inputs['TICKET']]);
            default:
                throw new \InvalidArgumentException(
                    "prepareForms: incorrect request type"
                );
        }

        $inputs = array_change_key_case($inputs);

        return [
            "URL" => $url,
            "METHOD" => $method,
            "INPUTS" => $inputs
        ];
    }

    /**
     * Register order in PS
     *
     * @param $order
     * @return array
     * @throws \DOMException
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

        $url = 'https://pay.avangard.ru/iacq/h2h/reg';

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
     * Prepare order to request
     *
     * @param $order
     * @return array
     */
    protected function prepareOrder($order)
    {
        if (empty($order['LANGUAGE'])) {
            $order['LANGUAGE'] = 'RU';
        }

        if (!empty($order['ORDER_ITEMS'])) {
            for ($i = 0; $i < count($order['ORDER_ITEMS']); $i++) {
                $order['ORDER_ITEMS'][$i]['num'] = $i + 1;
            }

            $order['ORDER_ITEMS'] = json_encode($order['ORDER_ITEMS']);
        }

        return array_merge($this->getOrderAccess(), $order);
    }

    /**
     * Validate order
     *
     * @param (array) $order
     * $order exist:
     * - AMOUNT (number, require) сумма к оплате в копейках
     * - ORDER_NUMBER (string, require) номер заказа в магазине
     * - ORDER_DESCRIPTION (string, require) описание заказа в магазине
     * - LANGUAGE (string, require, default 'RU') описание заказа в магазине
     * - BACK_URL (string, require) ссылка безусловного редиректа
     * - BACK_URL_OK (string) ссылка успешного редиректа
     * - BACK_URL_FAIL (string) ссылка НЕуспешного редиректа
     * - CLIENT_NAME (string) имя плательщика
     * - CLIENT_ADDRESS (string) физический адрес плательщика
     * - CLIENT_EMAIL (string, require, если включена отправка чеков) email плательщика
     * - CLIENT_PHONE (string) телефон плательщика
     * - CLIENT_IP (string) ip-адрес плательщика
     * - ORDER_ITEMS (array) можно передавать при настроенной фискализации для формирования позиций в чеке
     *      Имеет следующий вид:
     *          [
     *              [
     *                  num - позиция в чеке (заполнять не нужно, проставляется автоматически в методе prepareOrder)
     *                  name - наименование товара
     *                  quantity - количество товара
     *                  price - цена за единицу товара в рублях
     *                  fullPrice - итоговая цена позиции
     *              ],
     *              ...
     *          ]
     *      Сумма всех товаров в ORDER_ITEMS должна равняться полю AMOUNT / 100
     */
    protected function checkAndPrepareOrder()
    {
        $order = $this->getOrder();

        if (!empty($order['ORDER_ITEMS'])) {
            $orderItemsReqParams = [
                'name' => 'STRING',
                'quantity' => 'NUMERIC',
                'price' => 'NUMERIC',
                'fullPrice' => 'NUMERIC',
            ];

            $orderItemsTotal = 0;
            foreach ($order['ORDER_ITEMS'] as $item) {
                foreach ($orderItemsReqParams as $key => $type) {
                    if (empty($item[$key])) {
                        throw new \InvalidArgumentException(
                            'checkOrder: error in validation: order item key ' . $key . ' not found'
                        );
                    }
                }

                $orderItemsTotal += $item['fullPrice'];
            }

            $orderAmount = $order['AMOUNT'] / 100;
            if ($orderItemsTotal != $orderAmount) {
                throw new \InvalidArgumentException(
                    "checkOrder: error in validation: total items price $orderItemsTotal not equal to order amount $orderAmount"
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

        if ($this->isSendBills()) {
            $arrayOfReq['CLIENT_EMAIL'] = 'STRING';
        }

        foreach ($arrayOfReq as $key => $type) {
            if (empty($order[$key])) {
                throw new \InvalidArgumentException(
                    'checkOrder: error in validation: key ' . $key . ' not found'
                );
            }
        }

        $this->setOrder($order);
    }
}
