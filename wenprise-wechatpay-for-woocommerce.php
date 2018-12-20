<?php

/**
 * Plugin Name: Wenprise WeChatPay Checkout For WooCommerce
 * Plugin URI: https://www.wpzhiku.com
 * Description: Wenprise WeChatPay Checkout For WooCommerce
 * Version: 1.0.1
 * Author: WenPrice Co., Ltd
 * Author URI: https://www.wpzhiku.com
 * Text Domain: wprs-woo-wechatpay
 * Domain Path: /languages
 */

namespace WizhiWooCommercePayment\WechatPay;

if ( ! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('WENPRISE_WECHATPAY_FILE_PATH', __FILE__);
define('WENPRISE_WECHATPAY_PATH', plugin_dir_path(__FILE__));
define('WENPRISE_WECHATPAY_URL', plugin_dir_url(__FILE__));
define('WENPRISE_WECHATPAY_WOOCOMMERCE_ID', 'wprs-woo-wechatpay');
define('WENPRISE_WECHATPAY_ASSETS_URL', WENPRISE_WECHATPAY_URL . 'assets/');

add_action('plugins_loaded', function ()
{
    if ( ! class_exists('WC_Payment_Gateway')) {
        return;
    }

    load_plugin_textdomain('wprs-woo-wechatpay', false, dirname(plugin_basename(__FILE__)) . '/languages');

    require WENPRISE_WECHATPAY_PATH . 'vendor/autoload.php';
    require WENPRISE_WECHATPAY_PATH . 'class-checkout.php';

    add_filter('woocommerce_payment_gateways', function ($methods)
    {
        $methods[] = 'Wenprise_Wechat_Pay_Gateway';

        return $methods;
    });
}, 0);
