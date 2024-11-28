<?php

namespace Wenprise\Wechatpay;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

class Init {

	public function __construct() {
		add_filter( 'plugin_action_links_' . WENPRISE_WECHATPAY_BASE_FILE, [ $this, 'add_settings_link' ] );
		add_filter( 'option_trp_advanced_settings', [ $this, 'ignore_translate_strings' ], 10, 2 );

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		add_filter( 'wprs_wc_wechat_open_id', [ $this, 'xh_login_integrate' ], 12 );
		add_filter( 'wprs_wc_wechat_open_id', [ $this, 'wenprise_security_integrate' ], 12 );

		add_action( 'woocommerce_blocks_loaded', [ $this, 'add_block_support' ] );
		add_action( 'before_woocommerce_init', [ $this, 'add_custom_table_support' ] );

		add_filter( 'woocommerce_valid_order_statuses_for_payment', [ $this, 'modify_order_status_for_wap' ], 10, 2 );
	}


	function enqueue_scripts() {
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return;
		}

		if ( ( is_checkout() || is_checkout_pay_page() ) && wp_is_mobile() && ! Helpers::is_wechat() ) {
			$version = Constants::get_constant( 'WC_VERSION' );
			$suffix  = Constants::is_true( 'SCRIPT_DEBUG' ) ? '' : '.min';

			wp_register_script( 'qrcode', WC()->plugin_url() . '/assets/js/jquery-qrcode/jquery.qrcode' . $suffix . '.js', [ 'jquery' ], $version );
			wp_enqueue_script( 'wprs-wc-wechatpay-scripts', WENPRISE_WECHATPAY_URL . '/frontend/script.js', [ 'jquery', 'jquery-blockui', 'qrcode' ], WENPRISE_WECHATPAY_VERSION, true );

			wp_localize_script( 'wprs-wc-wechatpay-scripts', 'WpWooWechatData', [
				'query_url' => WC()->api_request_url( 'wprs-wc-wechatpay-query' ),
			] );
		}
	}


	/**
	 * 兼容讯虎微信登录插件
	 *
	 * @return string
	 */
	function xh_login_integrate($open_id) {
		global $wpdb;
		$user_id = get_current_user_id();
		$table_name = $wpdb->prefix . 'xh_social_channel_wechat';

		if(function_exists('xh_social_loginbar')){
			$wechat_login = $wpdb->get_row( "SELECT mp_openid FROM $table_name WHERE user_id = $user_id" );

			if ( ! is_wp_error( $wechat_login ) && $wechat_login ) {
				$open_id = $wechat_login->mp_openid;
			}
		}

		return $open_id;
	}


	/**
	 * 兼容 Wenprise Security 登录插件
	 *
	 * @return bool|string
	 */
	function wenprise_security_integrate($open_id) {
		if ( class_exists( \WenpriseSecurity\Models\OpenAuth::class ) ) {
			$auth = new \WenpriseSecurity\Models\OpenAuth();

			if(method_exists( $auth, 'get_open_id' ) ) {
				$open_id = $auth->get_open_id( 'wechat' );
			}

			if(method_exists( $auth, 'get_openid_by_user_id' ) ) {
				$open_id = $auth->get_openid_by_user_id( 'wechat' );
			}
		}

		return $open_id;
	}


	/**
	 * 避免 TranslatePress 插件翻译签名字符串
	 */
	function ignore_translate_strings( $options ) {
		$options[ 'exclude_gettext_strings' ][ 'string' ][] = 'Pay for order %1$s at %2$s';
		$options[ 'exclude_gettext_strings' ][ 'domain' ][] = 'wprs-wc-wechatpay';

		return $options;
	}


	/**
	 * 插件插件设置链接
	 */
	function add_settings_link( $links ) {
		$url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wprs-wc-wechatpay' );
		$url = '<a href="' . esc_url( $url ) . '">' . __( 'Settings', 'wprs-wc-wechatpay' ) . '</a>';
		array_unshift( $links, $url );

		return $links;
	}

	/**
	 * Registers WooCommerce Blocks integration.
	 */
	function add_block_support() {
		if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				static function ( PaymentMethodRegistry $payment_method_registry )
				{
					$payment_method_registry->register( new BlockSupport() );
				}
			);
		}
	}


	function add_custom_table_support() {
		if ( class_exists( FeaturesUtil::class ) ) {
			FeaturesUtil::declare_compatibility( 'custom_order_tables', WENPRISE_WECHATPAY_FILE_PATH );
		}
	}


	/**
	 * 如果订单支付页面是从微信 H5 支付跳转回来的，设置正在处理中的订单也可以继续支付，以便页面可以继续查询订单状态，验证支付结果。
	 */
	function modify_order_status_for_wap( $status, $instance ) {
		$from = Helpers::data_get( $_GET, 'from', false );

		$status_addon = [];
		if ( $from === 'wap' ) {
			$status_addon = [ 'processing' ];
		}

		return array_merge( $status, $status_addon );
	}

}