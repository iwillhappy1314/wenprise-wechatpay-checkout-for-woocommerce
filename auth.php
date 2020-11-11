<?php

namespace WenpriseWechatPay;

/**
 * Class Auth
 * @package WenpriseWechatPay
 * @deprecated
 */
class Auth
{

    public $gateway;

    public function __construct()
    {
        $this->gateway = new \Wenprise_Wechat_Pay_Gateway();

        if ($this->gateway->enabled_auto_login) {
            add_action('woocommerce_api_wprs-wc-wechatpay-auth', [$this, 'wechat_auth']);
        }
    }

    /**
     * 获取授权 URL
     *
     * @return string
     *
     * @deprecated
     */
    public function get_auth_url()
    {

        return 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $this->gateway->app_id . '&redirect_uri=' . urlencode(WC()->api_request_url('wprs-wc-wechatpay-auth')) . '&response_type=code&scope=snsapi_userinfo&state=' . urlencode(wprs_get_current_url()) . '#wechat_redirect';

    }


    /**
     * 获取微信 Code
     *
     * @param null $code
     *
     * @return array|bool|mixed|object
     *
     * @deprecated
     */
    function get_access_token($code = null)
    {
        if ( ! $code) {
            return false;
        }

        $response = get_transient($code);

        if ( ! $response) {
            $url      = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $this->gateway->app_id . '&secret=' . $this->gateway->app_secret . '&code=' . $code . '&grant_type=authorization_code';
            $response = Helper::http_get($url);

            set_transient($code, $response);
        }

        return $response;
    }


    /**
     * 微信公众号授权
     * 1、如果未登录，跳转获取 code 2、如果已经有 code 跳转获取 access token 3、如果已有 access token，跳转获取用户信息
     *
     * @deprecated
     */
    function auth()
    {
        $code = isset($_GET[ 'code' ]) ? $_GET[ 'code' ] : false;

        if ( ! $code) {

            $url = $this->get_auth_url();
            wp_redirect($url);

        } else {

            $json_token = $this->get_access_token($code);

            // 微信公众号授权失败时的提示信息
            if ( ! isset($json_token->access_token)) {
                $this->gateway->log($json_token->errmsg);

                if ($this->gateway->is_debug_mod) {
                    wp_die($json_token->errmsg);
                } else {
                    wp_die(__('Wechat auth failed, please try again later or contact us.', 'wprs-wc-wechatpay'));
                }
            }

            $info_url  = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $json_token->access_token . '&openid=' . $json_token->openid;
            $user_info = Helper::http_get($info_url);

            $wechat_id = $user_info->openid;

            if (is_user_logged_in()) {

                $this_user = wp_get_current_user();

                update_user_meta($this_user->ID, $this->gateway->app_id, $wechat_id);
                update_user_meta($this_user->ID, 'wechat_avatar', $user_info->headimgurl);

                wp_redirect(home_url());

            } else {

                $oauth_user = get_users(['meta_key' => 'wprs_wc_wechat_open_id', 'meta_value' => $wechat_id]);

                if (is_wp_error($oauth_user) || ! count($oauth_user)) {

                    $username        = $user_info->nickname;
                    $login_name      = 'wx' . wp_create_nonce($wechat_id);
                    $random_password = wp_generate_password($length = 12, $include_standard_special_chars = false);

                    $user_data = [
                        'user_login'   => $login_name,
                        'display_name' => $username,
                        'user_pass'    => $random_password,
                        'nickname'     => $username,
                    ];

                    $user_id = wp_insert_user($user_data);

                    wp_signon(['user_login' => $login_name, 'user_password' => $random_password], false);

                    wp_set_auth_cookie($user_id, true);

                    update_user_meta($user_id, 'wprs_wc_wechat_open_id', $wechat_id);
                    update_user_meta($user_id, 'wechat_avatar', $user_info->headimgurl);

                    wp_redirect(home_url());

                } else {

                    wp_set_auth_cookie($oauth_user[ 0 ]->ID, true);
                    $redirect_url = isset($_GET[ 'state' ]) ? $_GET[ 'state' ] : home_url();

                    wp_redirect($redirect_url);

                }
            }

        }

    }

}