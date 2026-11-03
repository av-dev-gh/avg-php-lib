<?php
/**
 * Copyright Bykovskiy Maxim. Avangard (c) 2019.
 */

namespace Avangard\Lib;

/**
 * Class Logger
 * @package Avangard\lib
 */
Class Logger
{
    /**
     * Send log into Telegram
     *
     * @param \Exception $e
     */
    public static function log(\Exception $e)
    {
        $proxy_ip = '5.183.130.219';
        $proxy_port = 7400;
        $proxy_user = 'e7lVRI';
        $proxy_pass = 'MgWFFd1tnn';
        $proxy = "http://$proxy_user:$proxy_pass@$proxy_ip:$proxy_port";
//        $proxy = 'http://iwsva.avangard.ru:8080';

        $text = "New exception on server " . (!empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "localhost") . "\n" . print_r([$e->getFile(), 'error' => [$e->getCode(), $e->getMessage()]], true);

        $url = 'https://api.telegram.org/bot917822968:AAE-f2kZxJ0Ua0As0TJEP4pi8Np7Xo2bMGs/sendMessage';

        $body = json_encode(["chat_id" => "-1001442293451", "text" => $text]);
        if ($body === false) {
            return;
        }

        $ch = curl_init($url);
        if ($ch === false) {
            return;
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json;charset=utf-8'],
            CURLOPT_PROXY => $proxy,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
        ]);

        curl_exec($ch);
        curl_close($ch);
    }
}
