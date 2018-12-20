<?php

if ( ! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use Endroid\QrCode\QrCode;
use Omnipay\Omnipay;

/**
 * Gateway class
 */
class Wenprise_Wechat_Pay_Gateway extends \WC_Payment_Gateway
{

    /** @var bool 日志是否启用 */
    public $debug_active = false;

    /** @var WC_Logger Logger 实例 */
    public $log = false;

    /**
     * @var bool
     */
    public $environment = false;

    /**
     * @var string
     */
    public $app_id = '';

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
     * 网关支持的功能
     *
     * @var array
     */
    public $supports = ['products', 'refunds'];

    /** @var string WC_API for the gateway - 作为回调 url 使用 */
    public $notify_url;

    function __construct()
    {

        $this->current_currency = get_option('woocommerce_currency');

        $this->multi_currency_enabled = in_array('woocommerce-multilingual/wpml-woocommerce.php',
                apply_filters('active_plugins', get_option('active_plugins'))) && get_option('icl_enable_multi_currency') == 'yes';

        // 支付方法的全局 ID
        $this->id = WENPRISE_WECHATPAY_WOOCOMMERCE_ID;

        // 支付网关页面显示的支付网关标题
        $this->method_title = __("Wechat Pay", 'wprs-wc-wechatpay');

        // 支付网关设置页面显示的支付网关标题
        $this->method_description = __("Wechat Pay payment gateway for WooCommerce", 'wprs-wc-wechatpay');

        // 前端显示的支付网关名称
        $this->title = __("Wechat Pay", 'wprs-wc-wechatpay');

        // 支付网关标题
        $this->icon = apply_filters('omnipay_eechat_pay_icon', null);

        $this->supports = [];

        // 被 init_settings() 加载的基础设置
        $this->init_form_fields();

        $this->init_settings();

        $this->debug_active = false;

        $this->has_fields = false;

        $this->description = $this->get_option('description');

        $this->exchange_rate = $this->get_option('exchange_rate');

        // 转换设置为变量以方便使用
        foreach ($this->settings as $setting_key => $value) {
            $this->$setting_key = $value;
        }

        // 设置是否应该重命名按钮。
        $this->order_button_text = apply_filters('woocommerce_Wechatpay_button_text', __('Proceed to Wechatpay', 'wprs-wc-wechatpay'));

        // 保存设置
        if (is_admin()) {
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        }

        // 仪表盘通知
        add_action('admin_notices', [$this, 'requirement_checks']);

        // 添加 URL
        add_action('woocommerce_api_wprs-wechatpay-query', [$this, 'query_order']);
        add_action('woocommerce_api_wprs-wechatpay-notify', [$this, 'listen_notify']);

        // 添加前端脚本
        add_action('wp_enqueue_scripts', [$this, 'enqueue_script']);
    }


    /**
     * 网关设置
     */
    public function init_form_fields()
    {
        $this->form_fields = [
            'enabled'     => [
                'title'   => __('Enable / Disable', 'wprs-wc-wechatpay'),
                'label'   => __('Enable this payment gateway', 'wprs-wc-wechatpay'),
                'type'    => 'checkbox',
                'default' => 'no',
            ],
            // 'environment' => [
            //     'title'       => __(' Wechatpay Sanbox Mode', 'wprs-wc-wechatpay'),
            //     'label'       => __('Enable Wechatpay Sanbox Mode', 'wprs-wc-wechatpay'),
            //     'type'        => 'checkbox',
            //     'description' => sprintf(__('Wechatpay sandbox can be used to test payments. Sign up for an account <a href="%s">here</a>',
            //         'wprs-wc-wechatpay'),
            //         'https://sandbox.Wechatpay.com'),
            //     'default'     => 'no',
            // ],
            'title'       => [
                'title'   => __('Title', 'wprs-wc-wechatpay'),
                'type'    => 'text',
                'default' => __('Wechatpay', 'wprs-wc-wechatpay'),
            ],
            'description' => [
                'title'   => __('Description', 'wprs-wc-wechatpay'),
                'type'    => 'textarea',
                'default' => __('Pay securely using Wechat Pay', 'wprs-wc-wechatpay'),
                'css'     => 'max-width:350px;',
            ],
            'app_id'      => [
                'title'       => __('Wechat App Id', 'wprs-wc-wechatpay'),
                'type'        => 'text',
                'description' => __('Enter your Wechat App Id.', 'wprs-wc-wechatpay'),
            ],
            'mch_id'      => [
                'title'       => __('Wechat Mch Id', 'wprs-wc-wechatpay'),
                'type'        => 'text',
                'description' => __('Enter your Wechat Mch Id.', 'wprs-wc-wechatpay'),
            ],
            'api_key'     => [
                'title'       => __('Wechat Api Key', 'wprs-wc-wechatpay'),
                'type'        => 'text',
                'description' => __('Enter your Wechat Api Key', 'wprs-wc-wechatpay'),
            ],
        ];

        if ( ! in_array($this->current_currency, ['RMB', 'CNY'])) {

            $this->form_fields[ 'exchange_rate' ] = [
                'title'       => __('Exchange Rate', 'wprs-wc-wechatpay'),
                'type'        => 'text',
                'description' => sprintf(__("Please set the %s against Chinese Yuan exchange rate, eg if your currency is US Dollar, then you should enter 6.19",
                    'wprs-wc-wechatpay'), $this->current_currency),
            ];

        }
    }


    /**
     * 添加前端脚本
     */
    public function enqueue_script()
    {
        $orderId = get_query_var('order-pay');
        $order   = new WC_Order($orderId);
        if ("wprs-wc-wechatpay" == $order->payment_method) {
            if (is_checkout_pay_page() && ! isset($_GET[ 'pay_for_order' ])) {
                wp_enqueue_script('wprs-wc-wechatpay-scripts', plugins_url('/assets/main.js', __FILE__), ['jquery'], null);
            }
        }
    }


    /**
     * 管理选项
     */
    public function admin_options()
    { ?>

        <h3><?php echo ( ! empty($this->method_title)) ? $this->method_title : __('Settings', 'wprs-wc-wechatpay'); ?></h3>

        <?php echo ( ! empty($this->method_description)) ? wpautop($this->method_description) : ''; ?>

        <table class="form-table">
        <?php $this->generate_settings_html(); ?>
        </table><?php
    }


    /**
     * 是否为测试模式
     *
     * @return bool
     */
    public function is_test_mode()
    {
        return $this->environment == "yes";
    }


    /**
     * 是否为测试模式
     * to the payment parameter before redirecting offsite to 2co for payment.
     *
     * This filter controls enabling testing via sandbox account.
     *
     * @return bool
     */
    public function is_sandbox_test()
    {
        return apply_filters('woocommerce_wenprise_wechatpay_enable_sandbox', false);
    }


    /**
     * 检查是否满足需求
     *
     * @access public
     * @return void
     */
    function requirement_checks()
    {
        if ( ! in_array($this->current_currency, ['RMB', 'CNY']) && ! $this->exchange_rate) {
            echo '<div class="error"><p>' . sprintf(__('WeChatPay is enabled, but the store currency is not set to Chinese Yuan. Please <a href="%1s">set the %2s against the Chinese Yuan exchange rate</a>.',
                    'wechatpay'), admin_url('admin.php?page=wc-settings&tab=checkout&section=wprs-wc-wechatpay#woocommerce_wprs-wc-wechatpay_exchange_rate'),
                    $this->current_currency) . '</p></div>';
        }
    }


    /**
     * 检查是否可用
     *
     * @return bool
     */
    function is_available()
    {

        $is_available = ('yes' === $this->enabled) ? true : false;

        if ($this->multi_currency_enabled) {
            if ( ! in_array(get_woocommerce_currency(), ['RMB', 'CNY']) && ! $this->exchange_rate) {
                $is_available = false;
            }
        } elseif ( ! in_array($this->current_currency, ['RMB', 'CNY']) && ! $this->exchange_rate) {
            $is_available = false;
        }

        return $is_available;
    }


    /**
     * 获取支付网关
     *
     * @return mixed
     */
    public function get_gateway()
    {
        /** @var \Omnipay\WechatPay\BaseAbstractGateway $gateway */
        $gateway = Omnipay::create('WechatPay_Native');

        $gateway->setAppId($this->app_id);
        $gateway->setMchId($this->mch_id);

        // 这个 key 需要在微信商户里面单独设置，而吧是微信服务号里面的 key
        $gateway->setApiKey($this->api_key);

        $gateway->setNotifyUrl(urldecode($this->notify_url));

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

        $order    = new WC_Order($order_id);
        $order_no = $order->get_order_number();

        $this->notify_url = wc_get_endpoint_url('wprs-wechatpay-notify');

        do_action('wenprise_woocommerce_wechatpay_before_process_payment');

        // 调用响应的方法来处理支付
        try {

            $gateway = $this->get_gateway();

            $order_data = apply_filters('woocommerce_wenprise_wechatpay_args',
                [
                    'body'             => '网站订单',
                    'out_trade_no'     => $order_no,
                    'total_fee'        => $order->get_total() * 100,
                    'spbill_create_ip' => '127.0.0.1',
                    'fee_type'         => 'CNY',
                ]
            );

            // 生成订单并发送支付
            /**
             * @var \Omnipay\WechatPay\Message\CreateOrderRequest  $request
             * @var \Omnipay\WechatPay\Message\CreateOrderResponse $response
             */
            $request  = $gateway->purchase($order_data);
            $response = $request->send();

            do_action('woocommerce_wenprise_wechatpay_before_payment_redirect', $response);

            $code_url = $response->getCodeUrl();
            update_post_meta($order_id, 'code_url', $code_url);

            wc_empty_cart();

            // 微信支付, 显示二维码
            if ( ! empty($_SERVER[ 'HTTP_X_REQUESTED_WITH' ]) && strtolower($_SERVER[ 'HTTP_X_REQUESTED_WITH' ]) == 'xmlhttprequest') {

                $data = [
                    'result'   => 'success',
                    'redirect' => $order->get_checkout_payment_url(true),
                ];

                wp_send_json($data);

            } else {

                $args = [
                    'out_trade_no' => $order_no,
                ];

                wc_get_template('payment/qrcode', $args, WC()->template_path(), WENPRISE_WECHATPAY_PATH . 'templates/');
            }

            // 返回支付连接，由 WooCommerce 跳转到微信支付
            if ($response->isRedirect()) {

                return [
                    'result'   => 'success',
                    'redirect' => $response->getRedirectUrl(),
                ];

            }

        } catch (Exception $e) {

            $error = $e->getMessage();
            $order->add_order_note(sprintf("%s Payments Failed: '%s'", $this->method_title, $error));

            wc_add_notice($error, "error");

            return [
                'result'   => 'fail',
                'redirect' => '',
            ];

        }
    }


    /**
     * 扫码支付页面
     *
     * @param $order_id int 订单 ID
     */
    function receipt_page($order_id)
    {
        $code_url = get_post_meta($order_id, 'code_url', true);

        if ($code_url) {
            $qrCode = new QrCode($code_url);
            $qrCode->setSize(256);
            $qrCode->setMargin(0);

            echo '<p>' . __('Please scan the QR code with WeChat to finish the payment.', 'wprs-wc-wechatpay') . '</p>';
            echo '<img id="js-wprs-wc-wechatpay" data-order_id="' . $order_id . '" src="' . $qrCode->writeDataUri() . '" />';
        }

    }


    /**
     * 监听微信扫码支付返回
     */
    public function query_order()
    {
        $order_id = isset($_GET[ 'order_id' ]) ? $_GET[ 'order_id' ] : false;
        $order    = wc_get_order($order_id);

        if ( ! $order->needs_payment()) {
            $data = [
                'success'  => true,
                'redirect' => $this->get_return_url(wc_get_order($order_id)),
            ];

            wp_send_json($data);
        }
    }


    /**
     * 处理支付接口异步返回的信息
     */
    public function listen_notify()
    {

        if (isset($_REQUEST[ 'out_trade_no' ]) && ! empty($_REQUEST[ 'out_trade_no' ])) {

            try {
                $gateway = $this->get_gateway();

                /**
                 * 获取支付宝返回的参数
                 */
                $options = [
                    'request_params' => file_get_contents('php://input'),
                ];

                /** @var \Omnipay\WechatPay\Message\CompletePurchaseResponse $response */
                $response = $gateway->completePurchase($options)->send();

                $order = new WC_Order($response->getTransactionId());

                if ($response->isPaid()) {
                    $transaction_ref = $response->getTransactionReference();
                    $order->payment_complete();

                    // 添加订单备注
                    $order->add_order_note(
                        sprintf(__('Wechatpay payment complete (Charge ID: %s)', 'wprs-wc-wechatpay'),
                            $transaction_ref
                        )
                    );

                    wp_redirect($this->get_return_url($order));
                    exit;
                } else {
                    $error = $response->getMessage();
                    $order->add_order_note(sprintf("%s Payments Failed: '%s'", $this->method_title, $error));
                    wc_add_notice($error, 'error');
                    $this->log($error);
                    wp_redirect(wc_get_checkout_url());
                    exit;
                }

            } catch (\Exception $e) {

                $error = $e->getMessage();
                wc_add_notice($error, 'error');
                $this->log($error);
                wp_redirect(wc_get_checkout_url());
                exit;

            }
        }
    }


    /**
     * Logger 辅助功能
     *
     * @param $message
     */
    public function log($message)
    {
        if ($this->debug_active) {
            if ( ! ($this->log)) {
                $this->log = new WC_Logger();
            }
            $this->log->add('wprs-wc-wechatpay', $message);
        }
    }

}
