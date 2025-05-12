<?php
/**
 * Copyright Bykovskiy Maxim. Avangard (c) 2019.
 */

namespace Avangard\Methods;

use Avangard\Lib\Convertor;
use Avangard\Lib\ArrayToXml;
use Avangard\Lib\Fiscalization;
use DOMException;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Trait Refunds
 * @package Avangard\Methods
 */
trait Refunds
{
    /**
     * Send refund by ticket
     *
     * @param (array) $params
     * - TICKET (string, require) ticket заказа в системе банка
     * todo проверить, в копейках или нет
     * - AMOUNT (float) сумма к возврату, может отсутствовать. Должна быть меньше или равна сумме заказа
     *      Если AMOUNT равен сумме заказа или отсутствует, то будет произведён полный возврат.
     *      Если AMOUNT меньше суммы заказа, то будет произведён частичный возврат
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
     * @return array
     * @throws DOMException|GuzzleException
     */
    public function orderRefund($params)
    {
        if (empty($params['TICKET'])) {
            throw new \InvalidArgumentException(
                'orderRefund: ticket not found'
            );
        }

        if (!empty($params['ORDER_ITEMS'])) {
            try {
                Fiscalization::checkOrderItems([
                    'items' => $params['ORDER_ITEMS'],
                    'amount' => $params['AMOUNT'],
                ]);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException(
                    'orderRefund: ' . $e->getMessage()
                );
            }

            $params['ORDER_ITEMS'] = Fiscalization::prepareOrderItems($params['ORDER_ITEMS']);
        }

        $request = array_merge($this->getOrderAccess(), $params);

        $xml = ArrayToXml::convert($request, 'REVERSE_ORDER', false, "UTF-8");

        $url = 'https://pay.avangard.ru/iacq/h2h/reverse_order';

        $result = $this->net_client->request('POST', $url, ['body' => 'xml=' . $xml, 'headers' => ['Content-Type' => 'application/x-www-form-urlencoded;charset=utf-8']]);

        $status = $result->getStatusCode();

        if ($status != 200) {
            throw new \InvalidArgumentException(
                "orderRefund: incorrect http code: " . $status, $status
            );
        }

        $response = $result->getBody()->getContents();

        error_reporting(1); 
        $resultObject = Convertor::covertToArray($response);
        error_reporting(E_ALL);

        if (!isset($resultObject['response_code'])) {
            throw new \InvalidArgumentException(
                "orderRefund: error in xml data"
            );
        }

        if (isset($resultObject['rev_id'])) {
            $maxRequestsCount = 8;
            $secondsBetweenRequests = 5;

            for ($i = 0; $i < $maxRequestsCount; $i++) {
                sleep($secondsBetweenRequests);

                if ($this->getRefundStatus($resultObject['rev_id']))
                    break;
            }
        }

        if ($status == 200 && $resultObject['response_code'] == 0) {
            return ['transaction_id' => $resultObject['id']];
        }

        throw new \InvalidArgumentException(
            "orderRefund: error in PS: " . $resultObject['response_message'], $resultObject['response_code']
        );
    }

    /**
     * Send cancel of order by ticket
     *
     * @param $ticket
     * @return bool
     * @throws DOMException|GuzzleException
     */
    public function orderCancel($ticket)
    {
        $request = array_merge($this->getOrderAccess(), ['ticket' => $ticket]);

        $xml = ArrayToXml::convert($request, 'cancel_order', false, "UTF-8");

        $url = 'https://pay.avangard.ru/iacq/h2h/cancel_order';

        $result = $this->net_client->request('POST', $url, ['body' => 'xml=' . $xml, 'headers' => ['Content-Type' => 'application/x-www-form-urlencoded;charset=utf-8']]);

        $status = $result->getStatusCode();

        if ($status != 200) {
            throw new \InvalidArgumentException(
                "orderCancel: incorrect http code: " . $status, $status
            );
        }

        $response = $result->getBody()->getContents();

        error_reporting(1);
        $resultObject = Convertor::covertToArray($response);
        error_reporting(E_ALL);

        if (!isset($resultObject['response_code'])) {
            throw new \InvalidArgumentException(
                "orderCancel: error in xml data"
            );
        }

        if ($status == 200 && $resultObject['response_code'] == 0) {
            return true;
        }

        throw new \InvalidArgumentException(
            "orderCancel: error in PS: " . $resultObject['response_message'], $resultObject['response_code']
        );
    }

    /**
     * Checks the status of an earlier refund request
     *
     * @param int $rev_id
     * @return bool
     * @throws DOMException|GuzzleException
     */
    public function getRefundStatus($rev_id)
    {
        $params = compact('rev_id');

        $request = array_merge($this->getOrderAccess(), $params);

        $xml = ArrayToXml::convert($request, 'reverse_status', false, "UTF-8");

        $url = 'https://pay.avangard.ru/iacq/h2h/reverse_status';

        $result = $this->net_client->request('POST', $url, ['body' => 'xml=' . $xml, 'headers' => ['Content-Type' => 'application/x-www-form-urlencoded;charset=utf-8']]);

        $status = $result->getStatusCode();

        if ($status != 200) {
            throw new \InvalidArgumentException(
                "getRefundStatus: incorrect http code: " . $status, $status
            );
        }

        $response = $result->getBody()->getContents();

        error_reporting(1);
        $resultObject = Convertor::covertToArray($response);
        error_reporting(E_ALL);

        if (!isset($resultObject['status_id'])) {
            throw new \InvalidArgumentException(
                "getRefundStatus: error in xml data"
            );
        }

        if ($status == 200) {
            switch ($resultObject['status_id']) {
                case 0:
                    return false;
                case 1:
                    return true;
            }
        }

        throw new \InvalidArgumentException(
            "getRefundStatus: error in PS: " . $resultObject['status_desc'], $resultObject['status_id']
        );
    }
}
