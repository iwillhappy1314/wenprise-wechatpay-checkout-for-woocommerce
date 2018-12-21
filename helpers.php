<?php

if ( ! function_exists('wprs_is_wechat')) {
    /**
     * 判断是否在微信中打开
     */
    function wprs_is_wechat()
    {
        if ( ! empty($_SERVER[ 'HTTP_USER_AGENT' ]) && strpos($_SERVER[ 'HTTP_USER_AGENT' ], 'MicroMessenger') !== false) {
            return true;
        }

        return false;
    }
}



if ( ! function_exists('wprs_get_ip')) {
    /**
     * 获取用户的真实 IP
     *
     * @return mixed
     */
    function wprs_get_ip()
    {
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
}



if ( ! function_exists('wprs_get_current_url')) {
    /**
     * 获取当前 URL
     *
     * @return bool|string
     */
    function wprs_get_current_url()
    {
        $url = false;

        if (isset($_SERVER[ 'SERVER_ADDR' ])) {
            $is_https   = isset($_SERVER[ 'HTTPS' ]) && 'on' == $_SERVER[ 'HTTPS' ];
            $protocol   = 'http' . ($is_https ? 's' : '');
            $host       = isset($_SERVER[ 'HTTP_HOST' ])
                ? $_SERVER[ 'HTTP_HOST' ]
                : $_SERVER[ 'SERVER_ADDR' ];
            $port       = $_SERVER[ 'SERVER_PORT' ];
            $path_query = $_SERVER[ 'REQUEST_URI' ];

            $url = sprintf('%s://%s%s%s',
                $protocol,
                $host,
                $is_https
                    ? (443 != $port ? ':' . $port : '')
                    : (80 != $port ? ':' . $port : ''),
                $path_query
            );
        }

        return $url;
    }
}