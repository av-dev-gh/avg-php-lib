<?php
/**
 * Copyright Bykovskiy Maxim. Avangard (c) 2019.
 */

namespace Avangard;

use Avangard\Api\ApiVersion5;

/**
 * Class ApiClient
 * @package Avangard
 */
class ApiClient
{
    /**
     * Contain object of selected class version api
     *
     * @var ApiVersion5
     */
    public $request;

    /**
     * Type of connection to PS
     */
    const HOST2HOST = 1;
    /**
     * Type of connection to PS
     */
    const POSTFORM = 2;
    /**
     * Type of connection to PS
     */
    const GETURL = 3;

    /**
     * ApiClient constructor.
     *
     * @param $shopId
     * @param $shopPassword
     * @param $shopSign
     * @param $serverSign
     * @param string $proxy
     */
    public function __construct($shopId, $shopPassword, $shopSign, $serverSign, $sendBills = false, $proxy = null)
    {
        $this->request = new ApiVersion5($shopId, $shopPassword, $shopSign, $serverSign, $sendBills, $proxy);
    }

    /**
     * Get library version
     *
     * @return string
     */
    public static function getVersion()
    {
        $ver = '4.0.0';
        return "Library version $ver. Avangard (c) 2025.";
    }
}
