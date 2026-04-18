<?php
// Common.php

namespace app\common\utils;

class Common
{
    const APP_KEY = 'appKey';
    const APP_SECRET = 'appSecret';
    const NONCE = 'nonce';
    const TIMESTAMP = 'timestamp';
    const SERVICE_ID = 'serviceId';
    const SIGN = 'sign';

    const requestId = 'requestId';
    const mobile = 'mobile';
    const name = 'name';
    const amount = 'amount';
    const identity = 'identity';
    const bankAccount = 'bankAccount';
    const dateTime = 'dateTime';
    const salaryType = 'salaryType';
    const extRemark = 'extRemark';
    



    public static function getSign($paramMap, $appSecret)
    {
        $text = self::getUrlText($paramMap);
        $text .= "&appSecret=" . $appSecret;
        return strtoupper(md5($text));
    }

    private static function getUrlText($beanMap)
    {
        $beanMap = self::getSortedMap($beanMap);
        $builder = '';
        foreach ($beanMap as $key => $value) {
            if (is_string($value)) {
                $value = $value;
            } else {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            }
            $builder .= $key . '=' . $value . '&';
        }
        return substr($builder, 0, -1);
    }

    private static function getSortedMap($paramMap)
    {
        ksort($paramMap);
        $map = [];
        foreach ($paramMap as $key => $value) {
            if (!empty($key) && $key !== self::APP_SECRET && !empty($value)) {
                $map[$key] = $value;
            }
        }
        return $map;
    }

    // public static function doPost($url, $paramMap)
    // {
    //     $client = new \GuzzleHttp\Client();
    //     try {
    //         $response = $client->post($url, [
    //             'form_params' => $paramMap
    //         ]);
    //         return $response->getBody()->getContents();
    //     } catch (\Exception $e) {
    //         return $e->getMessage();
    //     }
    // }

    public static function doPost($url, $paramMap)
    {
        $postData = http_build_query($paramMap);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $error = 'Curl error: ' . curl_error($ch);
            curl_close($ch);
            return $error;
        }

        curl_close($ch);
        return $response;
    }
    
}
