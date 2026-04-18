<?php

//namespace app\common\controller;

/**
 * Rsa加密类
 *
 * 本类用于实现Rsa算法的加密及解密
 */
class Rsa{
    /**
     * 初始化配置
     * RsaService constructor.
     */
    public function __construct()
    {
    }

    /**
     * 生成 sha1WithRSA 签名
     * @param string $toSign 数据
     * @param string $privateKey 私钥
     * @return string
     */
    public static function genSign($toSign, $privateKey){
        $privateKey = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($privateKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
        $key = openssl_get_privatekey($privateKey);
        openssl_sign($toSign, $signature, $key);
        openssl_free_key($key);
        $sign = base64_encode($signature);
        return $sign;
    }

    /**
     * 私钥加密
     * @param $data
     * @param $private_key
     * @return string
     */
    public static function privateEncrypt($data,$private_key)
    {
        if (is_array($data)){
            ksort($data);
            $data = json_encode($data);
        }
        $pem = chunk_split(trim($private_key), 64, PHP_EOL);
        $pem = "-----BEGIN RSA PRIVATE KEY-----".PHP_EOL . $pem . "-----END RSA PRIVATE KEY-----";
        $privateKey = openssl_pkey_get_private($pem);
        $ciphertext = '';
        $data = str_split($data, 117); // 加密的数据长度限制为比密钥长度少11位，如128位的密钥最多加密的数据长度为117
        foreach ($data as $d) {
            openssl_private_encrypt($d, $crypted, $privateKey); // OPENSSL_PKCS1_PADDING
            $ciphertext .= $crypted;
        }
        return base64_encode($ciphertext);
    }

    /**
     * 私钥解密
     * @param $data
     * @param $private_key
     * @param bool $unserialize
     * @return mixed
     * @throws \Exception
     */
    public static function privateDecrypt($data,$private_key)
    {
        $pem = chunk_split(trim($private_key), 64, "\n");
        $pem = "-----BEGIN RSA PRIVATE KEY-----\n" . $pem . "-----END RSA PRIVATE KEY-----";
        $privateKey = openssl_pkey_get_private($pem);
        $crypto = '';
        foreach (str_split(base64_decode($data), 128) as $chunk) {
            openssl_private_decrypt($chunk, $decryptData, $privateKey);
            $crypto .= $decryptData;
        }
        if ($crypto === false) {
            throw new \Exception('Could not decrypt the data.');
        }
        return json_decode($crypto,true);
    }
    public static function urlSafeBase64decode($string){
        $data = str_replace(array('-','_'), array('+','/'), $string);
        $mod4 = strlen($data) % 4;
        if($mod4){
            $data .= substr('====', $mod4);
        }
        return base64_decode($data);
    }
   public static function verify($content,$toSign,$privateKey){
       $privateKey = "-----BEGIN PUBLIC KEY-----\n" .
           wordwrap($privateKey, 64, "\n", true) .
           "\n-----END PUBLIC KEY-----";
       $key = openssl_pkey_get_public($privateKey);
       $rs = openssl_verify($content,base64_decode($toSign),$key);
       openssl_free_key($key);
       return $rs;

   }



    /**
     * 公钥加密
     * @param $data
     * @param $public_key
     * @param bool $serialize 是为了不管你传的是字符串还是数组，都能转成字符串
     * @return string
     * @throws \Exception
     */
    public static function publicEncrypt($data,$public_key)
    {
        $pem = chunk_split(trim($public_key), 64, "\n");
        $pem = "-----BEGIN PUBLIC KEY-----\n" . $pem . "-----END PUBLIC KEY-----";
        $publicKey = openssl_pkey_get_public($pem);
        $ciphertext = '';
        if (is_array($data)){
            ksort($data);
            $data = json_encode($data);
        }
        // 加密的数据长度限制为比密钥长度少11位，如128位的密钥最多加密的数据长度为117
        $data = str_split($data, 117);
        foreach ($data as $d) {
            openssl_public_encrypt($d, $crypted, $publicKey); // OPENSSL_PKCS1_PADDING
            $ciphertext .= $crypted;
        }
        if ($ciphertext === false) {
            throw new \Exception('Could not encrypt the data.');
        }
        openssl_free_key($publicKey);
        return base64_encode($ciphertext);
    }


    /**
     * 公钥解密
     * @param $data
     * @param $public_key
     * @return mixed
     * @throws \Exception
     */
    public static function publicDecrypt($data, $public_key)
    {
        $pem = chunk_split(trim($public_key), 64, PHP_EOL);
        $pem = "-----BEGIN PUBLIC KEY-----" .PHP_EOL. $pem . "-----END PUBLIC KEY-----";
        $pem = openssl_pkey_get_public($pem);
        $dataArray = str_split(base64_decode($data), 128);
        $decrypted = '';
        foreach($dataArray as $subData){
            $subDecrypted = null;
            openssl_public_decrypt($subData,$subDecrypted,$pem);
            $decrypted .= $subDecrypted;
        }
        return $decrypted;
    }
}
