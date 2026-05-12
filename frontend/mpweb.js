var wprs_wechat_pay_triggered = false;

/**
 * 调用微信支付
 */
function wprs_wc_call_wechat_pay() {

    if (wprs_wechat_pay_triggered || typeof WpWooWechatPayOrder === 'undefined') {
        return;
    }

    wprs_wechat_pay_triggered = true;

    var $loading = jQuery('#js-wechat-pay-loading');
    var $buttonWrapper = jQuery('#js-wechat-pay-button-wrapper');

    var onBridgeReady = function() {
        WeixinJSBridge.invoke(
            'getBrandWCPayRequest', {
                'appId'    : WpWooWechatPayOrder.appId,
                'timeStamp': WpWooWechatPayOrder.timeStamp || WpWooWechatPayOrder.timestamp,
                'nonceStr' : WpWooWechatPayOrder.nonceStr,
                'package'  : WpWooWechatPayOrder.package,
                'signType' : WpWooWechatPayOrder.signType,
                'paySign'  : WpWooWechatPayOrder.paySign,
            },
            function(res) {
                wprs_wechat_pay_triggered = false;

                if (res.err_msg === 'get_brand_wcpay_request:ok') {
                    window.location.href = WpWooWechatData.return_url;
                } else {
                    $loading.hide();
                    $buttonWrapper.show();

                    if (res.err_msg !== 'get_brand_wcpay_request:cancel') {
                        alert('支付失败: ' + res.err_msg);
                    }
                }
            },
        );
    };

    if (typeof WeixinJSBridge === 'undefined') {
        if (document.addEventListener) {
            document.addEventListener('WeixinJSBridgeReady', onBridgeReady, false);
        } else if (document.attachEvent) {
            document.attachEvent('WeixinJSBridgeReady', onBridgeReady);
            document.attachEvent('onWeixinJSBridgeReady', onBridgeReady);
        }
    } else {
        onBridgeReady();
    }

}

// 立即尝试执行
wprs_wc_call_wechat_pay();

// 同时也注册到 ready 事件，双重保险
if (typeof jQuery !== 'undefined') {
    jQuery(function($) {
        wprs_wc_call_wechat_pay();
    });
}
