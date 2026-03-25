<?php
namespace Avangard\Lib;

trait Request
{
    /***
     * @param $url
     * @param $xml
     * @return Response
     */
    protected function h2h($url, $xml)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'xml=' . $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/x-www-form-urlencoded;charset=utf-8']);
        $result = curl_exec($ch);
        $response = new Response($result, curl_getinfo($ch, CURLINFO_HTTP_CODE), curl_error($ch));
        curl_close($ch);

        return $response;
    }
}