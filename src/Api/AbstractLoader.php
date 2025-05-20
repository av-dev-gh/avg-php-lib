<?php
/**
 * Copyright Bykovskiy Maxim. Avangard (c) 2019.
 */

namespace Avangard\Api;

use Box\DataObjects\BaseAuth;
use Box\BaseBox;
use Box\BoxFactory;
use GuzzleHttp\Client;

/**
 * Class AbstractLoader
 *
 * @package Avangard\Api
 */
abstract class AbstractLoader
{
    /**
     * API url of test environment
     */
    const REQUEST_URL_TEST = 'https://pay.avangard.ru/iacq_test3';

    /**
     * API url of production environment
     */
    const REQUEST_URL_PRODUCTION = 'https://pay.avangard.ru/iacq';

    /**
     * Object of Guzzle client
     *
     * @var Client
     */
    protected $net_client;

    /**
     * Bank shop id
     *
     * @var string
     */
    protected $shop_id;

    /**
     * Bank shop password
     *
     * @var string
     */
    protected $shop_password;

    /**
     * Bank shop signature
     *
     * @var string
     */
    protected $shop_sign;

    /**
     * Bank server signature
     *
     * @var string
     */
    protected $server_sign;

    /**
     * Mode of API: test or production
     *
     * @var bool
     */
    protected $test_mode;

    /**
     * AbstractLoader constructor.
     *
     * @param $shop_id
     * @param $shop_password
     * @param $shop_sign
     * @param $server_sign
     * @param $test_mode
     * @param $proxy
     */
    public function __construct($shop_id, $shop_password, $shop_sign, $server_sign, $test_mode, $proxy)
    {
        if (empty($shop_id) ||
            empty($shop_password) ||
            empty($shop_sign) ||
            empty($server_sign)
            ) {
            throw new \InvalidArgumentException(
                "Incorrect arguments in abstract constructor."
            );
        }

        $this->net_client = new Client((!empty($proxy) ? ['proxy' => $proxy, 'http_errors' => false, 'verify' => false] : ['http_errors' => false, 'verify' => false]));
        $this->shop_id = (string)$shop_id;
        $this->shop_password = (string)$shop_password;
        $this->shop_sign = (string)$shop_sign;
        $this->server_sign = (string)$server_sign;
        $this->test_mode = (bool)$test_mode;
    }

    /**
     * Returns API URL
     *
     * @return string
     */
    protected function getRequestUrl()
    {
        return $this->test_mode ? self::REQUEST_URL_TEST : self::REQUEST_URL_PRODUCTION;
    }

    /**
     * Get auth data to bank
     *
     * @return array
     */
    protected function getOrderAccess()
    {
        return [
            "SHOP_ID" => $this->shop_id,
            'SHOP_PASSWD' => $this->shop_password
        ];
    }

    /**
     * Send http header
     */
    public function sendResponse()
    {
        header("HTTP/1.1 202 Accepted");
        die();
    }
}