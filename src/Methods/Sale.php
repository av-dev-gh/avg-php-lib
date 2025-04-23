<?php
/**
 * Copyright Bykovskiy Maxim. Avangard (c) 2019.
 */

namespace Avangard\Methods;

use Avangard\ApiClient;

/**
 * Trait Sale
 * @package Avangard\Methods
 */
trait Sale
{
    /**
     * Validate response from PS
     *
     * @param array $params
     * @return bool
     */
    public function isCorrectHash($params = array())
    {
        if (empty($params['order_number']) ||
            empty($params['amount']) ||
            empty($params['signature'])) {
            return false;
        }

        $signature = strtoupper(
            MD5(
                strtoupper(
                    MD5(
                        $this->server_sign
                    ) .
                    MD5(
                        $this->shop_id .
                        $params['order_number'] .
                        $params['amount']
                    )
                )
            )
        );

        return $signature == $params['signature'];
    }
}