<?php
define('ROOT',dirname(__FILE__).DIRECTORY_SEPARATOR);
define('CONFIG',require_once ROOT."config.php");
require_once ROOT."Rsa.php";
require_once ROOT."Des.php";

class  HttpUtlis
{



   private function getReqId(){
       return date("YmdHis").''.rand(0,99).''.rand(0,99);
   }

   public function sendToYouFu($requestMessage){
     if(!is_string($requestMessage)){
         $requestMessage = json_encode($requestMessage);
     }
     $host = array('Content-Type: application/json; charset=utf-8','Content-Length:'.strlen($requestMessage));
     $this->log('发起请求'.CONFIG['YOU_FU_URL']);
     $data = $this->post_url(CONFIG['YOU_FU_URL'],$requestMessage,  $host,  '','', -1,  false,  'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1)');
     if(empty($data)){
         $this->log('【HTTP响应结果处理】返回结果为空');
         return false;
     }
    return $this->handleResponse($data);
   }
    public function handleResponse($responseMessage) {
     $this->log('HTTP响应结果处理 开始:'.$responseMessage);
     $data  = json_decode($responseMessage,true);
     if($data['resCode'] != '0000'){
            $this->log($data);
            $this->log("系统处理异常");
            return false ;
    }
   //签约接口如果没传otherparams那个参数，同步接口成功 resdata是空的这种情况就不需要验签了
      if(!empty($data['resData'])){
          /** SHA1_WITH_RSA验签 **/
          $verstatus = Rsa::verify($data['resData'],$data['sign'],CONFIG['YOU_FU_PUBLIC_KEY']);
          if(!$verstatus){
              $this->log("验签失败!");
              return false;
          }


        /** DES 解密 **/
       $reqData =  Des::decrypt( $data['resData'],CONFIG['INTER_KEY']);
       if(empty($reqData)){
           $this->log("解密失败");
           return false;
       }
        $data['resData'] = json_decode($reqData,true);
      }
       return json_encode($data);
    }
    public  function buildRequest($funCodeEnum,$requestDTO){
       $this->log($requestDTO);
       $requestMessage = [];
       $requestMessage['reqId'] = $this->getReqId();
       $requestMessage['merId'] = CONFIG['MER_ID'];
       $requestMessage['version'] = CONFIG['API_VERSION'];
       $requestMessage['funCode'] = $funCodeEnum;
       $jsonReq = json_encode($requestDTO);
       $bs =  Des::encrypt( $jsonReq , CONFIG['INTER_KEY']);
       $requestMessage['reqData'] = $bs;
       $sign =  Rsa::genSign($bs, CONFIG['MER_PRIVATE_KEY']);
       $requestMessage['sign'] = $sign;
       $this->log('【构建请求报文】'.json_encode($requestMessage));
       return $requestMessage;
    }


    private function post_url($url, $post = '', $host = '', $referrer = '', $cookie = '', $proxy = -1, $sock = false, $useragent = 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1)')
    {
        if (empty($post) && empty($host) && empty($referrer) && empty($cookie) && ($proxy == -1 || empty($proxy)) && empty($useragent)) return @file_get_contents($url);
        $method = empty($post) ? 'GET' : 'POST';

        if (function_exists('curl_init') && empty($cookie)) {
            $ch = @curl_init();
            @curl_setopt($ch, CURLOPT_URL, $url);
            if ($proxy != -1 && !empty($proxy)) @curl_setopt($ch, CURLOPT_PROXY, 'http://' . $proxy);
            @curl_setopt($ch, CURLOPT_REFERER, $referrer);
            @curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
            @curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIE_FILE_PATH);
            @curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE_PATH);
            @curl_setopt($ch, CURLOPT_HEADER, 0);
            @curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            @curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
            @curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            @curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

            if(is_array($host) && !is_string($host)){
                @curl_setopt($ch, CURLOPT_HTTPHEADER, $host);
                unset($host);
            }
            if ($method == 'POST') {
                @curl_setopt($ch, CURLOPT_POST, 1);
                @curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            }

            $result = curl_exec($ch);
            @curl_close($ch);
        }

        if ($result === false && function_exists('file_get_contents')) {
            $urls = parse_url($url);
            if (empty($host)) $host = $urls['host'];
            $httpheader = $method . " " . $url . " HTTP/1.1\r\n";
            $httpheader .= "Accept: */*\r\n";
            $httpheader .= "Accept-Language: zh-cn\r\n";
            $httpheader .= "Referer: " . $referrer . "\r\n";
            if ($method == 'POST') $httpheader .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $httpheader .= "User-Agent: " . $useragent . "\r\n";
            $httpheader .= "Host: " . $host . "\r\n";
            if ($method == 'POST') $httpheader .= "Content-Length: " . strlen($post) . "\r\n";
            $httpheader .= "Connection: Keep-Alive\r\n";
            $httpheader .= "Cookie: " . $cookie . "\r\n";

            $opts = array(
                'http' => array(
                    'method' => $method,
                    'header' => $httpheader,
                    'timeout' => 60,
                    'content' => ($method == 'POST' ? $post : '')
                )
            );
            if ($proxy != -1 && !empty($proxy)) {
                $opts['http']['proxy'] = 'tcp://' . $proxy;
                $opts['http']['request_fulluri'] = true;
            }
            $context = @stream_context_create($opts);
            $result = @file_get_contents($url, 'r', $context);
        }

        if ($sock && $result === false && function_exists('fsockopen')) {
            $urls = parse_url($url);
            if (empty($host)) $host = $urls['host'];
            $port = empty($urls['port']) ? 80 : $urls['port'];

            $httpheader = $method . " " . $url . " HTTP/1.1\r\n";
            $httpheader .= "Accept: */*\r\n";
            $httpheader .= "Accept-Language: zh-cn\r\n";
            $httpheader .= "Referer: " . $referrer . "\r\n";
            if ($method == 'POST') $httpheader .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $httpheader .= "User-Agent: " . $useragent . "\r\n";
            $httpheader .= "Host: " . $host . "\r\n";
            if ($method == 'POST') $httpheader .= "Content-Length: " . strlen($post) . "\r\n";
            $httpheader .= "Connection: Keep-Alive\r\n";
            $httpheader .= "Cookie: " . $cookie . "\r\n";
            $httpheader .= "\r\n";
            if ($method == 'POST') $httpheader .= $post;
            $fd = false;
            if ($proxy != -1 && !empty($proxy)) {
                $proxys = explode(':', $proxy);
                $fd = @fsockopen($proxys[0], $proxys[1]);
            } else {
                $fd = @fsockopen($host, $port);
            }
            @fwrite($fd, $httpheader);
            @stream_set_timeout($fd, 60);
            $result = '';
            while (!@feof($fd)) {
                $result .= @fread($fd, 8192);
            }
            @fclose($fd);
        }

        return $result;
    }
    private function log($log){
       $logContent = '';
       if(is_string($log)){
           $logContent = '【优付】'.$log;
       }else{
           $logContent = '【优付】'.json_encode($log, JSON_UNESCAPED_UNICODE);
       }
       file_put_contents('/tmp/youfu_api_debug.log', date('Y-m-d H:i:s') . ' - ' . $logContent . "\n", FILE_APPEND);
    }
}