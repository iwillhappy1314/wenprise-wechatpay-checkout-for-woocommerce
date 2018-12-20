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

    public $environment = false;

    public $app_id = false;
    public $mch_id = false;
    public $api_key = false;

    /** @var string WC_API for the gateway - 作为回调 url 使用 */
    public $notify_url;

    function __construct()
    {

        // 支付方法的全局 ID
        $this->id = WENPRISE_WECHATPAY_WOOCOMMERCE_ID;

        // 支付网关页面显示的支付网关标题
        $this->method_title = __("Wechat Pay", 'wprs-woo-wechatpay');

        // 支付网关设置页面显示的支付网关标题
        $this->method_description = __("Wechat Pay payment gateway for WooCommerce", 'wprs-woo-wechatpay');

        // 前端显示的支付网关名称
        $this->title = __("Wechat Pay", 'wprs-woo-wechatpay');

        // 支付网关标题
        $this->icon = apply_filters('omnipay_eechat_pay_icon', null);

        $this->supports = [];

        // 被 init_settings() 加载的基础设置
        $this->init_form_fields();

        $this->init_settings();

        $this->debug_active = true;
        $this->has_fields   = false;

        $this->description = $this->get_option('description');

        // 转换设置为变量以方便使用
        foreach ($this->settings as $setting_key => $value) {
            $this->$setting_key = $value;
        }

        // 设置是否应该重命名按钮。
        $this->order_button_text = apply_filters('woocommerce_Wechatpay_button_text', __('Proceed to Wechatpay', 'wprs-woo-wechatpay'));

        // 保存设置
        if (is_admin()) {
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        }

        // Hooks
        add_action('woocommerce_api_wprs-wechatpay-query', [$this, 'query_order']);
        add_action('woocommerce_api_wprs-wechatpay-notify', [$this, 'listen_notify']);
    }


    /**
     * 网关设置
     */
    public function init_form_fields()
    {
        $this->form_fields = [
            'enabled'     => [
                'title'   => __('Enable / Disable', 'wprs-woo-wechatpay'),
                'label'   => __('Enable this payment gateway', 'wprs-woo-wechatpay'),
                'type'    => 'checkbox',
                'default' => 'no',
            ],
            'environment' => [
                'title'       => __(' Wechatpay Sanbox Mode', 'wprs-woo-wechatpay'),
                'label'       => __('Enable Wechatpay Sanbox Mode', 'wprs-woo-wechatpay'),
                'type'        => 'checkbox',
                'description' => sprintf(__('Wechatpay sandbox can be used to test payments. Sign up for an account <a href="%s">here</a>',
                    'wprs-woo-wechatpay'),
                    'https://sandbox.Wechatpay.com'),
                'default'     => 'no',
            ],
            'title'       => [
                'title'   => __('Title', 'wprs-woo-wechatpay'),
                'type'    => 'text',
                'default' => __('Wechatpay', 'wprs-woo-wechatpay'),
            ],
            'description' => [
                'title'   => __('Description', 'wprs-woo-wechatpay'),
                'type'    => 'textarea',
                'default' => __('Pay securely using Wechat Pay', 'wprs-woo-wechatpay'),
                'css'     => 'max-width:350px;',
            ],
            'app_id'      => [
                'title'       => __('Wechat App Id', 'wprs-woo-wechatpay'),
                'type'        => 'text',
                'description' => __('Enter your Wechat App Id.', 'wprs-woo-wechatpay'),
            ],
            'mch_id'      => [
                'title'       => __('Wechat Mch Id', 'wprs-woo-wechatpay'),
                'type'        => 'text',
                'description' => __('Enter your Wechat Mch Id.', 'wprs-woo-wechatpay'),
            ],
            'api_key'     => [
                'title'       => __('Wechat Api Key', 'wprs-woo-wechatpay'),
                'type'        => 'text',
                'description' => __('Enter your Wechat Api Key', 'wprs-woo-wechatpay'),
            ],
        ];
    }


    /**
     * 管理选项
     */
    public function admin_options()
    { ?>

        <h3><?php echo ( ! empty($this->method_title)) ? $this->method_title : __('Settings', 'woocommerce'); ?></h3>

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

        $order            = new WC_Order($order_id);
        $this->notify_url = WC()->api_request_url('wprs-wechatpay-notify');

        do_action('wenprise_woocommerce_wechatpay_before_process_payment');

        // 调用响应的方法来处理支付
        try {

            $gateway = $this->get_gateway();

            // 获取购物车中的商品
            $order_cart = $order->get_items();

            // 构造购物车数组
            $cart = [];
            foreach ($order_cart as $product_id => $product) {
                $cart[] = [
                    'name'       => $product[ 'name' ],
                    'quantity'   => $product[ 'qty' ],
                    'price'      => $product[ 'line_total' ],
                    'product_id' => $product_id,
                ];
            }

            // 添加更多购物车数据
            if (($shipping_total = $order->get_total()) > 0) {
                $cart[] = [
                    'name'     => __('Shipping Fee', 'wprs-woo-wechatpay'),
                    'quantity' => 1,
                    'price'    => $shipping_total,
                ];
            }

            $order_no = $order->get_order_number();

            $order = apply_filters('woocommerce_wenprise_wechatpay_args',
                [
                    'body'             => '购买精益中国图书',
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
            $request  = $gateway->purchase($order);
            $response = $request->send();

            do_action('woocommerce_wenprise_wechatpay_before_payment_redirect', $response);

            // 微信支付, 显示二维码
            $code_url = $response->getCodeUrl();

            $qrCode = new QrCode($code_url);
            $qrCode->setSize(128);
            $qrCode->setMargin(0);

            $image_url = $qrCode->writeDataUri();

            if ( ! empty($_SERVER[ 'HTTP_X_REQUESTED_WITH' ]) && strtolower($_SERVER[ 'HTTP_X_REQUESTED_WITH' ]) == 'xmlhttprequest') {

                $data = [
                    'out_trade_no' => $order_no,
                    'url'          => $image_url,
                ];

                wp_send_json($data);

            } else {

                $args = [
                    'out_trade_no' => $order_no,
                    'image_url'    => $image_url,
                ];

                wc_get_template('payment/qrcode', $args);
            }


            // 返回支付连接，由 Woo Commerce 跳转到支付宝支付
            if ($response->isRedirect()) {
                return [
                    'result'   => 'success',
                    'redirect' => $response->getRedirectUrl(),
                ];
            } else {
                $error = $response->getMessage();
                $order->add_order_note(sprintf("%s Payments Failed: '%s'", $this->method_title, $error));
                wc_add_notice($error, 'error');
                $this->log($error);

                return [
                    'result'   => 'fail',
                    'redirect' => '',
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
     * 监听微信扫码支付返回
     */
    public function query_order()
    {

    }


    /**
     * 处理支付接口异步返回的信息
     */
    public function listen_notify()
    {

        print_r($_REQUEST);

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
                        sprintf(__('Wechatpay payment complete (Charge ID: %s)', 'wprs-woo-wechatpay'),
                            $transaction_ref
                        )
                    );

                    WC()->cart->empty_cart();
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
            $this->log->add('wprs-woo-wechatpay', $message);
        }
    }

}
