<?php

//namespace app\common\controller;

/**
 * DES加密类
 *
 * 本类用于实现des算法的加密及解密
 */
class Des
{
    //des加密（ecb模式）
    public static function encrypt($data,$key)
    {
        if (is_array($data)){
            ksort($data);
            $data = json_encode($data);
        }
        $data = self::pkcs5Pad($data, 8);
        $sign = openssl_encrypt($data, 'DES-ECB', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);
        $sign = base64_encode($sign);
        return $sign;
    }
    //des解密（ECB模式）
    public static function decrypt($data,$key)
    {
        $sign = openssl_decrypt(base64_decode($data), 'DES-ECB', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);
        $sign = self::pkcs5Unpad($sign);
        return $sign;
    }

    public static function hex2bin($hexData)
    {
        $binData = "";
        for ($i = 0; $i < strlen($hexData); $i += 2) {
            $binData .= chr(hexdec(substr($hexData, $i, 2)));
        }
        return $binData;
    }

    public static function pkcs5Pad($text, $blocksize)
    {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    public static function pkcs5Unpad($text)
    {
        $pad = ord($text[strlen($text) - 1]);
        if ($pad > strlen($text))
            return false;
        if (strspn($text, chr($pad), strlen($text) - $pad ) != $pad){
            return false;
        }
        return substr($text,0,-1*$pad);
    }
}



