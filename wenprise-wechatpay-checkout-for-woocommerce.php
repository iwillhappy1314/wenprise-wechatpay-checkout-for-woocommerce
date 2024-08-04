<?php
/**
 * Plugin Name: Wenprise WeChatPay Payment Gateway For WooCommerce
 * Plugin URI: https://www.wpzhiku.com/wenprise-wechatpay-payment-gateway-for-woocommerce
 * Description: Wenprise WeChatPay Payment Gateway For WooCommerce， WooCommerce 全功能微信支付网关
 * Version: 2.0.2
 * Author: WordPress智库
 * Author URI: https://www.wpzhiku.com
 * Text Domain: wprs-wc-wechatpay
 * Domain Path: /languages
 * Requires PHP: 7.2
 * Requires at least: 4.7
 * Tested up to: 6.6
 * WC requires at least: 3.6
 * WC tested up to: 9.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( PHP_VERSION_ID < 70200 ) {
	// 显示警告信息
	if ( is_admin() ) {
		add_action( 'admin_notices', function ()
		{
			printf( '<div class="error"><p>' . __( 'Wenprise WeChatPay Payment Gateway For WooCommerce 需要 PHP %1$s 以上版本才能运行，您当前的 PHP 版本为 %2$s， 请升级到 PHP 到 %1$s 或更新的版本， 否则插件没有任何作用。',
					'wprs' ) . '</p></div>',
				'7.2.0', PHP_VERSION );
		} );
	}

	return;
}

define('WENPRISE_WECHATPAY_BASE_FILE', plugin_basename( __FILE__ ));
define( 'WENPRISE_WECHATPAY_PATH', plugin_dir_path( __FILE__ ) );
define( 'WENPRISE_WECHATPAY_URL', plugin_dir_url( __FILE__ ) );

const WENPRISE_WECHATPAY_FILE_PATH   = __FILE__;
const WENPRISE_WECHATPAY_VERSION   = '2.0.1';
const WENPRISE_WECHATPAY_WOOCOMMERCE_ID = 'wprs-wc-wechatpay';
const WENPRISE_WECHATPAY_ASSETS_URL     = WENPRISE_WECHATPAY_URL . 'frontend/';

add_action( 'plugins_loaded', function ()
{
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	require WENPRISE_WECHATPAY_PATH . 'vendor/autoload.php';

	load_plugin_textdomain( 'wprs-wc-wechatpay', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	add_action( 'woocommerce_receipt_wprs-wc-wechatpay', [ new \Wenprise\Wechatpay\PaymentGateway(), 'receipt_page' ] );

	add_filter( 'woocommerce_payment_gateways', function ( $methods )
	{
		$methods[] = '\\Wenprise\\Wechatpay\\PaymentGateway';

		return $methods;
	} );

	new \Wenprise\Wechatpay\Init();

}, 0 );