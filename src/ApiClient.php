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
     * Payment type - by bank card
     */
    const PAYMENT_TYPE_CARD = 'payment_card';

    /**
     * Payment type - by QR-code (SBP)
     */
    const PAYMENT_TYPE_QR = 'payment_qr';

    /**
     * Payment type - all available
     */
    const PAYMENT_TYPE_ALL = 'payment_all';

    /**
     * ApiClient constructor.
     *
     * @param string $shopId
     * @param string $shopPassword
     * @param string $shopSign
     * @param string $serverSign
     * @param bool $test_mode
     * @param string $proxy
     */
    public function __construct($shopId, $shopPassword, $shopSign, $serverSign, $test_mode = false, $proxy = null)
    {
        $this->request = new ApiVersion5($shopId, $shopPassword, $shopSign, $serverSign, $test_mode, $proxy);
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
