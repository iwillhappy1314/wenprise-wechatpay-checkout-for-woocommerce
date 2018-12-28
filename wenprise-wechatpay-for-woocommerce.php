<?php
/**
 * Plugin Name: Wenprise WeChatPay Checkout For WooCommerce
 * Plugin URI: https://www.wpzhiku.com
 * Description: Wenprise WeChatPay Checkout For WooCommerce
 * Version: 1.0.1
 * Author: WenPrise Co., Ltd
 * Author URI: https://www.wpzhiku.com
 * Text Domain: wprs-wc-wechatpay
 * Domain Path: /languages
 */

if ( ! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('WENPRISE_WECHATPAY_FILE_PATH', __FILE__);
define('WENPRISE_WECHATPAY_PATH', plugin_dir_path(__FILE__));
define('WENPRISE_WECHATPAY_URL', plugin_dir_url(__FILE__));
define('WENPRISE_WECHATPAY_WOOCOMMERCE_ID', 'wprs-wc-wechatpay');
define('WENPRISE_WECHATPAY_ASSETS_URL', WENPRISE_WECHATPAY_URL . 'assets/');


add_action('plugins_loaded', function ()
{
    if ( ! class_exists('WC_Payment_Gateway')) {
        return;
    }

    require WENPRISE_WECHATPAY_PATH . 'vendor/autoload.php';
    require WENPRISE_WECHATPAY_PATH . 'helpers.php';
    require WENPRISE_WECHATPAY_PATH . 'class-checkout.php';

    load_plugin_textdomain('wprs-wc-wechatpay', false, dirname(plugin_basename(__FILE__)) . '/languages');

    add_action('woocommerce_receipt_wprs-wc-wechatpay', [new Wenprise_Wechat_Pay_Gateway(), 'receipt_page']);

    add_filter('woocommerce_payment_gateways', function ($methods)
    {
        $methods[] = 'Wenprise_Wechat_Pay_Gateway';

        return $methods;
    });

    Puc_v4_Factory::buildUpdateChecker(
        'https://api.wpcio.com/api/plugin/info/wenprise-wechatpay-for-woocommerce',
        __FILE__,
        'wenprise-wechatpay-for-woocommerce'
    );
}, 0);


/**
 * 在微信中打开时，自动登录，以便获取微信 Open ID, 实现公众号 JS API 支付
 */
add_action('init', function ()
{
    if (wprs_is_wechat() && ! is_user_logged_in()) {
        $Gateway = new Wenprise_Wechat_Pay_Gateway();

        $Gateway->wechat_auth();
    }
});


/**
 * 如果订单支付页面是从微信 H5 支付跳转回来的，设置正在处理中的订单也可以继续支付，以便页面可以继续查询订单状态，验证支付结果。
 */
add_filter('woocommerce_valid_order_statuses_for_payment', function ($status, $instance)
{
    $form = isset($_GET[ 'from' ]) ? $_GET[ 'from' ] : false;

    $status_addon = [];
    if ($form == 'wap') {
        $status_addon = ['processing'];
    }

    return array_merge($status, $status_addon);
}, 10, 2);


