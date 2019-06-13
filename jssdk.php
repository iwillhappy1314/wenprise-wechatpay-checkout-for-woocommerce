<?php

class JSSDK
{
    /**
     * @var
     */
    private $appId;

    /**
     * @var
     */
    private $appSecret;


    /**
     * JSSDK constructor.
     *
     * @param $appId
     * @param $appSecret
     */
    public function __construct($appId, $appSecret)
    {
        $this->appId     = $appId;
        $this->appSecret = $appSecret;
    }


    /**
     * 创建 nonce 字符串
     *
     * @param int $length
     *
     * @return string
     */
    private function createNonceStr($length = 16)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str   = "";

        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }

        return $str;
    }


    /**
     * 获取 js API 票据
     *
     * @return mixed
     */
    private function getJsApiTicket()
    {
        // jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例
        $data = get_option('wprs-wc-wechat-jsapi_ticket');

        if (isset($data->expire_time) && $data->expire_time < time()) {

            $accessToken = $this->getAccessToken();

            // 如果是企业号用以下 URL 获取 ticket
            // $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";
            $url    = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
            $res    = json_decode(wp_remote_retrieve_body(wp_remote_post($url)));
            $ticket = $res->ticket;

            if ($ticket) {
                $data->expire_time  = time() + 7000;
                $data->jsapi_ticket = $ticket;
                update_option('wprs-wc-wechat-jsapi_ticket', $data);
            }

        } else {

            $ticket = isset($data->jsapi_ticket) ? $data->jsapi_ticket : '';

        }

        return $ticket;
    }


    /**
     *  获取访问令牌
     *
     * @return mixed
     */
    private function getAccessToken()
    {
        // access_token 应该全局存储与更新，以下代码以写入到文件中做示例
        $data = get_option('wprs-wc-wechat-access_token');

        if ($data->expire_time < time()) {

            // 如果是企业号用以下URL获取access_token
            // $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=$this->appId&corpsecret=$this->appSecret";
            $url          = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
            $res          = json_decode(wp_remote_retrieve_body(wp_remote_post($url)));
            $access_token = $res->access_token;

            if ($access_token) {
                $data->expire_time  = time() + 7000;
                $data->access_token = $access_token;
                update_option('wprs-wc-wechat-access_token', $data);
            }

        } else {

            $access_token = $data->access_token;

        }

        return $access_token;
    }


    /**
     * 获取签名包
     *
     * @return array
     */
    public function getSignPackage()
    {
        $jsapiTicket = $this->getJsApiTicket();

        // 注意 URL 一定要动态获取，不能 hardcode.
        $protocol = ( ! empty($_SERVER[ 'HTTPS' ]) && $_SERVER[ 'HTTPS' ] !== 'off' || $_SERVER[ 'SERVER_PORT' ] == 443) ? "https://" : "http://";
        $url      = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

        $timestamp = time();
        $nonceStr  = $this->createNonceStr();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        $signature = sha1($string);

        $signPackage = [
            "appId"     => $this->appId,
            "nonceStr"  => $nonceStr,
            "timestamp" => $timestamp,
            "url"       => $url,
            "signature" => $signature,
            "rawString" => $string,
        ];

        return $signPackage;
    }

}

