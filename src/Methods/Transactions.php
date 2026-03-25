<?php
/**
 * Copyright Bykovskiy Maxim. Avangard (c) 2019.
 */

namespace Avangard\Methods;

use Avangard\Lib\Convertor;
use Avangard\Lib\ArrayToXml;

/**
 * Trait Transactions
 * @package Avangard\Methods
 */
trait Transactions
{
    /**
     * Get all opers by order number
     *
     * @param $order_number
     * @return array|mixed
     * @throws \DOMException
     */
    public function getOpersByOrderNumber($order_number)
    {
        $request = array_merge($this->getOrderAccess(), ['order_number' => $order_number]);

        $xml = ArrayToXml::convert($request, 'get_opers_list', false, "UTF-8");

        $url = $this->getRequestUrl() . '/h2h/get_opers_list';

        $result = $this->h2h($url, $xml);

        $status = $result->getStatusCode();

        if($status != 200) {
            throw new \InvalidArgumentException(
                "getOpersByOrderNumber: incorrect http code: " . $status, $status
            );
        }

        $response = $result->getBody();

        error_reporting(1);
        $resultObject = Convertor::covertToArray($response);
        error_reporting(E_ALL);

        if(!isset($resultObject['response_code'])) {
            throw new \InvalidArgumentException(
                "getOpersByOrderNumber: error in xml data"
            );
        }

        if($status == 200 && $resultObject['response_code'] == 0) {
            return (!empty($resultObject['oper_info']) ? (!empty($resultObject['oper_info'][0]) ? $resultObject['oper_info'] : [$resultObject['oper_info']]) : []);
        }

        throw new \InvalidArgumentException(
            "getOpersByOrderNumber: error in PS: " . $resultObject['response_message'], $resultObject['response_code']
        );
    }

    /**
     * Get ticket of first paid operation by order number
     *
     * @throws \DOMException
     */
    public function getTicketByOrderNumber($order_number)
    {
        $opersData = $this->getOpersByOrderNumber($order_number);

        if (is_array($opersData)) {
            foreach ($opersData as $operation) {
                if ($operation["status_code"] == 3) {
                    return $operation["ticket"];
                }
            }
        } else {
            if ($opersData["status_code"] == 3) {
                return $opersData["ticket"];
            }
        }

        return null;
    }

    /**
     * Get all opers behind one day
     *
     * @param $date
     * @return array|mixed
     * @throws \DOMException
     */
    public function getOpersByDate($date)
    {
        $date = date("d.m.Y", strtotime($date));

        $request = array_merge($this->getOrderAccess(), ['date' => $date]);

        $xml = ArrayToXml::convert($request, 'get_opers_by_date', false, "UTF-8");

        $url = $this->getRequestUrl() . '/h2h/get_opers_by_date';

        $result = $this->h2h($url, $xml);

        $status = $result->getStatusCode();

        if($status != 200) {
            throw new \InvalidArgumentException(
                "getOpersByDate: incorrect http code: " . $status, $status
            );
        }

        $response = $result->getBody();

        error_reporting(1);
        $resultObject = Convertor::covertToArray($response);
        error_reporting(E_ALL);

        if(!isset($resultObject['response_code'])) {
            throw new \InvalidArgumentException(
                "getOpersByDate: error in xml data"
            );
        }

        if($status == 200 && $resultObject['response_code'] == 0) {
            return (!empty($resultObject['oper_info']) ? (!empty($resultObject['oper_info'][0]) ? $resultObject['oper_info'] : [$resultObject['oper_info']]) : []);
        }

        throw new \InvalidArgumentException(
            "getOpersByDate: error in PS: " . $resultObject['response_message'], $resultObject['response_code']
        );
    }
}
