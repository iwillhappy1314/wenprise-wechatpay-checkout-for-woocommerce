<?php
/**
 * Plugin Name: Wenprise WeChatPay Payment Gateway For WooCommerce
 * Plugin URI: https://www.wpzhiku.com/wenprise-wechatpay-payment-gateway-for-woocommerce
 * Description: Wenprise WeChatPay Payment Gateway For WooCommerce， WooCommerce 全功能微信支付网关
 * Version: 1.1.2
 * Author: WordPress智库
 * Author URI: https://www.wpzhiku.com
 * Text Domain: wprs-wc-wechatpay
 * Domain Path: /languages
 * Requires PHP: 7.1
 */

use WenpriseWechatPay\Helper;

if ( ! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (PHP_VERSION_ID < 70100) {
    // 显示警告信息
    if (is_admin()) {
        add_action('admin_notices', function ()
        {
            printf('<div class="error"><p>' . __('Wenprise WeChatPay Payment Gateway For WooCommerce 需要 PHP %1$s 以上版本才能运行，您当前的 PHP 版本为 %2$s， 请升级到 PHP 到 %1$s 或更新的版本， 否则插件没有任何作用。',
                    'wprs') . '</p></div>',
                '7.1.0', PHP_VERSION);
        });
    }

    return;
}

define('WENPRISE_WECHATPAY_PATH', plugin_dir_path(__FILE__));
define('WENPRISE_WECHATPAY_URL', plugin_dir_url(__FILE__));

const WENPRISE_WECHATPAY_VERSION = '1.0.10';
const WENPRISE_WECHATPAY_WOOCOMMERCE_ID = 'wprs-wc-wechatpay';
const WENPRISE_WECHATPAY_ASSETS_URL = WENPRISE_WECHATPAY_URL . 'frontend/';

require WENPRISE_WECHATPAY_PATH . 'helpers.php';

add_action('wp_enqueue_scripts', function ()
{
    if ( ! class_exists('WC_Payment_Gateway')) {
        return;
    }

    if ((is_checkout() || is_checkout_pay_page()) && wp_is_mobile() && ! Helper::is_wechat()) {
        wp_enqueue_script('wprs-wc-wechatpay-scripts', plugins_url('/frontend/script.js', __FILE__), ['jquery', 'jquery-blockui'], WENPRISE_WECHATPAY_VERSION, true);

        wp_localize_script('wprs-wc-wechatpay-scripts', 'WpWooWechatData', [
            'bridge_url' => WC()->api_request_url('wprs-wc-wechatpay-bridge'),
            'query_url'  => WC()->api_request_url('wprs-wc-wechatpay-query'),
        ]);
    }
});

add_action('plugins_loaded', function ()
{
    if ( ! class_exists('WC_Payment_Gateway')) {
        return;
    }

    require WENPRISE_WECHATPAY_PATH . 'vendor/autoload.php';
    require WENPRISE_WECHATPAY_PATH . 'class-checkout.php';

    load_plugin_textdomain('wprs-wc-wechatpay', false, dirname(plugin_basename(__FILE__)) . '/languages');

    add_action('woocommerce_receipt_wprs-wc-wechatpay', [new Wenprise_Wechat_Pay_Gateway(), 'receipt_page']);

    add_filter('woocommerce_payment_gateways', function ($methods)
    {
        $methods[] = 'Wenprise_Wechat_Pay_Gateway';

        return $methods;
    });

}, 0);


/**
 * 兼容迅虎登录插件
 */
add_filter('wprs_wc_wechat_open_id', function ($open_id)
{

    global $wpdb;
    $user_id = get_current_user_id();

    $wechat_login = $wpdb->get_row("SELECT mp_openid FROM {$wpdb->prefix}xh_social_channel_wechat WHERE user_id = $user_id");

    if ( ! is_wp_error($wechat_login) && $wechat_login) {
        $open_id = $wechat_login->mp_openid;
    }

    return $open_id;
});


/**
 * 兼容 Wenprise Security 登录插件
 */
add_filter('wprs_wc_wechat_open_id', static function ($open_id)
{
    if (class_exists(\WenpriseSecurity\Models\OpenAuth::class)) {
        $auth = new \WenpriseSecurity\Models\OpenAuth();

        $open_id = $auth->get_open_id('wechat');
    }

    return $open_id;
});


/**
 * 插件插件设置链接
 */
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links)
{
    $url = admin_url('admin.php?page=wc-settings&tab=checkout&section=wprs-wc-wechatpay');
    $url = '<a href="' . esc_url($url) . '">' . __('Settings', 'wprs-wc-wechatpay') . '</a>';
    array_unshift($links, $url);

    return $links;
});


/**
 * 如果订单支付页面是从微信 H5 支付跳转回来的，设置正在处理中的订单也可以继续支付，以便页面可以继续查询订单状态，验证支付结果。
 */
add_filter('woocommerce_valid_order_statuses_for_payment', function ($status, $instance)
{
    $from = Helper::data_get($_GET, 'from', false);

    $status_addon = [];
    if ($from === 'wap') {
        $status_addon = ['processing'];
    }

    return array_merge($status, $status_addon);
}, 10, 2);