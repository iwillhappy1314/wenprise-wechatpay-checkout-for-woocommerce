<?php

namespace WenpriseWechatPay;

class Helper
{

    /**
     * 判断是否在微信中打开
     */
    public static function is_wechat()
    {
        return ! empty($_SERVER[ 'HTTP_USER_AGENT' ]) && strpos($_SERVER[ 'HTTP_USER_AGENT' ], 'MicroMessenger') !== false;
    }


    /**
     * 判断客户端是否为小程序
     *
     * @return bool
     */
    public static function is_mini_app()
    {
        return ! empty($_SERVER[ 'HTTP_USER_AGENT' ]) && (strpos($_SERVER[ 'HTTP_USER_AGENT' ], 'miniProgram') !== false || strpos($_SERVER[ 'HTTP_USER_AGENT' ], 'miniprogramhtmlwebview') !== false);
    }


    public static function data_value($value)
    {
        return $value instanceof \Closure ? $value() : $value;
    }


    public static function data_get($array, ?string $key = null, $default = null)
    {
        if (is_null($key)) {
            return $array;
        }

        $array = (array)$array;

        if (isset($array[ $key ])) {
            return $array[ $key ];
        }

        foreach (explode('.', $key) as $segment) {
            if ( ! is_array($array) || ! array_key_exists($segment, $array)) {
                return self::data_value($default);
            }

            $array = $array[ $segment ];
        }

        return $array;
    }

    /**
     * 获取用户的真实 IP
     *
     * @return mixed
     */
    public static function get_client_ip()
    {
        if (isset($_SERVER[ 'HTTP_CF_CONNECTING_IP' ])) {
            $_SERVER[ 'REMOTE_ADDR' ]    = $_SERVER[ 'HTTP_CF_CONNECTING_IP' ];
            $_SERVER[ 'HTTP_CLIENT_IP' ] = $_SERVER[ 'HTTP_CF_CONNECTING_IP' ];
        }

        $client  = @$_SERVER[ 'HTTP_CLIENT_IP' ];
        $forward = @$_SERVER[ 'HTTP_X_FORWARDED_FOR' ];
        $remote  = $_SERVER[ 'REMOTE_ADDR' ];

        if (filter_var($client, FILTER_VALIDATE_IP)) {
            $ip = $client;
        } elseif (filter_var($forward, FILTER_VALIDATE_IP)) {
            $ip = $forward;
        } else {
            $ip = $remote;
        }

        return $ip;
    }


    /**
     * 获取当前 URL
     *
     * @return bool|string
     */
    public static function get_current_url()
    {
        $url = false;

        if (isset($_SERVER[ 'SERVER_ADDR' ])) {
            $is_https   = isset($_SERVER[ 'HTTPS' ]) && 'on' === $_SERVER[ 'HTTPS' ];
            $protocol   = 'http' . ($is_https ? 's' : '');
            $host       = isset($_SERVER[ 'HTTP_HOST' ]) ? $_SERVER[ 'HTTP_HOST' ] : $_SERVER[ 'SERVER_ADDR' ];
            $port       = $_SERVER[ 'SERVER_PORT' ];
            $path_query = $_SERVER[ 'REQUEST_URI' ];

            $url = sprintf('%s://%s%s%s',
                $protocol,
                $host,
                $is_https ? (443 != $port ? ':' . $port : '') : (80 != $port ? ':' . $port : ''),
                $path_query
            );
        }

        return $url;
    }


    /**
     * 获取远程内容，如果失败，报错，如果成功，返回 decode 后的对象
     *
     * @param $url
     *
     * @return array|mixed|object
     */
    public static function remote_get($url)
    {
        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            wp_die(__('request failed, please try again', 'wprs-wc-wechatpay'));
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

}