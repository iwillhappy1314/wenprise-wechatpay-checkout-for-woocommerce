<?php

namespace Wenprise\Wechatpay;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use Automattic\Jetpack\Constants;
use Wenprise\Wechatpay\Omnipay\Omnipay;

/**
 * Gateway class
 */
class PaymentGateway extends \WC_Payment_Gateway {

	/** @var \WC_Logger Logger 实例 */
	public $log = false;

	/**
	 * @var string
	 */
	private $order_prefix = '';

	/**
	 * @var string
	 */
	public $app_id = '';

	/**
	 * @var string
	 */
	public $app_secret = '';

	/**
	 * @var string
	 */
	public $mini_app_id = '';

	/**
	 * @var string
	 */
	public $mini_app_secret = '';

	/**
	 * @var string
	 */
	public $mch_id = '';

	/**
	 * @var string
	 */
	public $api_key = '';

	/**
	 * @var string
	 */
	public $current_currency = '';

	/**
	 * @var bool
	 */
	public $multi_currency_enabled = false;

	/**
	 * @var string
	 */
	public $exchange_rate = '';

	/**
	 * @var string
	 */
	public $cert_path = '';

	/**
	 * @var string
	 */
	public $key_path = '';

	/**
	 * @var string
	 */
	public $is_debug_mod = false;

	/**
	 * @var string
	 */
	public $template = '';

	/**
	 * @var string
	 */
	public $qrcode_timeout = '120';

	/**
	 * 旧版自动登录设置，用于兼容已保存的网关配置。
	 *
	 * @var string
	 */
	public $enabled_auto_login = '';

	/**
	 * 网关支持的功能
	 *
	 * @var array
	 */
	public $supports = [ 'products', 'refunds' ];


	public function __construct() {

		// 支付方法的全局 ID
		$this->id = WENPRISE_WECHATPAY_WOOCOMMERCE_ID;

		// 支付网关页面显示的支付网关标题
		$this->method_title = __( 'WeChatPay Payment Gateway By Wenprise', 'wprs-wc-wechatpay' );

		// 支付网关设置页面显示的支付网关标题
		$this->method_description = __( 'WeChat Pay payment gateway for WooCommerce', 'wprs-wc-wechatpay' );

		// 被 init_settings() 加载的基础设置
		$this->init_form_fields();

		$this->init_settings();

		// 转换设置为变量以方便使用
		foreach ( $this->settings as $setting_key => $value ) {
			$this->$setting_key = $value;
		}

		// 前端显示的支付网关名称
		$this->title = $this->get_option( 'title' );

		// 支付网关标题
		$this->icon = apply_filters( 'omnipay_wechat_pay_icon', WENPRISE_WECHATPAY_ASSETS_URL . 'wechatpay.png' );

		$this->is_debug_mod = 'yes' === $this->get_option( 'is_debug_mod' );

		$this->has_fields = false;

		$this->description = $this->get_option( 'description' );

		$this->current_currency = get_option( 'woocommerce_currency' );

		$this->multi_currency_enabled = in_array( 'woocommerce-multilingual/wpml-woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) && get_option( 'icl_enable_multi_currency' ) === 'yes';

		$this->exchange_rate = $this->get_option( 'exchange_rate' );

		// 保存设置
		if ( is_admin() ) {
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
		}

		// 仪表盘通知
		add_action( 'admin_notices', [ $this, 'requirement_checks' ] );

		// 添加 URL
		add_action( 'woocommerce_api_wprs-wc-wechatpay-query', [ $this, 'query_order' ] );
		add_action( 'woocommerce_api_wprs-wc-wechatpay-notify', [ $this, 'listen_notify' ] );
		add_action( 'woocommerce_api_wprs-wc-wechatpay-bridge', [ $this, 'bridge' ] );
		add_action( 'woocommerce_api_wprs-wc-wechatpay-refresh-qrcode', [ $this, 'refresh_qrcode' ] );

		// 小程序支付功能
		add_action( 'woocommerce_api_wprs-wc-wechatpay-mini-app-login', [ $this, 'mini_app_login' ] );
		add_action( 'woocommerce_api_wprs-wc-wechatpay-mini-app-bridge', [ $this, 'process_mini_app_payment' ] );

		// 添加前端脚本
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_script' ] );
	}


	/**
	 * 网关设置
	 */
	public function init_form_fields() {
		/**
		 * 扫码回调链接: home_url('wc-api/wprs-wc-wechatpay-notify/')
		 * 支付授权目录: home_url()
		 * H5 支付域名: home_url()
		 */

		$this->form_fields = [
			'enabled'         => [
				'title'   => __( 'Enable / Disable', 'wprs-wc-wechatpay' ),
				'label'   => __( 'Enable this payment gateway', 'wprs-wc-wechatpay' ),
				'type'    => 'checkbox',
				'default' => 'no',
			],
			'title'           => [
				'title'   => __( 'Title', 'wprs-wc-wechatpay' ),
				'type'    => 'text',
				'default' => __( 'Wechatpay', 'wprs-wc-wechatpay' ),
			],
			'description'     => [
				'title'   => __( 'Description', 'wprs-wc-wechatpay' ),
				'type'    => 'textarea',
				'default' => __( 'Pay securely using WeChat Pay', 'wprs-wc-wechatpay' ),
				'css'     => 'max-width:350px;',
			],
			'order_prefix'    => [
				'title'       => __( 'Order Number Prefix', 'wprs-wc-wechatpay' ),
				'type'        => 'text',
				'description' => __( 'Only alphabet or number Allowed', 'wprs-wc-wechatpay' ),
				'default'     => __( 'WC-', 'wprs-wc-wechatpay' ),
			],
			'app_id'          => [
				'title'       => __( 'WeChat Official Account Developer ID(AppID)', 'wprs-wc-wechatpay' ),
				'type'        => 'text',
				'description' => __( 'Enter your WeChat Official Account Developer ID(AppID). Setup and obtain it in "Settings and Development > Basic configuration".', 'wprs-wc-wechatpay' ),
			],
			'app_secret'      => [
				'title'       => __( 'WeChat Official Account Developer Password(AppSecret)', 'wprs-wc-wechatpay' ),
				'type'        => 'text',
				'description' => __( 'Enter your WeChat Official Account Developer Password(AppSecret). Setup and obtain it in "Settings and Development > Basic configuration".', 'wprs-wc-wechatpay' ),
			],
			'mini_app_id'     => [
				'title'       => __( 'WeChat miniApp AppID(小程序ID)', 'wprs-wc-wechatpay' ),
				'type'        => 'text',
				'description' => __( 'Enter your WeChat MiniApp AppId. Setup and obtain it in 「开发 > 开发管理 > 开发设置」。', 'wprs-wc-wechatpay' ),
			],
			'mini_app_secret' => [
				'title'       => __( 'WeChat MiniApp AppSecret(小程序密钥)', 'wprs-wc-wechatpay' ),
				'type'        => 'text',
				'description' => __( 'Enter your WeChat MiniApp AppSecret. Setup and obtain it in 「开发 > 开发管理 > 开发设置」。', 'wprs-wc-wechatpay' ),
			],
			'mch_id'          => [
				'title'       => __( 'WeChatPay Mch Id', 'wprs-wc-wechatpay' ),
				'type'        => 'text',
				'description' => sprintf( __( 'Enter your WeChatPay Mch Id. obtain it in <a target=_blank href="%s">here</a>.', 'wprs-wc-wechatpay' ), 'https://pay.weixin.qq.com/index.php/core/account/info' ),
			],
			'api_key'         => [
				'title'       => __( 'WeChatPay APIv2 Secret', 'wprs-wc-wechatpay' ),
				'type'        => 'text',
				'description' => sprintf( __( 'Enter your WeChatPay APIv2 Secret that Setup in <a target=_blank href="%s">here</a>。支付授权目录和 H5 支付域名为： %s, 扫码回调链接为: %s.', 'wprs-wc-wechatpay' ), 'https://pay.weixin.qq.com/index.php/core/cert/api_cert', home_url(), home_url( 'wc-api/wprs-wc-wechatpay-notify/' ) ),
			],
			'cert_path'       => [
				'title'       => __( 'apiclient_cert.pem path', 'wprs-wc-wechatpay' ),
				'type'        => 'text',
				'description' => sprintf( __( 'Enter the absolute path of apiclient_cert.pem file that can access by the site, used by refund action。<br/>Ex: <code>/home/apiclient_cert.pem</code>，For security *DO NOT* place it in public dir. Setup in <a target=_blank href="%s">here</a>', 'wprs-wc-wechatpay' ), 'https://pay.weixin.qq.com/index.php/core/cert/api_cert' ),
			],
			'key_path'        => [
				'title'       => __( 'apiclient_key.pem Path', 'wprs-wc-wechatpay' ),
				'type'        => 'text',
				'description' => sprintf( __( 'Enter the absolute path of apiclient_key.pem file that can access by the site, used by refund action. <br/>Ex: <code>/home/apiclient_key.pem</code>，For security *DO NOT* place it in public dir. Setup in <a target=_blank href="%s">here</a>', 'wprs-wc-wechatpay' ), 'https://pay.weixin.qq.com/index.php/core/cert/api_cert' ),
			],
			'is_debug_mod'    => [
				'title'       => __( 'Debug Mode', 'wprs-wc-wechatpay' ),
				'label'       => __( 'Enable debug mod', 'wprs-wc-wechatpay' ),
				'type'        => 'checkbox',
				'description' => __( 'If checked, plugin will show program errors in frontend.', 'wprs-wc-wechatpay' ),
				'default'     => 'no',
			],
			'template'        => [
				'title'   => __( 'Checkout Style', 'wprs-wc-wechatpay' ),
				'type'    => 'select',
				'default' => 'modal',
				'options' => [
					'modal' => __( 'Show qrcode in modal', 'wprs-wc-wechatpay' ),
					'flat'  => __( 'Show qrcode in page', 'wprs-wc-wechatpay' ),
				],
			],
			'qrcode_timeout' => [
				'title'             => __( 'QR Code Timeout (minutes)', 'wprs-wc-wechatpay' ),
				'type'              => 'number',
				'description'       => __( 'Set how long a WeChat Pay Native QR code stays valid before the checkout page asks the customer to refresh it.', 'wprs-wc-wechatpay' ),
				'default'           => '120',
				'custom_attributes' => [
					'min'  => '5',
					'step' => '1',
				],
			],
		];

		if ( ! in_array( $this->current_currency, [ 'RMB', 'CNY' ] ) ) {

			$this->form_fields[ 'exchange_rate' ] = [
				'title'       => __( 'Exchange Rate', 'wprs-wc-wechatpay' ),
				'type'        => 'text',
				'description' => sprintf( __( 'Please set the %s against Chinese Yuan exchange rate, eg if your currency is US Dollar, then you should enter 6.19',
					'wprs-wc-wechatpay' ), $this->current_currency ),
			];

		}
	}


	/**
	 * 添加前端脚本
	 */
	public function enqueue_script() {
		$order_id = get_query_var( 'order-pay' );

		if ( Helpers::is_mini_app() ) {
			$jssdk = new SDK( $this->mini_app_id, $this->mini_app_secret );
		} else {
			$jssdk = new SDK( $this->app_id, $this->app_secret );
		}

		$signPackage = $jssdk->GetSignPackage();

		if ( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( WENPRISE_WECHATPAY_WOOCOMMERCE_ID === $order->get_payment_method() ) {

				if ( ! isset( $_GET[ 'pay_for_order' ] ) && is_checkout_pay_page() ) {

					if ( Helpers::is_wechat() ) {
						wp_enqueue_script( 'wprs-wc-wechatpay-js-sdk', 'https://res.wx.qq.com/open/js/jweixin-1.6.2.js', [ 'jquery' ], '1.6.2', true );
						wp_enqueue_script( 'wprs-wc-wechatpay-mpweb', WENPRISE_WECHATPAY_URL . '/frontend/mpweb.js', [ 'jquery' ], WENPRISE_WECHATPAY_VERSION, true );
					}

					wp_enqueue_style( 'wprs-wc-wechatpay-style', WENPRISE_WECHATPAY_URL . '/frontend/styles.css', [], WENPRISE_WECHATPAY_VERSION, false );

					$suffix = Constants::is_true( 'SCRIPT_DEBUG' ) ? '' : '.min';
					wp_enqueue_script( 'qrcode', WC()->plugin_url() . '/assets/js/jquery-qrcode/jquery.qrcode' . $suffix . '.js', [ 'jquery' ], WENPRISE_WECHATPAY_VERSION );
					wp_enqueue_script( 'wprs-wc-wechatpay-scripts', WENPRISE_WECHATPAY_URL . '/frontend/script.js', [ 'jquery', 'jquery-blockui', 'qrcode' ], WENPRISE_WECHATPAY_VERSION, true );

					wp_localize_script( 'wprs-wc-wechatpay-scripts', 'WpWooWechatPaySign', (array) $signPackage );

					if ( ! empty( $order ) ) {
						$order_data = $order->get_meta( 'wprs_wc_wechat_order_data' );
						wp_localize_script( 'wprs-wc-wechatpay-scripts', 'WpWooWechatPayOrder', (array) $order_data );
					}

					wp_localize_script( 'wprs-wc-wechatpay-scripts', 'WpWooWechatData', [
						'return_url'  => $this->get_return_url( $order ),
						'bridge_url'  => WC()->api_request_url( 'wprs-wc-wechatpay-bridge' ),
						'query_url'   => WC()->api_request_url( 'wprs-wc-wechatpay-query' ),
						'refresh_url' => WC()->api_request_url( 'wprs-wc-wechatpay-refresh-qrcode' ),
						'nonce'       => wp_create_nonce( 'wprs_wc_wechatpay_qrcode' ),
					] );

				}

			}
		}

	}


	/**
	 * 管理选项
	 */
	public function admin_options() { ?>

        <h3>
			<?php echo esc_html( ( ! empty( $this->method_title ) ) ? $this->method_title : __( 'Settings', 'wprs-wc-wechatpay' ) ); ?>
			<?php wc_back_link( __( 'Return to payments', 'woocommerce' ), admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ); ?>
        </h3>

		<?php echo ( ! empty( $this->method_description ) ) ? wp_kses_post( wpautop( $this->method_description ) ) : ''; ?>

        <table class="form-table">
			<?php $this->generate_settings_html(); ?>
        </table>

		<?php
	}


	/**
	 * 检查是否满足需求
	 *
	 * @access public
	 * @return void
	 */
	public function requirement_checks() {
		if ( ! $this->exchange_rate && ! in_array( $this->current_currency, [ 'RMB', 'CNY' ] ) ) {
			$message = sprintf(
				__( 'WeChatPay is enabled, but the store currency is not set to Chinese Yuan. Please <a href="%1s">set the %2s against the Chinese Yuan exchange rate</a>.', 'wprs-wc-wechatpay' ),
				esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wprs-wc-wechatpay#woocommerce_wprs-wc-wechatpay_exchange_rate' ) ),
				esc_html( $this->current_currency )
			);

			echo '<div class="error"><p>' . wp_kses_post( $message ) . '</p></div>';
		}
	}


	/**
	 * 手机浏览器跳转微信支付中间页面，为了解决浏览器屏蔽而存在
	 */
	public function bridge() {
		wp_die( __( 'Calling WeChat Pay, please wait a moment...', 'wprs-wc-wechatpay' ), __( 'Calling WeChat Pay, please wait a moment...', 'wprs-wc-alipay' ) );
	}


	/**
	 * 检查是否可用
	 *
	 * @return bool
	 */
	public function is_available(): bool {

		$is_available = 'yes' === $this->enabled;

		if ( $this->multi_currency_enabled ) {
			if ( ! $this->exchange_rate && ! in_array( get_woocommerce_currency(), [ 'RMB', 'CNY' ] ) ) {
				$is_available = false;
			}
		} elseif ( ! $this->exchange_rate && ! in_array( $this->current_currency, [ 'RMB', 'CNY' ] ) ) {
			$is_available = false;
		}

		return $is_available;
	}


	/**
	 * 获取支付网关
	 *
	 * @param string $type
	 *
	 * @return \Wenprise\Wechatpay\Omnipay\Common\GatewayInterface|\Wenprise\Wechatpay\Omnipay\WechatPay\BaseAbstractGateway
	 */
	public function get_gateway( string $type = '' ) {

		/** @var \Wenprise\Wechatpay\Omnipay\WechatPay\BaseAbstractGateway $gateway */
		if ( $type === 'native' ) {
			$gateway = Omnipay::create( 'WechatPay_Native' );
		} elseif ( wp_is_mobile() ) {
			if ( Helpers::is_wechat() || $type === 'mini_app' ) {
				$gateway = Omnipay::create( 'WechatPay_Js' );
			} else {
				$gateway = Omnipay::create( 'WechatPay_Mweb' );
			}
		} else {
			$gateway = Omnipay::create( 'WechatPay_Native' );
		}

		if ( Helpers::is_mini_app() || $type === 'mini_app' ) {
			$gateway->setAppId( trim( $this->mini_app_id ) );
		} else {
			$gateway->setAppId( trim( $this->app_id ) );
		}

		$gateway->setMchId( trim( $this->mch_id ) );

		// 这个 key 需要在微信商户里面单独设置，而不是微信服务号里面的 key
		$gateway->setApiKey( trim( $this->api_key ) );

		$gateway->setNotifyUrl( WC()->api_request_url( 'wprs-wc-wechatpay-notify' ) );

		return $gateway;
	}


	/**
	 * 获取订单号
	 * 对于重复支付的情况，在原订单号后附加重试次数
	 *
	 * @param $order_id
	 *
	 * @return string
	 */
	public function get_order_number( $order_id ): string {
		$order       = wc_get_order( $order_id );
		$retry_count = (int) $order->get_meta( '_wprs_wechat_pay_retry_count', true );

		// 如果是重试支付，增加重试次数并更新
		if ( $retry_count > 0 ) {
			$retry_count ++;
			$order->update_meta_data( '_wprs_wechat_pay_retry_count', $retry_count );
			$order->save();

			return $this->order_prefix . ltrim( $order_id, '#' ) . '_retry' . $retry_count;
		}

		// 首次支付，设置重试次数为0
		$order->update_meta_data( '_wprs_wechat_pay_retry_count', 0 );
		$order->save();

		return $this->order_prefix . ltrim( $order_id, '#' );
	}


	/**
	 * 检查订单是否属于当前支付网关。
	 *
	 * @param \WC_Order|false $order WooCommerce 订单对象。
	 *
	 * @return bool
	 */
	private function is_wechatpay_order( $order ): bool {
		return $order instanceof \WC_Order && WENPRISE_WECHATPAY_WOOCOMMERCE_ID === $order->get_payment_method();
	}


	/**
	 * 检查请求是否有权限访问订单。
	 *
	 * @param \WC_Order|false $order     WooCommerce 订单对象。
	 * @param string          $order_key 请求中携带的订单 key。
	 *
	 * @return bool
	 */
	private function can_access_order( $order, string $order_key = '' ): bool {
		if ( ! $order instanceof \WC_Order ) {
			return false;
		}

		if ( hash_equals( $order->get_order_key(), $order_key ) ) {
			return true;
		}

		$user_id = get_current_user_id();

		return $user_id > 0 && (int) $order->get_user_id() === $user_id;
	}


	/**
	 * 根据订单 ID 和订单 key 获取可访问的微信支付订单。
	 *
	 * @param int    $order_id  订单 ID。
	 * @param string $order_key 订单 key。
	 *
	 * @return \WC_Order|false
	 */
	private function get_accessible_order( int $order_id, string $order_key = '' ) {
		$order = wc_get_order( $order_id );

		if ( ! $this->is_wechatpay_order( $order ) || ! $this->can_access_order( $order, $order_key ) ) {
			return false;
		}

		return $order;
	}


	/**
	 * 获取换算成人民币后的订单金额，单位为分。
	 *
	 * @param \WC_Order $order WooCommerce 订单对象。
	 *
	 * @return int
	 */
	private function get_order_total_fee( \WC_Order $order ): int {
		$exchange_rate = (float) $this->get_option( 'exchange_rate' );
		if ( $exchange_rate <= 0 ) {
			$exchange_rate = 1;
		}

		$total = round( (float) $order->get_total() * $exchange_rate, get_option( 'woocommerce_price_num_decimals' ) );

		return (int) round( $total * 100 );
	}


	/**
	 * 获取微信扫码二维码的有效时长，单位为秒。
	 *
	 * @return int
	 */
	private function get_qrcode_timeout_seconds(): int {
		$timeout_minutes = max( 5, absint( $this->get_option( 'qrcode_timeout', 120 ) ) );

		return $timeout_minutes * MINUTE_IN_SECONDS;
	}


	/**
	 * 获取微信扫码二维码的过期时间。
	 *
	 * @return int
	 */
	private function get_qrcode_expires_at(): int {
		return time() + $this->get_qrcode_timeout_seconds();
	}


	/**
	 * 保存微信扫码二维码数据。
	 *
	 * @param \WC_Order $order        WooCommerce 订单对象。
	 * @param string    $code_url     二维码链接。
	 * @param string    $out_trade_no 微信商户订单号。
	 *
	 * @return int
	 */
	private function save_native_qrcode( \WC_Order $order, string $code_url, string $out_trade_no ): int {
		$created_at = time();
		$expires_at = $this->get_qrcode_expires_at();

		$order->update_meta_data( 'wprs_wc_wechat_code_url', $code_url );
		$order->update_meta_data( 'wprs_wc_wechat_out_trade_no', $out_trade_no );
		$order->update_meta_data( 'wprs_wc_wechat_qrcode_created_at', $created_at );
		$order->update_meta_data( 'wprs_wc_wechat_qrcode_expires_at', $expires_at );
		$order->save();

		return $expires_at;
	}


	/**
	 * 生成用于刷新二维码的微信商户订单号。
	 *
	 * @param \WC_Order $order WooCommerce 订单对象。
	 *
	 * @return string
	 */
	private function get_refreshed_order_number( \WC_Order $order ): string {
		$retry_count = (int) $order->get_meta( '_wprs_wechat_pay_retry_count', true ) + 1;
		$order->update_meta_data( '_wprs_wechat_pay_retry_count', $retry_count );
		$order->save();

		return $this->order_prefix . ltrim( $order->get_id(), '#' ) . '_retry' . $retry_count;
	}


	/**
	 * 创建微信 Native 扫码支付二维码。
	 *
	 * @param \WC_Order $order        WooCommerce 订单对象。
	 * @param string    $out_trade_no 微信商户订单号。
	 *
	 * @return array
	 * @throws \Exception 无法生成二维码时抛出异常。
	 */
	private function create_native_qrcode( \WC_Order $order, string $out_trade_no ): array {
		$gateway = $this->get_gateway( 'native' );

		$order_data = apply_filters( 'woocommerce_wenprise_wechatpay_args',
			[
				'body'             => sprintf( __( 'Pay for order %1$s at %2$s', 'wprs-wc-wechatpay' ), $out_trade_no, get_bloginfo( 'name' ) ),
				'out_trade_no'     => $out_trade_no,
				'total_fee'        => $this->get_order_total_fee( $order ),
				'spbill_create_ip' => Helpers::get_client_ip(),
				'fee_type'         => 'CNY',
			]
		);

		$request  = $gateway->purchase( $order_data );
		$response = $request->send();

		if ( ! $response->isSuccessful() || ! $response->getCodeUrl() ) {
			$this->log( $response->getData() );
			throw new \Exception( __( 'Failed to generate WeChat Pay QR code.', 'wprs-wc-wechatpay' ) );
		}

		$expires_at = $this->save_native_qrcode( $order, $response->getCodeUrl(), $out_trade_no );

		return [
			'qrcode'       => $response->getCodeUrl(),
			'expires_at'   => $expires_at,
			'out_trade_no' => $out_trade_no,
		];
	}


	/**
	 * 检查微信回调金额是否匹配订单金额。
	 *
	 * @param \WC_Order $order WooCommerce 订单对象。
	 * @param mixed     $data  微信支付回调数据。
	 *
	 * @return bool
	 */
	private function is_notify_amount_valid( \WC_Order $order, $data ): bool {
		if ( ! is_array( $data ) ) {
			return false;
		}

		$total_fee = isset( $data[ 'total_fee' ] ) ? (int) $data[ 'total_fee' ] : 0;

		return $total_fee > 0 && $total_fee === $this->get_order_total_fee( $order );
	}


	/**
	 * WooCommerce 支付处理 function/method.
	 *
	 * @inheritdoc
	 *
	 * @param int $order_id
	 *
	 * @return array|string[]
	 */
	public function process_payment( $order_id ): array {
		$order = wc_get_order( $order_id );

		/**
		 * 小程序环境中，直接跳转到订单支付页面
		 * 由订单支付页面中的 JS 自动调起微信登录和支付
		 */
		if ( Helpers::is_mini_app() ) {
			return [
				'result'   => 'success',
				'redirect' => add_query_arg( [ 'order-pay' => $order->get_id(), 'key' => $order->get_order_key(), 'from' => 'mini_app' ], wc_get_checkout_url() ),
			];
		}

		$order_no = $this->get_order_number( $order_id );
		$total    = $this->get_order_total();

		$exchange_rate = (float) $this->get_option( 'exchange_rate' );
		if ( $exchange_rate <= 0 ) {
			$exchange_rate = 1;
		}

		$total = round( $total * $exchange_rate, get_option( 'woocommerce_price_num_decimals' ) );
		$total = number_format( $total, get_option( 'woocommerce_price_num_decimals' ), '.', '' );

		do_action( 'wenprise_woocommerce_wechatpay_before_process_payment' );

		// 调用响应的方法来处理支付
		$gateway = $this->get_gateway();

		$order_data = apply_filters( 'woocommerce_wenprise_wechatpay_args',
			[
				'body'             => sprintf( __( 'Pay for order %1$s at %2$s', 'wprs-wc-wechatpay' ), $order_no, get_bloginfo( 'name' ) ),
				'out_trade_no'     => $order_no,
				'total_fee'        => $total * 100,
				'spbill_create_ip' => Helpers::get_client_ip(),
				'fee_type'         => 'CNY',
			]
		);

		if ( Helpers::is_wechat() ) {
			// 修改 Open ID 的获取方法，方便其他开发这兼容自己的微信登录
			$open_id                 = apply_filters( 'wprs_wc_wechat_open_id', get_user_meta( get_current_user_id(), 'wprs_wc_wechat_open_id', true ) );
			$order_data[ 'open_id' ] = $open_id;
		}

		/**
		 * 生成订单并发送支付
		 *
		 * @var \Wenprise\Wechatpay\Omnipay\WechatPay\Message\CreateOrderRequest  $request
		 * @var \Wenprise\Wechatpay\Omnipay\WechatPay\Message\CreateOrderResponse $response
		 */
		$request  = $gateway->purchase( $order_data );
		$response = $request->send();

		do_action( 'woocommerce_wenprise_wechatpay_before_payment_redirect', $response );

		// 微信支付, 显示二维码
		if ( $response->isSuccessful() ) {

			// 生成支付订单后清空购物车，以免订单重复
			WC()->cart->empty_cart();
			$order->update_meta_data( 'wprs_wc_wechat_out_trade_no', $order_no );

			if ( wp_is_mobile() ) {

				if ( Helpers::is_wechat() ) {
					// 微信中，返回跳转 URL，带上支付数据，由微信拉起支付
					$order->update_meta_data( 'wprs_wc_wechat_order_data', $response->getJsOrderData() );
					$order->save();

					$redirect_url = add_query_arg( [ 'order-pay' => $order->get_id(), 'key' => $order->get_order_key() ], wc_get_checkout_url() );
				} else {
					/**
					 * 移动浏览器中，返回跳转 URL，跳转 URL 中包含支付 URL，由 JS 跳转到支付 URL 进行支付
					 * 支付后，微信支付服务器推送支付成功数据到网站
					 * 在跳转URL中，JS 轮询支付状态，检测到支付成功后，跳转到支付成功页面
					 */
					$payment_url = $response->getMwebUrl() . '&redirect_url=' . urlencode( $order->get_checkout_payment_url( true ) . '&from=wap' );

					$redirect_url = add_query_arg( [ 'order-pay' => $order->get_id(), 'key' => $order->get_order_key(), 'from' => 'wap' ], wc_get_checkout_url() );

					$order->update_meta_data( 'wprs_wc_wechat_mweb_url', $payment_url );
					$order->save();

					return [
						'result'      => 'success',
						'redirect'    => $redirect_url,
						'payment_url' => $payment_url,
					];
				}

			} else {
				/**
				 * PC端，返回跳转URL，跳转页面中包含原生支付二维码
				 * 用户支付成功后，微信服务器推送支付成功数据到网站
				 * 在跳转URL中，JS 轮询支付状态，检测到支付成功后，跳转到支付成功页面
				 */
				$this->save_native_qrcode( $order, $response->getCodeUrl(), $order_no );

				$redirect_url = $order->get_checkout_payment_url( true );
			}

			return [
				'result'   => 'success',
				'redirect' => $redirect_url,
			];

		} else {

			if ( $this->is_debug_mod ) {

				$error = $response->getData();
				$this->log( $error );

				if ( array_key_exists( 'return_msg', $error ) ) {
					wc_add_notice( $error[ 'return_code' ] . ': ' . $error[ 'return_msg' ], 'error' );
				}

				if ( array_key_exists( 'err_code_des', $error ) ) {
					wc_add_notice( $error[ 'err_code' ] . ': ' . $error[ 'err_code_des' ], 'error' );
				}

				return [
					'result' => 'failure',
				];

			}

			wc_add_notice( __( 'WeChat payment configuration error, please contact us.', 'wprs-wc-wechatpay' ), 'error' );

			return [
				'result' => 'failure',
			];

		}

	}


	/**
	 * 在这里处理小程序支付
	 */
	public function process_mini_app_payment() {

		/**
		 * 获取生成订单需要的 Order ID 和 Open ID，这两个数据从微信小程序中传过来
		 */
		$post_data = json_decode( file_get_contents( 'php://input' ), true );

		if ( ! is_array( $post_data ) ) {
			wp_send_json_error( __( 'Invalid request data.', 'wprs-wc-wechatpay' ), 400 );
		}

		$order_id  = isset( $post_data[ 'order_id' ] ) ? absint( $post_data[ 'order_id' ] ) : 0;
		$order_key = isset( $post_data[ 'order_key' ] ) ? wc_clean( wp_unslash( $post_data[ 'order_key' ] ) ) : '';
		$open_id   = isset( $post_data[ 'open_id' ] ) ? sanitize_text_field( wp_unslash( $post_data[ 'open_id' ] ) ) : '';

		$order = $this->get_accessible_order( $order_id, $order_key );

		if ( ! $order || ! $open_id || ! $order->needs_payment() ) {
			wp_send_json_error( __( 'Invalid payment request.', 'wprs-wc-wechatpay' ), 403 );
		}

		$gateway = $this->get_gateway( 'mini_app' );

		$order_no = $this->get_order_number( $order_id );
		$total    = $order->get_total();

		$exchange_rate = (float) $this->get_option( 'exchange_rate' );
		if ( $exchange_rate <= 0 ) {
			$exchange_rate = 1;
		}

		$total = round( $total * $exchange_rate, get_option( 'woocommerce_price_num_decimals' ) );
		$total = number_format( $total, get_option( 'woocommerce_price_num_decimals' ), '.', '' );

		$order_data = [
			'body'             => sprintf( __( 'Pay for order %1$s at %2$s', 'wprs-wc-wechatpay' ), $order_no, get_bloginfo( 'name' ) ),
			'out_trade_no'     => $order_no,
			'total_fee'        => $total * 100,
			'spbill_create_ip' => Helpers::get_client_ip(),
			'fee_type'         => 'CNY',
			'open_id'          => $open_id,
		];

		/**
		 * 生成订单并发送支付
		 *
		 * @var \Wenprise\Wechatpay\Omnipay\WechatPay\Message\CreateOrderRequest  $request
		 * @var \Wenprise\Wechatpay\Omnipay\WechatPay\Message\CreateOrderResponse $response
		 */
		$request  = $gateway->purchase( $order_data );
		$response = $request->send();

		// 在小程序中使用的附加数据
		$addition_data = [
			'return_url' => $order->get_checkout_order_received_url(),
		];

		if ( $response->isSuccessful() ) {
			wp_send_json_success( array_merge( $addition_data, $response->getJsOrderData() ) );
		} else {
			$this->log( $response->getData() );
			wp_send_json_error( __( 'WeChat payment configuration error, please contact us.', 'wprs-wc-wechatpay' ) );
		}

	}


	/**
	 * 处理退款
	 *
	 * @param int    $order_id
	 * @param null   $amount
	 * @param string $reason
	 *
	 * @return bool
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ): bool {
		$gateway = $this->get_gateway();
		$gateway->setCertPath( $this->cert_path );
		$gateway->setKeyPath( $this->key_path );

		$order = wc_get_order( $order_id );
		$total = $order->get_total();

		$exchange_rate = (float) $this->get_option( 'exchange_rate' );
		if ( $exchange_rate <= 0 ) {
			$exchange_rate = 1;
		}

		$total      = round( $total * $exchange_rate, get_option( 'woocommerce_price_num_decimals' ) );
		$refund_fee = round( $amount * $exchange_rate, get_option( 'woocommerce_price_num_decimals' ) );
		$refund_fee = number_format( $refund_fee, get_option( 'woocommerce_price_num_decimals' ), '.', '' );

		if ( $refund_fee <= 0 || $refund_fee > $total ) {
			return false;
		}

		$request = $gateway->refund( [
			'transaction_id' => $order->get_transaction_id(),
			'out_trade_no'   => $this->get_order_number( $order_id ),
			'out_refund_no'  => 'refund_' . $order_id . '_' . wp_rand( 1000, 9999 ),
			'total_fee'      => $total * 100,      //=0.01
			'refund_fee'     => $refund_fee * 100, //=0.01
		] );

		/** @var \Wenprise\Wechatpay\Omnipay\WechatPay\Message\BaseAbstractResponse $response */
		try {
			$response = $request->send();
			$data     = $response->getData();
			
			if ( $response->isSuccessful() ) {
				$order->add_order_note(
					sprintf( __( 'Refunded %1$s', 'woocommerce' ), $amount )
				);

				$order->update_meta_data( 'refund_id', $data[ 'refund_id' ] );
				$order->save();

				return true;
			}

		} catch ( \Exception $e ) {
			$this->log( $e->getMessage() );
			return false;
		}

		return false;
	}


	/**
	 * 处理支付接口异步返回的信息
	 */
	public function listen_notify() {
		$gateway = $this->get_gateway();
		$raw_notify_data = file_get_contents( 'php://input' );

		error_log( '[wprs-wc-wechatpay] ' . wp_json_encode( [
			'event'      => 'wechatpay_notify_received',
			'time'       => current_time( 'mysql' ),
			'raw_length' => strlen( $raw_notify_data ),
		], JSON_UNESCAPED_UNICODE ) );

		$options = [
			'request_params' => $raw_notify_data,
		];

		/** @var \Wenprise\Wechatpay\Omnipay\WechatPay\Message\CompletePurchaseResponse $response */
		$request = $gateway->completePurchase( $options );

		try {
			$response = $request->send();
			$data     = $response->getRequestData();

			$out_trade_no = $data[ 'out_trade_no' ];
			$notify_out_trade_no = $out_trade_no;

			// 处理带有重试标记的订单号
			if ( strpos( $out_trade_no, '_retry' ) !== false ) {
				$parts        = explode( '_retry', $out_trade_no );
				$out_trade_no = $parts[ 0 ];
			}

			if ( is_numeric( $out_trade_no ) ) {
				if ( ! empty( $this->order_prefix ) ) {
					$order_id = (int) str_replace( $this->order_prefix, '', $out_trade_no );
				} else {
					$order_id = (int) $out_trade_no;
				}
			} else {
				$order_id = (int) str_replace( $this->order_prefix, '', $out_trade_no );
			}

			$order = wc_get_order( $order_id );
			$is_paid = $response->isPaid();
			$is_wechatpay_order = $this->is_wechatpay_order( $order );
			$is_amount_valid = $order instanceof \WC_Order && $this->is_notify_amount_valid( $order, $data );

			error_log( '[wprs-wc-wechatpay] ' . wp_json_encode( [
				'event'               => 'wechatpay_notify_parsed',
				'out_trade_no'        => $notify_out_trade_no,
				'normalized_trade_no' => $out_trade_no,
				'order_id'            => $order_id,
				'transaction_id'      => isset( $data[ 'transaction_id' ] ) ? $data[ 'transaction_id' ] : '',
				'total_fee'           => isset( $data[ 'total_fee' ] ) ? $data[ 'total_fee' ] : '',
				'is_paid'             => $is_paid,
				'is_wechatpay_order'  => $is_wechatpay_order,
				'is_amount_valid'     => $is_amount_valid,
			], JSON_UNESCAPED_UNICODE ) );

			if ( $is_paid && $is_wechatpay_order && $is_amount_valid ) {
				// 支付成功后重置重试计数
				$order->update_meta_data( '_wprs_wechat_pay_retry_count', 0 );
				$order->save();

				$this->complete_order( $order, $data );
				error_log( '[wprs-wc-wechatpay] ' . wp_json_encode( [
					'event'          => 'wechatpay_notify_completed',
					'order_id'       => $order->get_id(),
					'transaction_id' => isset( $data[ 'transaction_id' ] ) ? $data[ 'transaction_id' ] : '',
					'is_paid'        => $order->is_paid(),
				], JSON_UNESCAPED_UNICODE ) );

				header( 'Content-Type: text/xml; charset=' . get_option( 'blog_charset' ) );
				echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
				exit;
			} else {
				$error = $response->getData();
				error_log( '[wprs-wc-wechatpay] ' . wp_json_encode( [
					'event'              => 'wechatpay_notify_rejected',
					'order_id'           => $order_id,
					'is_paid'            => $is_paid,
					'is_wechatpay_order' => $is_wechatpay_order,
					'is_amount_valid'    => $is_amount_valid,
					'response_data'      => $error,
				], JSON_UNESCAPED_UNICODE ) );

				if ( $this->is_debug_mod ) {
					wc_add_notice( $error, 'error' );
				}
			}
		} catch ( \Exception $e ) {
			error_log( '[wprs-wc-wechatpay] ' . wp_json_encode( [
				'event'   => 'wechatpay_notify_exception',
				'message' => $e->getMessage(),
			], JSON_UNESCAPED_UNICODE ) );
		}
	}


	/**
	 * 清理微信支付过程中写入的临时订单数据。
	 *
	 * @param \WC_Order $order WooCommerce 订单对象。
	 *
	 * @return void
	 */
	private function cleanup_wechatpay_order_meta( \WC_Order $order ) {
		$order->delete_meta_data( 'wprs_wc_wechat_order_data' );
		$order->delete_meta_data( 'wprs_wc_wechat_code_url' );
		$order->delete_meta_data( 'wprs_wc_wechat_mweb_url' );
		$order->delete_meta_data( 'wprs_wc_wechat_out_trade_no' );
		$order->delete_meta_data( 'wprs_wc_wechat_qrcode_created_at' );
		$order->delete_meta_data( 'wprs_wc_wechat_qrcode_expires_at' );
		$order->save();
	}


	/**
	 * 完成订单并清理微信支付临时数据。
	 *
	 * @param int|\WC_Order $order_id 订单 ID 或订单对象。
	 * @param array         $data     微信支付回调或查询数据。
	 *
	 * @return void
	 */
	function complete_order( $order_id, $data ) {
		$order = $order_id instanceof \WC_Order ? $order_id : wc_get_order( $order_id );

		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$transaction_id = isset( $data[ 'transaction_id' ] ) ? sanitize_text_field( (string) $data[ 'transaction_id' ] ) : '';
		if ( ! $transaction_id ) {
			$this->cleanup_wechatpay_order_meta( $order );
			return;
		}

		$completed_transaction_id = (string) $order->get_meta( '_wprs_wc_wechatpay_completed_transaction_id', true );
		if ( hash_equals( $completed_transaction_id, $transaction_id ) ) {
			$this->cleanup_wechatpay_order_meta( $order );
			return;
		}

		$lock_key = 'wprs_wc_wechatpay_complete_' . md5( $order->get_id() . '_' . $transaction_id );
		$lock_time = (int) get_option( $lock_key );
		if ( $lock_time && time() - $lock_time > 5 * MINUTE_IN_SECONDS ) {
			delete_option( $lock_key );
		}

		if ( ! add_option( $lock_key, time(), '', 'no' ) ) {
			$this->cleanup_wechatpay_order_meta( $order );
			return;
		}

		if ( ! $order->is_paid() ) {
			$order->payment_complete( $transaction_id );
		}

		$order->update_meta_data( '_wprs_wc_wechatpay_completed_transaction_id', $transaction_id );

		// Empty cart.
		if ( WC()->cart ) {
			WC()->cart->empty_cart();
		}

		$this->cleanup_wechatpay_order_meta( $order );
		delete_option( $lock_key );
	}


	/**
	 * 扫码支付页面
	 *
	 * @param $order_id int 订单 ID
	 *
	 * @todo: 微信客户端在这里静默登录？
	 *
	 */
	function receipt_page( int $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		/**
		 * 小程序环境中，直接跳转到 WebView 支付页面
		 */
		if ( Helpers::is_mini_app() || Helpers::is_wechat() ) {
			?>
            <script>
              jQuery(document).ready(function($) {
                /**
                 * 调用微信小程序支付
                 */
                var wprs_wc_call_mini_app_pay = function() {
                  wx.miniProgram.reLaunch({url: <?php echo wp_json_encode( '/pages/wePay/wePay?order_id=' . $order->get_id() . '&order_key=' . rawurlencode( $order->get_order_key() ) ); ?>});
                };
                wprs_wc_call_mini_app_pay();
              });
            </script>

			<?php
		}

		$from       = isset( $_GET[ 'from' ] ) ? (string) $_GET[ 'from' ] : false;
		$code_url   = $order->get_meta( 'wprs_wc_wechat_code_url' );
		$expires_at = (int) $order->get_meta( 'wprs_wc_wechat_qrcode_expires_at' );

		if ( $from === 'wap' ) {
			// 移动浏览器中，显示去微信支付和查询支付结果按钮
			?>

            <div class="rs-instruction-box">
                <svg t="1682582744808" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg" p-id="6584" width="64" height="64">
                    <path d="M512 960C264.577 960 64 759.424 64 512S264.577 64 512 64s448 200.576 448 448-200.577 448-448 448z m246.632-610.632c-12.491-12.491-32.742-12.491-45.233 0L456 606.767 310.601 461.368c-12.491-12.491-32.742-12.491-45.233 0s-12.491 32.742 0 45.233l167.703 167.703c0.104 0.107 0.191 0.223 0.297 0.328 6.249 6.249 14.441 9.372 22.632 9.368 8.191 0.004 16.383-3.118 22.632-9.368 0.106-0.105 0.193-0.222 0.297-0.328l279.703-279.703c12.491-12.491 12.491-32.742 0-45.233z"
                          p-id="6585" fill="#16a34a"></path>
                </svg>

                <div class="rs-instruction-box__title">订单提交成功！</div>

                <p>请核实上面的支付信息后，点击下面的按钮去微信支付。</p>
            </div>

            <div class="rs-flex rs-justify-center rs-mt-4 rs-action-block">
                <a target="_blank" id="js-wprs-wc-wechatpay" data-order_id="<?php echo esc_attr( $order_id ); ?>" data-order_key="<?php echo esc_attr( $order->get_order_key() ); ?>" class="button alt rs-flex rs-payment-url rswc-button" href="<?php echo esc_url( $order->get_meta( 'wprs_wc_wechat_mweb_url' ) ); ?>">
                    <svg t="1686880431321" class="icon" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg" p-id="2044" xmlns:xlink="http://www.w3.org/1999/xlink" width="24" height="24">
                        <path d="M404.511405 600.865957c-4.042059 2.043542-8.602935 3.223415-13.447267 3.223415-11.197016 0-20.934798-6.169513-26.045189-15.278985l-1.959631-4.296863-81.56569-178.973184c-0.880043-1.954515-1.430582-4.14746-1.430582-6.285147 0-8.251941 6.686283-14.944364 14.938224-14.944364 3.351328 0 6.441713 1.108241 8.94165 2.966565l96.242971 68.521606c7.037277 4.609994 15.433504 7.305383 24.464181 7.305383 5.40101 0 10.533914-1.00284 15.328104-2.75167l452.645171-201.459315C811.496653 163.274644 677.866167 100.777241 526.648117 100.777241c-247.448742 0-448.035176 167.158091-448.035176 373.361453 0 112.511493 60.353576 213.775828 154.808832 282.214547 7.582699 5.405103 12.537548 14.292518 12.537548 24.325012 0 3.312442-0.712221 6.358825-1.569752 9.515724-7.544837 28.15013-19.62599 73.202209-20.188808 75.314313-0.940418 3.529383-2.416026 7.220449-2.416026 10.917654 0 8.245801 6.692423 14.933107 14.944364 14.933107 3.251044 0 5.89015-1.202385 8.629541-2.7793l98.085946-56.621579c7.377014-4.266164 15.188934-6.89913 23.790846-6.89913 4.577249 0 9.003048 0.703011 13.174044 1.978051 45.75509 13.159718 95.123474 20.476357 146.239666 20.476357 247.438509 0 448.042339-167.162184 448.042339-373.372709 0-62.451354-18.502399-121.275087-51.033303-173.009356L407.778822 598.977957 404.511405 600.865957z"
                              fill="#00C800" p-id="2045"></path>
                    </svg>
                    微信支付
                </a>
                <a href="#" id="js-wechatpay-fail" class="button rswc-button rs-flex alt2 rs-ml-4">
					<?php echo esc_html__( '查询支付结果', 'wprs-wc-alipay' ); ?>
                </a>
            </div>

			<?php

			// echo '<div class="wprs-wc-buttons">';
			// echo '<button class="button" id="js-wprs-wc-wechatpay" data-order_id="' . $order_id . '">已支付</button>';
			// echo '<a target="_blank" class="button" href="' . $order->get_meta('wprs_wc_wechat_mweb_url') . '">继续支付</a>';
			// echo '</div>';

		} else {

			if ( Helpers::is_wechat() ) {
				// 微信中，用户需要点击支付按钮调起支付窗口
				if ( Helpers::is_mini_app() ) {
					echo '<button class="button alt" onclick="wprs_wc_call_mini_app_pay()" >使用微信支付</button>';
				} else {
					echo '<button class="button alt" onclick="wprs_wc_call_wechat_pay()" >使用微信支付</button>';
				}
			}

			if ( $code_url ) {

				if ( $this->template === 'modal' ):
					?>

                    <div id="js-wechatpay-confirm-modal" class="rs-confirm-modal" style="display: none;">

                        <div class="rs-modal">
                            <header class="rs-modal__header">
								<?php echo esc_html__( 'Please scan the QR code with WeChat to finish the payment.', 'wprs-wc-wechatpay' ); ?>
                            </header>
                            <div class="rs-modal__content">
                                <div class="wprs-wechatpay-qrcode">
                                    <div class="wprs-wechatpay-qrcode__code-slot">
                                        <div id="js-wechatpay-qrcode-loading" class="wprs-wechatpay-qrcode__loading">
                                            <span class="wprs-wechatpay-qrcode__spinner"></span>
                                        </div>
                                        <div id="js-wprs-wc-wechatpay" data-order_id="<?php echo esc_attr( $order_id ); ?>" data-order_key="<?php echo esc_attr( $order->get_order_key() ); ?>" data-code_url="<?php echo esc_attr( $code_url ); ?>" data-expires_at="<?php echo esc_attr( $expires_at ); ?>"></div>
                                        <div id="js-wechatpay-qrcode-expired" class="wprs-wechatpay-qrcode__overlay" style="display:none;">
                                            <button type="button" id="js-wechatpay-refresh-qrcode" class="button alt rswc-button">
												<?php echo esc_html__( 'Refresh QR code', 'wprs-wc-wechatpay' ); ?>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="wprs-wechatpay-qrcode-status is-loading">
                                        <p id="js-wechatpay-qrcode-countdown" class="rs-qrcode-countdown">
											<?php echo esc_html__( 'Generating QR code...', 'wprs-wc-wechatpay' ); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <footer class="rs-modal__footer">
                                <input type="button" id="js-wechatpay-success" class="button alt is-primary" value="支付成功" />
                                <input type="button" id="js-wechatpay-fail" class="button" value="支付失败" />
                            </footer>
                        </div>

                    </div>

				<?php else: ?>

                    <div class='rs-conform-block'>

                        <div class='rs-block'>
                            <header class='rs-block__header'>
                                <div class="rs-wechatpay-logo">
                                    <img src="<?php echo esc_url( $this->icon ); ?>" alt="<?php echo esc_attr( $this->title ); ?>" /> <?php echo esc_html( $this->title ); ?>
                                </div>
                            </header>
                            <div class="rs-block__content">
                                <div class="wprs-wechatpay-qrcode">
                                    <div class="wprs-wechatpay-qrcode__code-slot">
                                        <div id="js-wechatpay-qrcode-loading" class="wprs-wechatpay-qrcode__loading">
                                            <span class="wprs-wechatpay-qrcode__spinner"></span>
                                        </div>
                                        <div id="js-wprs-wc-wechatpay" data-order_id="<?php echo esc_attr( $order_id ); ?>" data-order_key="<?php echo esc_attr( $order->get_order_key() ); ?>" data-code_url="<?php echo esc_attr( $code_url ); ?>" data-expires_at="<?php echo esc_attr( $expires_at ); ?>"></div>
                                        <div id="js-wechatpay-qrcode-expired" class="wprs-wechatpay-qrcode__overlay" style="display:none;">
                                            <button type="button" id="js-wechatpay-refresh-qrcode" class="button alt rswc-button">
												<?php echo esc_html__( 'Refresh QR code', 'wprs-wc-wechatpay' ); ?>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="wprs-wechatpay-qrcode-status is-loading">
                                        <p id="js-wechatpay-qrcode-countdown" class="rs-qrcode-countdown">
											<?php echo esc_html__( 'Generating QR code...', 'wprs-wc-wechatpay' ); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class='rs-block__tips'>
                            <img src="<?php echo esc_url( WENPRISE_WECHATPAY_ASSETS_URL . 'weixinpay_mobile.png' ); ?>" alt='微信扫一扫指引' />
                        </div>

                    </div>

				<?php endif; ?>

				<?php
			}
		}

	}


	/**
	 * 监听微信扫码支付返回
	 * https://pay.weixin.qq.com/wiki/doc/api/micropay.php?chapter=9_02
	 */
	/**
	 * 处理查询请求时的订单号
	 */
	public function query_order() {
		$order_id = isset( $_GET[ 'order_id' ] ) ? (int) $_GET[ 'order_id' ] : false;
		$order_key = isset( $_GET[ 'order_key' ] ) ? wc_clean( wp_unslash( $_GET[ 'order_key' ] ) ) : '';

		if ( $order_id ) {
			$order = $this->get_accessible_order( $order_id, $order_key );

			if ( ! $order ) {
				wp_send_json_error( __( 'Invalid order.', 'wprs-wc-wechatpay' ), 403 );
			}

			// 获取当前支付交易号，刷新二维码后会包含重试标记
			$current_order_number = $order->get_meta( 'wprs_wc_wechat_out_trade_no' );
			if ( ! $current_order_number ) {
				$current_order_number = $this->get_order_number( $order_id );
			}

			$response = $this->get_gateway()->query( [
				'out_trade_no' => $current_order_number,
			] )->send();

			$result_data = $response->getData();

			if ( Helpers::data_get( $result_data, 'return_code' ) === 'SUCCESS'
			     && Helpers::data_get( $result_data, 'result_code' ) === 'SUCCESS'
			     && Helpers::data_get( $result_data, 'trade_state' ) === 'SUCCESS'
			     && $this->is_notify_amount_valid( $order, $result_data )
			) {
				$this->complete_order( $order, $result_data );
				wp_send_json_success( $order->get_checkout_order_received_url() );
			}

			if ( $order && $order->is_paid() ) {
				wp_send_json_success( $order->get_checkout_order_received_url() );
			} else {
				wp_send_json_error( $order->get_checkout_payment_url() );
			}
		} else {
			wp_send_json_error();
		}
	}


	/**
	 * 刷新微信扫码支付二维码。
	 */
	public function refresh_qrcode() {
		$nonce = isset( $_POST[ 'nonce' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'nonce' ] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'wprs_wc_wechatpay_qrcode' ) ) {
			wp_send_json_error( __( 'Invalid request.', 'wprs-wc-wechatpay' ), 403 );
		}

		$order_id  = isset( $_POST[ 'order_id' ] ) ? absint( wp_unslash( $_POST[ 'order_id' ] ) ) : 0;
		$order_key = isset( $_POST[ 'order_key' ] ) ? wc_clean( wp_unslash( $_POST[ 'order_key' ] ) ) : '';

		$order = $this->get_accessible_order( $order_id, $order_key );

		if ( ! $order ) {
			wp_send_json_error( __( 'You are not allowed to refresh this QR code.', 'wprs-wc-wechatpay' ), 403 );
		}

		if ( ! $order->needs_payment() ) {
			wp_send_json_success( [
				'url'     => $order->get_checkout_order_received_url(),
				'message' => __( 'Payment successful', 'wprs-wc-wechatpay' ),
			] );
		}

		try {
			$qrcode_data = $this->create_native_qrcode( $order, $this->get_refreshed_order_number( $order ) );

			wp_send_json_success( [
				'qrcode'       => $qrcode_data[ 'qrcode' ],
				'expires_at'   => $qrcode_data[ 'expires_at' ],
				'out_trade_no' => $qrcode_data[ 'out_trade_no' ],
				'message'      => __( 'QR code refreshed.', 'wprs-wc-wechatpay' ),
			] );
		} catch ( \Exception $e ) {
			$this->log( $e->getMessage() );
			wp_send_json_error( $this->is_debug_mod ? $e->getMessage() : __( 'Failed to refresh WeChat Pay QR code, please try again.', 'wprs-wc-wechatpay' ) );
		}
	}


	/**
	 * 检查小程序登录接口是否触发频率限制。
	 *
	 * @return bool
	 */
	private function is_mini_app_login_rate_limited(): bool {
		$client_ip = Helpers::get_client_ip();
		$rate_key  = 'wprs_wc_wechatpay_mini_app_login_' . md5( $client_ip );
		$count     = (int) get_transient( $rate_key );

		if ( $count >= 30 ) {
			return true;
		}

		set_transient( $rate_key, $count + 1, MINUTE_IN_SECONDS );

		return false;
	}


	/**
	 * 小程序登录接口，使用微信 code 换取 openid。
	 */
	function mini_app_login() {
		if ( $this->is_mini_app_login_rate_limited() ) {
			wp_send_json_error( __( 'Too many requests, please try again later.', 'wprs-wc-wechatpay' ), 429 );
		}

		if ( ! isset( $_GET[ 'code' ] ) ) {
			wp_send_json_error( 'Missing code param' );
		}

		$code = sanitize_text_field( wp_unslash( $_GET[ 'code' ] ) );

		$args = [
			'appid'      => $this->mini_app_id,
			'secret'     => $this->mini_app_secret,
			'js_code'    => $code,
			'grant_type' => 'authorization_code',
		];

		$url_base = 'https://api.weixin.qq.com/sns/jscode2session';
		$url      = add_query_arg( $args, $url_base );

		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( $response->get_error_message() );
		} else {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( ! is_array( $body ) || empty( $body[ 'openid' ] ) ) {
				wp_send_json_error( [
					'errcode' => isset( $body[ 'errcode' ] ) ? (int) $body[ 'errcode' ] : 0,
					'errmsg' => isset( $body[ 'errmsg' ] ) ? sanitize_text_field( $body[ 'errmsg' ] ) : __( 'Mini app login failed.', 'wprs-wc-wechatpay' ),
				] );
			}

			wp_send_json_success( [
				'openid'  => sanitize_text_field( $body[ 'openid' ] ),
				'unionid' => isset( $body[ 'unionid' ] ) ? sanitize_text_field( $body[ 'unionid' ] ) : '',
			] );
		}

		exit();
	}


	/**
	 * Logger 辅助功能
	 *
	 * @param $message
	 */
	public function log( $message ) {
		if ( $this->is_debug_mod ) {
			if ( ! ( $this->log ) ) {
				$this->log = new \WC_Logger();
			}
			$this->log->add( 'wprs-wc-wechatpay', var_export( $message, true ) );
		}
	}

}
