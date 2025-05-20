<?php
/**
 * Copyright Bykovskiy Maxim. Avangard (c) 2019.
 */

namespace Avangard\Api;

use Avangard\Methods;
use Box\DataObjects\BaseAuth;

/**
 * Class ApiVersion5
 *
 * @package Avangard\Api
 */
class ApiVersion5 extends AbstractLoader
{
    /**
     * ApiVersion5 constructor.
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
        parent::__construct($shop_id, $shop_password, $shop_sign, $server_sign, $test_mode, $proxy);
    }

    use Methods\Orders;
    use Methods\Transactions;
    use Methods\Refunds;
    use Methods\Sale;
}