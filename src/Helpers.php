<?php

namespace Wenprise\Wechatpay;

class Helpers
{

    /**
     * 判断是否在微信中打开
     */
    public static function is_wechat(): bool
    {
        return ! empty($_SERVER[ 'HTTP_USER_AGENT' ]) && strpos($_SERVER[ 'HTTP_USER_AGENT' ], 'MicroMessenger') !== false;
    }


    /**
     * 判断客户端是否为小程序
     *
     * @return bool
     */
    public static function is_mini_app(): bool
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
	 * 获取用户 IP 地址。
	 *
	 * @return string
	 */
	public static function get_client_ip(): string
	{
		if ( class_exists( '\WC_Geolocation' ) ) {
			return \WC_Geolocation::get_ip_address();
		}

		$remote_ip = $_SERVER[ 'REMOTE_ADDR' ] ?? '';

		return filter_var( $remote_ip, FILTER_VALIDATE_IP ) ? $remote_ip : '0.0.0.0';
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
            $host       = $_SERVER[ 'HTTP_HOST' ] ?? $_SERVER[ 'SERVER_ADDR' ];
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
