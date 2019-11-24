<?php

if ( ! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use Endroid\QrCode\QrCode;
use Omnipay\Omnipay;

require WENPRISE_WECHATPAY_PATH . 'jssdk.php';

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
    public $app_secret = '';

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
    public $is_debug_mod = '';

    /**
     * 网关支持的功能
     *
     * @var array
     */
    public $supports = ['products', 'refunds'];


    function __construct()
    {

        // 支付方法的全局 ID
        $this->id = WENPRISE_WECHATPAY_WOOCOMMERCE_ID;

        // 支付网关页面显示的支付网关标题
        $this->method_title = __("Wechat Pay", 'wprs-wc-wechatpay');

        // 支付网关设置页面显示的支付网关标题
        $this->method_description = __("Wechat Pay payment gateway for WooCommerce", 'wprs-wc-wechatpay');

        // 前端显示的支付网关名称
        $this->title = __("Wechat Pay", 'wprs-wc-wechatpay');

        // 支付网关标题
        $this->icon = apply_filters('omnipay_wechat_pay_icon', WENPRISE_WECHATPAY_ASSETS_URL . "wechatpay.png");

        $this->supports = ['products', 'refunds'];

        $this->debug_active = false;

        $this->has_fields = false;

        $this->description = $this->get_option('description');

        $this->current_currency = get_option('woocommerce_currency');

        $this->multi_currency_enabled = in_array('woocommerce-multilingual/wpml-woocommerce.php',
                apply_filters('active_plugins', get_option('active_plugins'))) && get_option('icl_enable_multi_currency') == 'yes';

        $this->exchange_rate = $this->get_option('exchange_rate');

        // 转换设置为变量以方便使用
        foreach ($this->settings as $setting_key => $value) {
            $this->$setting_key = $value;
        }

        // 设置是否应该重命名按钮。
        $this->order_button_text = apply_filters('woocommerce_wechatpay_button_text', __('Proceed to Wechatpay', 'wprs-wc-wechatpay'));

        // 被 init_settings() 加载的基础设置
        $this->init_form_fields();

        $this->init_settings();

        // 保存设置
        if (is_admin()) {
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        }

        // 仪表盘通知
        add_action('admin_notices', [$this, 'requirement_checks']);

        // 添加 URL
        add_action('woocommerce_api_wprs-wc-wechatpay-query', [$this, 'query_order']);
        add_action('woocommerce_api_wprs-wc-wechatpay-notify', [$this, 'listen_notify']);
        add_action('woocommerce_api_wprs-wc-wechatpay-auth', [$this, 'wechat_auth']);

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
            'enabled'      => [
                'title'   => __('Enable / Disable', 'wprs-wc-wechatpay'),
                'label'   => __('Enable this payment gateway', 'wprs-wc-wechatpay'),
                'type'    => 'checkbox',
                'default' => 'no',
            ],
            // 'enabled_auto_login' => [
            //     'title'   => __('Enable / Disable', 'wprs-wc-wechatpay'),
            //     'label'   => __('Enable auto login in wechat Official Accounts', 'wprs-wc-wechatpay'),
            //     'type'    => 'checkbox',
            //     'default' => 'no',
            // ],
            // 'environment' => [
            //     'title'       => __(' Wechatpay Sanbox Mode', 'wprs-wc-wechatpay'),
            //     'label'       => __('Enable Wechatpay Sanbox Mode', 'wprs-wc-wechatpay'),
            //     'type'        => 'checkbox',
            //     'description' => sprintf(__('Wechatpay sandbox can be used to test payments. Sign up for an account <a href="%s">here</a>',
            //         'wprs-wc-wechatpay'),
            //         'https://sandbox.Wechatpay.com'),
            //     'default'     => 'no',
            // ],
            'title'        => [
                'title'   => __('Title', 'wprs-wc-wechatpay'),
                'type'    => 'text',
                'default' => __('Wechatpay', 'wprs-wc-wechatpay'),
            ],
            'description'  => [
                'title'   => __('Description', 'wprs-wc-wechatpay'),
                'type'    => 'textarea',
                'default' => __('Pay securely using Wechat Pay', 'wprs-wc-wechatpay'),
                'css'     => 'max-width:350px;',
            ],
            'app_id'       => [
                'title'       => __('Wechat App Id', 'wprs-wc-wechatpay'),
                'type'        => 'text',
                'description' => __('Enter your Wechat App Id. 支付授权目录和 H5 支付域名为网站首页地址，扫码回调链接：',
                        'wprs-wc-wechatpay') . home_url('wc-api/wprs-wc-wechatpay-notify/'),
            ],
            'app_secret'   => [
                'title'       => __('Wechat App Secret', 'wprs-wc-wechatpay'),
                'type'        => 'text',
                'description' => __('Enter your Wechat App Secret.', 'wprs-wc-wechatpay'),
            ],
            'mch_id'       => [
                'title'       => __('Wechat Mch Id', 'wprs-wc-wechatpay'),
                'type'        => 'text',
                'description' => __('Enter your Wechat Mch Id.', 'wprs-wc-wechatpay'),
            ],
            'api_key'      => [
                'title'       => __('Wechat Api Key', 'wprs-wc-wechatpay'),
                'type'        => 'text',
                'description' => __('Enter your Wechat Api Key', 'wprs-wc-wechatpay'),
            ],
            'cert_path'    => [
                'title'       => __('apiclient_cert.pem path', 'wprs-wc-wechatpay'),
                'type'        => 'text',
                'description' => __('Enter the absolute apiclient_cert.pem path that can access by the site, Used when refund, Ex: /home/apiclient_cert.pem，For security *DO NOT* place it in public dir',
                    'wprs-wc-wechatpay'),
            ],
            'key_path'     => [
                'title'       => __('apiclient_key.pem Path', 'wprs-wc-wechatpay'),
                'type'        => 'text',
                'description' => __('Enter the absolute apiclient_key.pem path that can access by the site, Used when refund，Ex: /home/apiclient_key.pem，For security *DO NOT* place it in public dir',
                    'wprs-wc-wechatpay'),
            ],
            'is_debug_mod' => [
                'title'       => __('Debug Mode', 'wprs-wc-wechatpay'),
                'label'       => __('Enable debug mod', 'wprs-wc-wechatpay'),
                'type'        => 'checkbox',
                'description' => sprintf(__('If checked, plugin will show program errors in frontend.', 'wprs-wc-wechatpay')),
                'default'     => 'no',
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
        $order_id = get_query_var('order-pay');
        $order    = wc_get_order($order_id);

        $jssdk       = new JSSDK($this->app_id, $this->app_secret);
        $signPackage = $jssdk->GetSignPackage();
        $order_data  = get_post_meta($order_id, 'wprs_wc_wechat_order_data', true);

        if (isset($order->payment_method) && "wprs-wc-wechatpay" == $order->payment_method) {
            if (is_checkout_pay_page() && ! isset($_GET[ 'pay_for_order' ])) {

                if (wprs_is_wechat()) {
                    wp_enqueue_script('wprs-wc-wechatpay-js-sdk', 'https://res.wx.qq.com/open/js/jweixin-1.4.0.js', ['jquery'], null, true);
                    wp_enqueue_script('wprs-wc-wechatpay-scripts', plugins_url('/frontend/mpweb.js', __FILE__), ['jquery'], null, true);
                }

                wp_enqueue_style('wprs-wc-wechatpay-style', plugins_url('/frontend/styles.css', __FILE__), [], null, false);
                wp_enqueue_script('wprs-wc-wechatpay-scripts', plugins_url('/frontend/query.js', __FILE__), ['jquery', 'jquery-blockui'], null, true);

                wp_localize_script('wprs-wc-wechatpay-scripts', 'WpWooWechatPaySign', $signPackage);
                wp_localize_script('wprs-wc-wechatpay-scripts', 'WpWooWechatPayOrder', $order_data);
                wp_localize_script('wprs-wc-wechatpay-scripts', 'WpWooWechatData', [
                    'return_url' => $this->get_return_url($order),
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
                    'wprs-wc-wechatpay'),
                    admin_url('admin.php?page=wc-settings&tab=checkout&section=wprs-wc-wechatpay#woocommerce_wprs-wc-wechatpay_exchange_rate'),
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
        if (wp_is_mobile()) {
            if (wprs_is_wechat()) {
                $gateway = Omnipay::create('WechatPay_Js');
            } else {
                $gateway = Omnipay::create('WechatPay_Mweb');
            }
        } else {
            $gateway = Omnipay::create('WechatPay_Native');
        }

        $gateway->setAppId(trim($this->app_id));
        $gateway->setMchId(trim($this->mch_id));

        // 这个 key 需要在微信商户里面单独设置，而吧是微信服务号里面的 key
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

        $order    = wc_get_order($order_id);
        $order_no = $order->get_order_number();
        $total    = $this->get_order_total();

        $exchange_rate = floatval($this->get_option('exchange_rate'));
        if ($exchange_rate <= 0) {
            $exchange_rate = 1;
        }

        $total = round($total * $exchange_rate, 2);

        // 修改 Open ID 的获取方法，主要兼容其他微信登录
        $open_id = apply_filters('wprs_wc_wechat_open_id', get_user_meta(get_current_user_id(), 'wprs_wc_wechat_open_id', true));

        do_action('wenprise_woocommerce_wechatpay_before_process_payment');

        // 调用响应的方法来处理支付
        $gateway = $this->get_gateway();

        $order_data = apply_filters('woocommerce_wenprise_wechatpay_args',
            [
                'body'             => '网站订单',
                'out_trade_no'     => $order_no,
                'total_fee'        => $total * 100,
                'spbill_create_ip' => wprs_get_ip(),
                'fee_type'         => 'CNY',
            ]
        );

        if (wprs_is_wechat()) {
            $order_data[ 'open_id' ] = $open_id;
        }

        // 生成订单并发送支付
        /**
         * @var \Omnipay\WechatPay\Message\CreateOrderRequest  $request
         * @var \Omnipay\WechatPay\Message\CreateOrderResponse $response
         */
        $request  = $gateway->purchase($order_data);
        $response = $request->send();

        do_action('woocommerce_wenprise_wechatpay_before_payment_redirect', $response);

        wc_empty_cart();

        // 微信支付, 显示二维码
        if ($response->isSuccessful()) {

            if (wp_is_mobile()) {
                if (wprs_is_wechat()) {
                    update_post_meta($order_id, 'wprs_wc_wechat_order_data', $response->getJsOrderData());

                    $redirect_url = $order->get_checkout_payment_url(true);
                } else {
                    $redirect_url = $response->getMwebUrl() . '&redirect_url=' . urlencode($order->get_checkout_payment_url(true) . '&from=wap');
                    update_post_meta($order_id, 'wprs_wc_wechat_mweb_url', $redirect_url);
                }

            } else {
                $code_url = $response->getCodeUrl();
                update_post_meta($order_id, 'wprs_wc_wechat_code_url', $code_url);

                $redirect_url = $order->get_checkout_payment_url(true);
            }

            return [
                'result'   => 'success',
                'redirect' => $redirect_url,
            ];

        } else {

            $error = $response->getData();
            $this->log($error);

            if ($this->is_debug_mod == 'yes') {
                wc_add_notice($error[ 'return_msg' ], 'error');

                return [
                    'result'   => 'failure',
                    'redirect' => $error[ 'return_msg' ],
                ];
            }

            return [
                'result'   => 'failure',
                'redirect' => __('WeChat payment configuration error, please contact us.', 'wprs-wc-wechatpay'),
            ];

        }

    }


    /**
     * 处理退款
     *
     * @param int    $order_id
     * @param null   $amount
     * @param string $reason
     *
     * @return bool|\WP_Error
     */
    public function process_refund($order_id, $amount = null, $reason = '')
    {
        $gateway = $this->get_gateway();
        $gateway->setCertPath($this->cert_path);
        $gateway->setKeyPath($this->key_path);

        $order = wc_get_order($order_id);
        $total = $order->get_total();

        $exchange_rate = floatval($this->get_option('exchange_rate'));
        if ($exchange_rate <= 0) {
            $exchange_rate = 1;
        }

        $total      = round($total * $exchange_rate, 2);
        $refund_fee = round($amount * $exchange_rate, 2);

        if ($refund_fee <= 0 || $refund_fee > $total) {
            false;
        }

        /** @var \Omnipay\WechatPay\Message\BaseAbstractRequest $request */
        $request = $gateway->refund([
            'transaction_id' => $order->get_transaction_id(),
            'out_trade_no'   => $order_id,
            'out_refund_no'  => 'refund_' . $order_id . '_' . mt_rand(1000, 9999),
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
        /** @var \Omnipay\WechatPay\Message\CompletePurchaseRequest $request */
        $request = $gateway->completePurchase($options);

        try {

            $response = $request->send();
            $data     = $response->getRequestData();

            $order = wc_get_order($data[ 'out_trade_no' ]);

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

                if ($this->is_debug_mod == 'yes') {
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

        $form     = isset($_GET[ 'from' ]) ? $_GET[ 'from' ] : false;
        $code_url = get_post_meta($order_id, 'wprs_wc_wechat_code_url', true);

        if ($form == 'wap') {
            // H5 支付需要手动检查订单是否完成
            echo '<div class="buttons has-addons">';
            echo '<button class="button u-width-50" id="js-wprs-wc-wechatpay" data-order_id="' . $order_id . '">已支付</button>';
            echo '<a class="button u-width-50" href="' . get_post_meta($order_id, 'wprs_wc_wechat_mweb_url', true) . '">继续支付</a>';
            echo '</div>';

        } else {
            if (wprs_is_wechat()) {
                echo '<button class="button" onclick="wprs_wc_call_wechat_pay()" >立即支付</button>';
            }

            if ($code_url) {
                $qrCode = new QrCode($code_url);
                $qrCode->setSize(256);
                $qrCode->setMargin(0);
                ?>

                <div id="js-wechatpay-confirm-modal" class="rs-confirm-modal" style="display: none;">

                    <div class="rs-modal">
                        <header class="rs-modal__header">
                            <?= __('Please scan the QR code with WeChat to finish the payment.', 'wprs-wc-wechatpay'); ?>
                        </header>
                        <div class="rs-modal__content">
                            <img id="js-wprs-wc-wechatpay" class="rs-image" data-order_id="<?= $order_id; ?>" src="<?= $qrCode->writeDataUri(); ?>"
                                 alt="微信支付二维码" />
                        </div>
                        <footer class="rs-modal__footer">
                            <input type="button" id="js-wechatpay-success" class="button alt is-primary" value="支付成功" />
                            <input type="button" id="js-wechatpay-fail" class="button" value="支付失败" />
                        </footer>
                    </div>

                </div>
            <?php }
        }

    }


    /**
     * 监听微信扫码支付返回
     */
    public function query_order()
    {
        $order_id = isset($_GET[ 'order_id' ]) ? $_GET[ 'order_id' ] : false;
        $order    = wc_get_order($order_id);

        if ($order) {
            if ($order->is_paid()) {
                wp_send_json_success($order->get_checkout_order_received_url());
            } else {
                wp_send_json_error($order->get_checkout_payment_url());
            }
        } else {
            wp_send_json_error($order->get_checkout_payment_url());
        }

    }


    /**
     * 获取微信 Code
     *
     * @param null $code
     *
     * @return array|bool|mixed|object
     */
    function get_access_token($code = null)
    {
        if ( ! $code) {
            return false;
        }

        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $this->app_id . '&secret=' . $this->app_secret . '&code=' . $code . '&grant_type=authorization_code';

        $response = file_get_contents($url);

        return json_decode($response, true);
    }


    /**
     * 微信公众号授权
     */
    function wechat_auth()
    {

        $code = isset($_GET[ 'code' ]) ? $_GET[ 'code' ] : false;

        if ( ! $code) {

            $url = $this->get_auth_url();
            wp_redirect($url);

        } else {

            $json_token = $this->get_access_token($code);

            // 微信公众号授权失败时的提示信息
            if ( ! isset($json_token[ 'access_token' ])) {
                $this->log($json_token[ 'errmsg' ]);

                if ($this->is_debug_mod == 'yes') {
                    wp_die($json_token[ 'errmsg' ]);
                } else {
                    wp_die(__('Wechat auth failed, please try again later or contact us.', 'wprs-wc-wechatpay'));
                }
            }

            $info_url  = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $json_token[ 'access_token' ] . '&openid=' . $json_token[ 'openid' ];
            $user_info = json_decode(file_get_contents($info_url), true);
            $wechat_id = $user_info[ "openid" ];

            if (is_user_logged_in()) {

                $this_user = wp_get_current_user();

                update_user_meta($this_user->ID, $this->app_id, $wechat_id);
                update_user_meta($this_user->ID, 'wechat_avatar', $user_info[ 'headimgurl' ]);

                wp_redirect(home_url());

            } else {

                $oauth_user = get_users(['meta_key' => 'wprs_wc_wechat_open_id', 'meta_value' => $wechat_id]);

                if (is_wp_error($oauth_user) || ! count($oauth_user)) {

                    $username        = $user_info[ 'nickname' ];
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
                    update_user_meta($user_id, 'wechat_avatar', $user_info[ 'headimgurl' ]);

                    wp_redirect(home_url());

                } else {

                    wp_set_auth_cookie($oauth_user[ 0 ]->ID, true);
                    $redirect_url = isset($_GET[ 'state' ]) ? $_GET[ 'state' ] : home_url();

                    wp_redirect($redirect_url);

                }
            }

        }

    }


    /**
     * 获取授权 URL
     *
     * @return string
     */
    public function get_auth_url()
    {

        $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $this->app_id . '&redirect_uri=' . urlencode(WC()->api_request_url('wprs-wc-wechatpay-auth')) . '&response_type=code&scope=snsapi_userinfo&state=' . urlencode(wprs_get_current_url()) . '#wechat_redirect';

        return $url;

    }


    /**
     * Logger 辅助功能
     *
     * @param $message
     */
    public function log($message)
    {
        if ($this->is_debug_mod == 'yes') {
            if ( ! ($this->log)) {
                $this->log = new WC_Logger();
            }
            $this->log->add('wprs-wc-wechatpay', $message);
        }
    }

}