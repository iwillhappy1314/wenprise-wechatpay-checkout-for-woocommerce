<?php

if ( ! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use Omnipay\Omnipay;
use WenpriseWechatPay\Helper;

require WENPRISE_WECHATPAY_PATH . 'jssdk.php';

/**
 * Gateway class
 */
class Wenprise_Wechat_Pay_Gateway extends WC_Payment_Gateway
{

    /** @var WC_Logger Logger 实例 */
    public $log = false;

    /**
     * @var bool
     *
     * @deprecated
     */
    public $enabled_auto_login = false;

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
     * 网关支持的功能
     *
     * @var array
     */
    public $supports = ['products', 'refunds'];


    public function __construct()
    {

        // 支付方法的全局 ID
        $this->id = WENPRISE_WECHATPAY_WOOCOMMERCE_ID;

        // 支付网关页面显示的支付网关标题
        $this->method_title = __('Wechat Pay', 'wprs-wc-wechatpay');

        // 支付网关设置页面显示的支付网关标题
        $this->method_description = __('Wechat Pay payment gateway for WooCommerce', 'wprs-wc-wechatpay');

        // 被 init_settings() 加载的基础设置
        $this->init_form_fields();

        $this->init_settings();

        // 转换设置为变量以方便使用
        foreach ($this->settings as $setting_key => $value) {
            $this->$setting_key = $value;
        }

        // 前端显示的支付网关名称
        $this->title = $this->get_option('title');

        // 支付网关标题
        $this->icon = apply_filters('omnipay_wechat_pay_icon', WENPRISE_WECHATPAY_ASSETS_URL . 'wechatpay.png');

        $this->is_debug_mod = 'yes' === $this->get_option('is_debug_mod');

        $this->has_fields = false;

        $this->enabled_auto_login = 'yes' === $this->get_option('enabled_auto_login');

        $this->description = $this->get_option('description');

        $this->current_currency = get_option('woocommerce_currency');

        $this->multi_currency_enabled = in_array('woocommerce-multilingual/wpml-woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')), true) && get_option('icl_enable_multi_currency') === 'yes';

        $this->exchange_rate = $this->get_option('exchange_rate');

        // 设置是否应该重命名按钮。
        $this->order_button_text = apply_filters('woocommerce_wechatpay_button_text', __('Proceed to Wechatpay', 'wprs-wc-wechatpay'));


        // 保存设置
        if (is_admin()) {
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        }

        // 仪表盘通知
        add_action('admin_notices', [$this, 'requirement_checks']);

        // 添加 URL
        add_action('woocommerce_api_wprs-wc-wechatpay-query', [$this, 'query_order']);
        add_action('woocommerce_api_wprs-wc-wechatpay-notify', [$this, 'listen_notify']);
        add_action('woocommerce_api_wprs-wc-wechatpay-bridge', [$this, 'bridge']);

        // 小程序支付功能
        add_action('woocommerce_api_wprs-wc-wechatpay-mini-app-login', [$this, 'mini_app_login']);
        add_action('woocommerce_api_wprs-wc-wechatpay-mini-app-bridge', [$this, 'process_mini_app_payment']);

        // 添加前端脚本
        add_action('wp_enqueue_scripts', [$this, 'enqueue_script']);
    }


    /**
     * 网关设置
     */
    public function init_form_fields()
    {

        // 扫码回调链接: home_url('wc-api/wprs-wc-wechatpay-notify/')
        // 支付授权目录: home_url()
        // H5 支付域名: home_url()

        $this->form_fields = [
            'enabled'            => [
                'title'   => __('Enable / Disable', 'wprs-wc-wechatpay'),
                'label'   => __('Enable this payment gateway', 'wprs-wc-wechatpay'),
                'type'    => 'checkbox',
                'default' => 'no',
            ],
            'enabled_auto_login' => [
                'title'   => __('Enable / Disable', 'wprs-wc-wechatpay'),
                'label'   => __('Enable auto login in wechat Official Accounts (此功能将被弃用，不建议使用)', 'wprs-wc-wechatpay'),
                'type'    => 'checkbox',
                'default' => 'no',
            ],
            'title'              => [
                'title'   => __('Title', 'wprs-wc-wechatpay'),
                'type'    => 'text',
                'default' => __('Wechatpay', 'wprs-wc-wechatpay'),
            ],
            'description'        => [
                'title'   => __('Description', 'wprs-wc-wechatpay'),
                'type'    => 'textarea',
                'default' => __('Pay securely using Wechat Pay', 'wprs-wc-wechatpay'),
                'css'     => 'max-width:350px;',
            ],
            'order_prefix'       => [
                'title'       => __('Order Number Prefix', 'wprs-wc-wechatpay'),
                'type'        => 'text',
                'description' => __('Only alphabet or number Allowed', 'wprs-wc-wechatpay'),
                'default'     => __('WC-', 'wprs-wc-wechatpay'),
            ],
            'app_id'             => [
                'title'       => __('Wechat App Id', 'wprs-wc-wechatpay'),
                'type'        => 'text',
                'description' => sprintf(__('Enter your Wechat App Id. Setup and obtain it in 「开发 > 基本配置」。', 'wprs-wc-wechatpay')),
            ],
            'app_secret'         => [
                'title'       => __('Wechat App Secret', 'wprs-wc-wechatpay'),
                'type'        => 'text',
                'description' => sprintf(__('Enter your Wechat App Secret. Setup and obtain it in 「开发 > 基本配置」。', 'wprs-wc-wechatpay')),
            ],
            'mini_app_id'        => [
                'title'       => __('Wechat miniApp App Id', 'wprs-wc-wechatpay'),
                'type'        => 'text',
                'description' => sprintf(__('Enter your Wechat Mini App Id. Setup and obtain it in 「开发 > 开发配置」。', 'wprs-wc-wechatpay')),
            ],
            'mini_app_secret'    => [
                'title'       => __('Wechat MiniApp Secret', 'wprs-wc-wechatpay'),
                'type'        => 'text',
                'description' => sprintf(__('Enter your Wechat App Secret. Setup and obtain it in 「开发 > 开发配置」。', 'wprs-wc-wechatpay')),
            ],
            'mch_id'             => [
                'title'       => __('Wechat Mch Id', 'wprs-wc-wechatpay'),
                'type'        => 'text',
                'description' => sprintf(__('Enter your Wechat Mch Id. obtain it in <a target=_blank href="%s">here</a>', 'wprs-wc-wechatpay'), 'https://pay.weixin.qq.com/index.php/core/account/info'),
            ],
            'api_key'            => [
                'title'       => __('Wechat Api Key', 'wprs-wc-wechatpay'),
                'type'        => 'text',
                'description' => sprintf(__('Enter your Wechat Api Key that Setup in <a target=_blank href="%s">here</a>。支付授权目录和 H5 支付域名为： %s, 扫码回调链接为: %s', 'wprs-wc-wechatpay'), 'https://pay.weixin.qq.com/index.php/core/cert/api_cert', home_url(), home_url('wc-api/wprs-wc-wechatpay-notify/')),
            ],
            'cert_path'          => [
                'title'       => __('apiclient_cert.pem path', 'wprs-wc-wechatpay'),
                'type'        => 'text',
                'description' => sprintf(__('Enter the absolute path of apiclient_cert.pem file that can access by the site, used by refund action。<br/>Ex: <code>/home/apiclient_cert.pem</code>，For security *DO NOT* place it in public dir. Setup in <a target=_blank href="%s">here</a>', 'wprs-wc-wechatpay'), 'https://pay.weixin.qq.com/index.php/core/cert/api_cert'),
            ],
            'key_path'           => [
                'title'       => __('apiclient_key.pem Path', 'wprs-wc-wechatpay'),
                'type'        => 'text',
                'description' => sprintf(__('Enter the absolute path of apiclient_key.pem file that can access by the site, used by refund action. <br/>Ex: <code>/home/apiclient_key.pem</code>，For security *DO NOT* place it in public dir. Setup in <a target=_blank href="%s">here</a>', 'wprs-wc-wechatpay'), 'https://pay.weixin.qq.com/index.php/core/cert/api_cert'),
            ],
            'is_debug_mod'       => [
                'title'       => __('Debug Mode', 'wprs-wc-wechatpay'),
                'label'       => __('Enable debug mod', 'wprs-wc-wechatpay'),
                'type'        => 'checkbox',
                'description' => sprintf(__('If checked, plugin will show program errors in frontend.', 'wprs-wc-wechatpay')),
                'default'     => 'no',
            ],
            'template'           => [
                'title'   => __('Checkout Style', 'wprs-wc-wechatpay'),
                'type'    => 'select',
                'default' => 'modal',
                'options' => [
                    'modal' => __('Show qrcode in modal', 'wprs-wc-wechatpay'),
                    'flat'  => __('Show qrcode in page', 'wprs-wc-wechatpay'),
                ],
            ],
        ];

        if ( ! in_array($this->current_currency, ['RMB', 'CNY'])) {

            $this->form_fields[ 'exchange_rate' ] = [
                'title'       => __('Exchange Rate', 'wprs-wc-wechatpay'),
                'type'        => 'text',
                'description' => sprintf(__('Please set the %s against Chinese Yuan exchange rate, eg if your currency is US Dollar, then you should enter 6.19',
                    'wprs-wc-wechatpay'), $this->current_currency),
            ];

        }
    }


    /**
     * 添加前端脚本
     */
    public function enqueue_script()
    {
        $order_id = get_query_var('order-pay');
        $order    = wc_get_order($order_id);

        if (Helper::is_mini_app()) {
            $jssdk = new JSSDK($this->mini_app_id, $this->mini_app_secret);
        } else {
            $jssdk = new JSSDK($this->app_id, $this->app_secret);
        }

        $signPackage = $jssdk->GetSignPackage();
        $order_data  = get_post_meta($order_id, 'wprs_wc_wechat_order_data', true);

        if ($order && 'wprs-wc-wechatpay' === $order->get_payment_method()) {
            if ( ! isset($_GET[ 'pay_for_order' ]) && is_checkout_pay_page()) {

                if (Helper::is_wechat()) {
                    wp_enqueue_script('wprs-wc-wechatpay-js-sdk', 'https://res.wx.qq.com/open/js/jweixin-1.6.0.js', ['jquery'], WENPRISE_WECHATPAY_VERSION, true);
                    wp_enqueue_script('wprs-wc-wechatpay-scripts', plugins_url('/frontend/mpweb.js', __FILE__), ['jquery'], WENPRISE_WECHATPAY_VERSION, true);
                }

                wp_enqueue_style('wprs-wc-wechatpay-style', plugins_url('/frontend/styles.css', __FILE__), [], WENPRISE_WECHATPAY_VERSION, false);
                wp_enqueue_script('wprs-wc-wechatpay-scripts', plugins_url('/frontend/script.js', __FILE__), ['jquery', 'jquery-blockui'], WENPRISE_WECHATPAY_VERSION, true);
                wp_enqueue_script('qrcode', WC()->plugin_url() . '/assets/js/jquery-qrcode/jquery.qrcode.js', ['jquery'], WENPRISE_WECHATPAY_VERSION);

                wp_localize_script('wprs-wc-wechatpay-scripts', 'WpWooWechatPaySign', $signPackage);

                if ( ! empty($order_data)) {
                    wp_localize_script('wprs-wc-wechatpay-scripts', 'WpWooWechatPayOrder', $order_data);
                }

                wp_localize_script('wprs-wc-wechatpay-scripts', 'WpWooWechatData', [
                    'return_url' => $this->get_return_url($order),
                    'bridge_url' => WC()->api_request_url('wprs-wc-wechatpay-bridge'),
                    'query_url'  => WC()->api_request_url('wprs-wc-wechatpay-query'),
                ]);

            }
        }

    }


    /**
     * 管理选项
     */
    public function admin_options()
    { ?>

        <h3>
            <?php echo ( ! empty($this->method_title)) ? $this->method_title : __('Settings', 'wprs-wc-wechatpay'); ?>
            <?php wc_back_link(__('Return to payments', 'woocommerce'), admin_url('admin.php?page=wc-settings&tab=checkout')); ?>
        </h3>

        <?php echo ( ! empty($this->method_description)) ? wpautop($this->method_description) : ''; ?>

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
    public function requirement_checks()
    {
        if ( ! $this->exchange_rate && ! in_array($this->current_currency, ['RMB', 'CNY'])) {
            echo '<div class="error"><p>' . sprintf(__('WeChatPay is enabled, but the store currency is not set to Chinese Yuan. Please <a href="%1s">set the %2s against the Chinese Yuan exchange rate</a>.', 'wprs-wc-wechatpay'),
                    admin_url('admin.php?page=wc-settings&tab=checkout&section=wprs-wc-wechatpay#woocommerce_wprs-wc-wechatpay_exchange_rate'),
                    $this->current_currency) . '</p></div>';
        }
    }


    /**
     * 手机浏览器跳转微信支付中间页面，为了解决浏览器屏蔽而存在
     */
    public function bridge()
    {
        wp_die(__('Calling Wechat Pay..., please wait a moment...', 'wprs-wc-wechatpay'), __('Calling Wechat Pay..., please wait a moment...', 'wprs-wc-alipay'));
    }


    /**
     * 获取订单号
     *
     * @param $order_id
     *
     * @return string
     */
    public function get_order_number($order_id)
    {
        return $this->order_prefix . ltrim($order_id, '#');
    }


    /**
     * 检查是否可用
     *
     * @return bool
     */
    public function is_available()
    {

        $is_available = 'yes' === $this->enabled;

        if ($this->multi_currency_enabled) {
            if ( ! $this->exchange_rate && ! in_array(get_woocommerce_currency(), ['RMB', 'CNY'])) {
                $is_available = false;
            }
        } elseif ( ! $this->exchange_rate && ! in_array($this->current_currency, ['RMB', 'CNY'])) {
            $is_available = false;
        }

        return $is_available;
    }


    /**
     * 获取支付网关
     *
     * @param string $type
     *
     * @return mixed
     */
    public function get_gateway($type = '')
    {

        /** @var \Omnipay\WechatPay\BaseAbstractGateway $gateway */
        if (wp_is_mobile()) {
            if (Helper::is_wechat() || $type === 'mini_app') {
                $gateway = Omnipay::create('WechatPay_Js');
            } else {
                $gateway = Omnipay::create('WechatPay_Mweb');
            }
        } else {
            $gateway = Omnipay::create('WechatPay_Native');
        }

        if (Helper::is_mini_app() || $type === 'mini_app') {
            $gateway->setAppId(trim($this->mini_app_id));
        } else {
            $gateway->setAppId(trim($this->app_id));
        }

        $gateway->setMchId(trim($this->mch_id));

        // 这个 key 需要在微信商户里面单独设置，而不是微信服务号里面的 key
        $gateway->setApiKey(trim($this->api_key));

        $gateway->setNotifyUrl(WC()->api_request_url('wprs-wc-wechatpay-notify'));

        return $gateway;
    }


    /**
     * WooCommerce 支付处理 function/method.
     *
     * @inheritdoc
     *
     * @param int $order_id
     *
     * @return mixed
     */
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        /**
         * 小程序环境中，直接跳转到订单支付页面
         * 由订单支付页面中的 JS 自动调起微信登录和支付
         */
        if (Helper::is_mini_app()) {
            return [
                'result'   => 'success',
                'redirect' => add_query_arg(['order-pay' => $order->get_id(), 'key' => $order->get_order_key(), 'from' => 'mini_app'], wc_get_checkout_url()),
            ];
        }

        $order_no = $this->get_order_number($order_id);
        $total    = $this->get_order_total();

        $exchange_rate = (float)$this->get_option('exchange_rate');
        if ($exchange_rate <= 0) {
            $exchange_rate = 1;
        }

        $total = round($total * $exchange_rate, 2);

        do_action('wenprise_woocommerce_wechatpay_before_process_payment');

        // 调用响应的方法来处理支付
        $gateway = $this->get_gateway();

        $order_data = apply_filters('woocommerce_wenprise_wechatpay_args',
            [
                'body'             => sprintf(__('Pay for order %1$s at %2$s', 'wprs-wc-wechatpay'), $order_no, get_bloginfo('name')),
                'out_trade_no'     => $order_no,
                'total_fee'        => $total * 100,
                'spbill_create_ip' => Helper::get_client_ip(),
                'fee_type'         => 'CNY',
            ]
        );

        if (Helper::is_wechat()) {
            // 修改 Open ID 的获取方法，方便其他开发这兼容自己的微信登录
            $open_id                 = apply_filters('wprs_wc_wechat_open_id', get_user_meta(get_current_user_id(), 'wprs_wc_wechat_open_id', true));
            $order_data[ 'open_id' ] = $open_id;
        }

        // 生成订单并发送支付
        /**
         * @var \Omnipay\WechatPay\Message\CreateOrderRequest  $request
         * @var \Omnipay\WechatPay\Message\CreateOrderResponse $response
         */
        $request  = $gateway->purchase($order_data);
        $response = $request->send();

        // $this->log(var_export($response, true));

        do_action('woocommerce_wenprise_wechatpay_before_payment_redirect', $response);

        // 微信支付, 显示二维码
        if ($response->isSuccessful()) {

            // 生成支付订单后清空购物车，以免订单重复
            WC()->cart->empty_cart();

            if (wp_is_mobile()) {

                if (Helper::is_wechat()) {
                    // 微信中，返回跳转 URL，带上支付数据，由微信拉起支付
                    update_post_meta($order_id, 'wprs_wc_wechat_order_data', $response->getJsOrderData());
                    $redirect_url = add_query_arg(['order-pay' => $order->get_id(), 'key' => $order->get_order_key()], wc_get_checkout_url());

                } else {
                    // 移动浏览器中，返回跳转 URL，跳转 URl 中包含支付 URL，由 JS 跳转到支付 URL 进行支付
                    // 支付后，微信支付服务器推送支付成功数据到网站
                    // 在跳转URL中，JS 轮询支付状态，检测到支付成功后，跳转到支付成功页面
                    $payment_url = $response->getMwebUrl() . '&redirect_url=' . urlencode($order->get_checkout_payment_url(true) . '&from=wap');

                    $redirect_url = add_query_arg(['order-pay' => $order->get_id(), 'key' => $order->get_order_key(), 'from' => 'wap'], wc_get_checkout_url());

                    update_post_meta($order_id, 'wprs_wc_wechat_mweb_url', $payment_url);

                    return [
                        'result'      => 'success',
                        'redirect'    => $redirect_url,
                        'payment_url' => $payment_url,
                    ];
                }

            } else {
                // PC端，返回跳转URL，跳转页面中包含原生支付二维码
                // 用户支付成功后，微信服务器推送支付成功数据到网站
                // 在跳转URL中，JS 轮询支付状态，检测到支付成功后，跳转到支付成功页面
                $code_url = $response->getCodeUrl();
                update_post_meta($order_id, 'wprs_wc_wechat_code_url', $code_url);

                $redirect_url = $order->get_checkout_payment_url(true);
            }

            return [
                'result'   => 'success',
                'redirect' => $redirect_url,
            ];

        } else {

            if ($this->is_debug_mod) {

                $error = $response->getData();
                $this->log($error);

                if (array_key_exists('return_msg', $error)) {
                    wc_add_notice($error[ 'return_code' ] . ': ' . $error[ 'return_msg' ], 'error');
                }

                if (array_key_exists('err_code_des', $error)) {
                    wc_add_notice($error[ 'err_code' ] . ': ' . $error[ 'err_code_des' ], 'error');
                }

                return [
                    'result' => 'failure',
                ];

            }

            wc_add_notice(__('WeChat payment configuration error, please contact us.', 'wprs-wc-wechatpay'), 'error');

            return [
                'result' => 'failure',
            ];

        }

    }


    /**
     * 在这里处理小程序支付
     */
    public function process_mini_app_payment()
    {

        /**
         * 获取生成订单需要的 Order ID 和 Open ID，这两个数据从微信小程序中传过来
         */
        $post_data = json_decode(file_get_contents('php://input'));

        $order_id = $post_data->order_id;
        $open_id  = $post_data->open_id;

        $gateway = $this->get_gateway('mini_app');
        $order   = wc_get_order($order_id);

        $order_no = $this->get_order_number($order_id);
        $total    = $order->get_total();

        $exchange_rate = (float)$this->get_option('exchange_rate');
        if ($exchange_rate <= 0) {
            $exchange_rate = 1;
        }

        $total = round($total * $exchange_rate, 2);

        $order_data = [
            'body'             => sprintf(__('Pay for order %1$s at %2$s', 'wprs-wc-wechatpay'), $order_no, get_bloginfo('name')),
            'out_trade_no'     => $order_no,
            'total_fee'        => $total * 100,
            'spbill_create_ip' => Helper::get_client_ip(),
            'fee_type'         => 'CNY',
            'open_id'          => $open_id,
        ];

        /**
         * 生成订单并发送支付
         *
         * @var \Omnipay\WechatPay\Message\CreateOrderRequest  $request
         * @var \Omnipay\WechatPay\Message\CreateOrderResponse $response
         */
        $request  = $gateway->purchase($order_data);
        $response = $request->send();

        // 在小程序中使用的附加数据
        $addition_data = [
            'return_url' => $order->get_checkout_order_received_url(),
        ];

        if ($response->isSuccessful()) {
            wp_send_json_success(array_merge($addition_data, $response->getJsOrderData()));
        } else {
            wp_send_json_error($response->getData());
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
    public function process_refund($order_id, $amount = null, $reason = '')
    {
        $gateway = $this->get_gateway();
        $gateway->setCertPath($this->cert_path);
        $gateway->setKeyPath($this->key_path);

        $order = wc_get_order($order_id);
        $total = $order->get_total();

        $exchange_rate = (float)$this->get_option('exchange_rate');
        if ($exchange_rate <= 0) {
            $exchange_rate = 1;
        }

        $total      = round($total * $exchange_rate, 2);
        $refund_fee = round($amount * $exchange_rate, 2);

        if ($refund_fee <= 0 || $refund_fee > $total) {
            return false;
        }

        $request = $gateway->refund([
            'transaction_id' => $order->get_transaction_id(),
            'out_trade_no'   => $this->get_order_number($order_id),
            'out_refund_no'  => 'refund_' . $order_id . '_' . wp_rand(1000, 9999),
            'total_fee'      => $total * 100,      //=0.01
            'refund_fee'     => $refund_fee * 100, //=0.01
        ]);

        /** @var \Omnipay\WechatPay\Message\BaseAbstractResponse $response */
        try {
            $response = $request->send();
            $data     = $response->getData();

            if ($response->isSuccessful()) {
                $order->add_order_note(
                    sprintf(__('Refunded %1$s', 'woocommerce'), $amount)
                );

                update_post_meta($order_id, 'refund_id', $data[ 'refund_id' ]);

                return true;
            }

        } catch (\Exception $e) {
            $this->log($e->getMessage());

            return false;
        }

        return false;
    }


    /**
     * 处理支付接口异步返回的信息
     */
    public function listen_notify()
    {

        $gateway = $this->get_gateway();

        /**
         * 获取支付宝返回的参数
         */
        $options = [
            'request_params' => file_get_contents('php://input'),
        ];

        /** @var \Omnipay\WechatPay\Message\CompletePurchaseResponse $response */
        $request = $gateway->completePurchase($options);

        try {

            $response = $request->send();
            $data     = $response->getRequestData();

            $out_trade_no = $data[ 'out_trade_no' ];

            if (is_numeric($out_trade_no)) {
                if ( ! empty($this->order_prefix)) {
                    $order_id = (int)str_replace($this->order_prefix, '', $out_trade_no);
                } else {
                    $order_id = (int)$out_trade_no;
                }
            } else {
                $order_id = (int)str_replace($this->order_prefix, '', $out_trade_no);
            }

            $order = wc_get_order($order_id);

            if ($response->isPaid()) {

                $order->payment_complete($data[ 'transaction_id' ]);

                // Empty cart.
                WC()->cart->empty_cart();

                // 添加订单备注
                $order->add_order_note(
                    sprintf(__('Wechatpay payment complete (Transaction ID: %s)', 'wprs-wc-wechatpay'), $data[ 'transaction_id' ])
                );

                delete_post_meta($order->get_id(), 'wprs_wc_wechat_order_data');
                delete_post_meta($order->get_id(), 'wprs_wc_wechat_code_url');
                delete_post_meta($order->get_id(), 'wprs_wc_wechat_mweb_url');

                echo exit('<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>');

            } else {

                $error = $response->getData();
                $this->log($error);

                if ($this->is_debug_mod) {
                    wc_add_notice($error, 'error');
                }

            }

        } catch (\Exception $e) {
            $this->log($e->getMessage());
        }

    }


    /**
     * 扫码支付页面
     *
     * @param $order_id int 订单 ID
     */
    function receipt_page($order_id)
    {

        /**
         * 小程序环境中，直接跳转到 WebView 支付页面
         */
        if (Helper::is_mini_app()) {
        ?>
        <script>
            /**
             * 调用微信小程序支付
             */
            function wprs_wc_call_mini_app_pay() {
              wx.miniProgram.reLaunch({url: '/pages/wePay/wePay?order_id=<?= $order_id; ?>'});
            }

            wprs_wc_call_mini_app_pay();
        </script>

        <?php
    }

        $from     = isset($_GET[ 'from' ]) ? (string)$_GET[ 'from' ] : false;
        $code_url = get_post_meta($order_id, 'wprs_wc_wechat_code_url', true);

        if ($from === 'wap') {
            // 移动浏览器中，显示已支付和继续支付的按钮没，功能和 Modal 类似

            echo '<div class="wprs-wc-buttons">';
            echo '<button class="button" id="js-wprs-wc-wechatpay" data-order_id="' . $order_id . '">已支付</button>';
            echo '<a target="_blank" class="button" href="' . get_post_meta($order_id, 'wprs_wc_wechat_mweb_url', true) . '">继续支付</a>';
            echo '</div>';

        } else {

            if (Helper::is_wechat()) {
                // 微信中，用户需要点击支付按钮调起支付窗口
                if (Helper::is_mini_app()) {
                    echo '<button class="button" onclick="wprs_wc_call_mini_app_pay()" >使用微信支付</button>';
                } else {
                    echo '<button class="button" onclick="wprs_wc_call_wechat_pay()" >立即支付</button>';
                }
            }

            if ($code_url) {

                if ($this->template === 'modal'):
                    ?>

                    <div id="js-wechatpay-confirm-modal" class="rs-confirm-modal" style="display: none;">

                        <div class="rs-modal">
                            <header class="rs-modal__header">
                                <?= __('Please scan the QR code with WeChat to finish the payment.', 'wprs-wc-wechatpay'); ?>
                            </header>
                            <div class="rs-modal__content">
                                <div id="js-wprs-wc-wechatpay" style="text-align: center" data-order_id="<?= $order_id; ?>"></div>
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
                                    <img src="<?= $this->icon; ?>" alt="<?= $this->method_title; ?>" /> <?= $this->method_title; ?>
                                </div>
                            </header>
                            <div class="rs-block__content">
                                <div id="js-wprs-wc-wechatpay" style="text-align: center" data-order_id="<?= $order_id; ?>"></div>
                            </div>
                            <footer class="rs-block__footer">
                                <?= __('Please scan the QR code with WeChat to finish the payment.', 'wprs-wc-wechatpay'); ?>
                            </footer>
                        </div>

                        <div class='rs-block__tips'>
                            <img src="<?= WENPRISE_WECHATPAY_ASSETS_URL . 'weixinpay_mobile.png'; ?>" alt='微信扫一扫指引' />
                        </div>

                    </div>

                <?php endif; ?>

                <script>
                  jQuery(document).ready(function($) {
                    $('#js-wprs-wc-wechatpay').qrcode('<?= $code_url; ?>');
                  });
                </script>

                <?php
            }
        }

    }


    /**
     * 监听微信扫码支付返回
     */
    public function query_order()
    {
        $order_id = isset($_GET[ 'order_id' ]) ? (int)$_GET[ 'order_id' ] : false;

        if ($order_id) {
            $order = wc_get_order($order_id);

            if ($order && $order->is_paid()) {
                wp_send_json_success($order->get_checkout_order_received_url());
            } else {
                wp_send_json_error($order->get_checkout_payment_url());
            }
        } else {
            wp_send_json_error();
        }
    }


    function mini_app_login()
    {
        if ( ! isset($_GET[ 'code' ])) {
            wp_send_json_error('Missing code param');
        }

        $args = [
            'appid'      => $this->mini_app_id,
            'secret'     => $this->mini_app_secret,
            'js_code'    => $_GET[ 'code' ],
            'grant_type' => 'authorization_code',
        ];

        $url_base = 'https://api.weixin.qq.com/sns/jscode2session';
        $url      = add_query_arg($args, $url_base);

        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        } else {
            wp_send_json_success(json_decode(wp_remote_retrieve_body($response)));
        }

        exit();
    }


    /**
     * Logger 辅助功能
     *
     * @param $message
     */
    public function log($message)
    {
        if ($this->is_debug_mod) {
            if ( ! ($this->log)) {
                $this->log = new WC_Logger();
            }
            $this->log->add('wprs-wc-wechatpay', var_export($message, true));
        }
    }

}