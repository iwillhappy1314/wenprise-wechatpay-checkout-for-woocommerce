(function($) {
    var loopCnt = 50;
    var looptime = 1000; //ms
    var queryTimer = null;
    var countdownTimer = null;

    if (document.getElementById('js-wechatpay-confirm-modal')) {
        $.blockUI({
            message: $('#js-wechatpay-confirm-modal'),
            css    : {
                width : '500px',
                height: '460px',
            },
        });
    }

    var $paymentBox = $('#js-wprs-wc-wechatpay');
    var $qrcodeLoading = $('#js-wechatpay-qrcode-loading');
    var $qrcodeStatus = $('.wprs-wechatpay-qrcode-status');
    var $qrcodeCountdown = $('#js-wechatpay-qrcode-countdown');
    var $qrcodeExpired = $('#js-wechatpay-qrcode-expired');
    var $refreshQrcode = $('#js-wechatpay-refresh-qrcode');

    function getExpiresAt() {
        return parseInt($paymentBox.data('expires_at'), 10) || 0;
    }

    function shouldContinueQuery() {
        var expiresAt = getExpiresAt();

        if (!expiresAt) {
            return loopCnt-- > 0;
        }

        return Math.floor(Date.now() / 1000) <= expiresAt + 300;
    }

    function setQrcodeStatus(text, status) {
        $qrcodeStatus.
            removeClass('is-loading is-ready is-expired').
            addClass('is-' + status).
            css('display', 'flex');
        $qrcodeCountdown.text(text).show();
    }

    function showQrcodeLoading() {
        if ($paymentBox.length) {
            $paymentBox.empty().hide();
        }

        $qrcodeExpired.hide();
        setQrcodeStatus('正在生成二维码...', 'loading');
        $qrcodeLoading.css('display', 'flex');
    }

    function hideQrcodeLoading() {
        $qrcodeLoading.hide();
        $paymentBox.show();
    }

    function renderQrcode(callback) {
        var codeUrl = $paymentBox.data('code_url');

        if (!$paymentBox.length || !codeUrl || !$.fn.qrcode) {
            return;
        }

        showQrcodeLoading();
        window.setTimeout(function() {
            $paymentBox.empty().qrcode(codeUrl);
            hideQrcodeLoading();

            if (typeof callback === 'function') {
                callback();
            }
        }, 50);
    }

    function formatRemainingTime(seconds) {
        var minutes = Math.floor(seconds / 60);
        var remainingSeconds = seconds % 60;

        return minutes + ':' + ('0' + remainingSeconds).slice(-2);
    }

    function showQrcodeExpired() {
        setQrcodeStatus('二维码已过期，请刷新。', 'expired');
        $qrcodeExpired.show();

        if (countdownTimer) {
            clearInterval(countdownTimer);
            countdownTimer = null;
        }
    }

    function updateQrcodeCountdown() {
        var expiresAt = getExpiresAt();
        var remainingSeconds = expiresAt - Math.floor(Date.now() / 1000);

        if (!$paymentBox.length || !expiresAt) {
            return;
        }

        if (remainingSeconds <= 0) {
            showQrcodeExpired();
            return;
        }

        $paymentBox.show();
        $qrcodeExpired.hide();
        setQrcodeStatus('请在 ' + formatRemainingTime(remainingSeconds) + ' 内使用微信扫码支付', 'ready');
    }

    function startQrcodeCountdown() {
        if (countdownTimer) {
            clearInterval(countdownTimer);
        }

        updateQrcodeCountdown();
        countdownTimer = setInterval(updateQrcodeCountdown, 1000);
    }

    if ($paymentBox.length) {
        renderQrcode(startQrcodeCountdown);
    }

    function wprs_woo_wechatpay_query_order(manual = false) {
        var order_id = $paymentBox.data('order_id');
        var order_key = $paymentBox.data('order_key');

        if (!order_id || !order_key) {
            return;
        }

        $.ajax({
            type   : 'GET',
            url    : WpWooWechatData.query_url,
            data   : {
                order_id : order_id,
                order_key: order_key,
            },
            success: function(data) {
                if (data && data.data && (data.success === true || manual === true)) {
                    location.href = data.data;
                } else {
                    if (shouldContinueQuery()) {
                        queryTimer = setTimeout(wprs_woo_wechatpay_query_order, looptime);
                    }
                }
            },
            error  : function(data) {
                if (shouldContinueQuery()) {
                    queryTimer = setTimeout(wprs_woo_wechatpay_query_order, looptime);
                }
            },
        });
    }

    wprs_woo_wechatpay_query_order();

    /**
     * 支付成功后，如果没有自动跳转，点击按钮查询订单并跳转支付结果
     */
    $('#js-wechatpay-success, #js-wechatpay-fail').click(function() {
        $.blockUI({message: '<div style="padding: 1rem;">订单查询中...</div>'});

        wprs_woo_wechatpay_query_order(true);
    });

    $refreshQrcode.click(function() {
        var $button = $(this);

        $button.prop('disabled', true);
        showQrcodeLoading();

        $.ajax({
            type   : 'POST',
            url    : WpWooWechatData.refresh_url,
            data   : {
                order_id : $paymentBox.data('order_id'),
                order_key: $paymentBox.data('order_key'),
                nonce    : WpWooWechatData.nonce,
            },
            success: function(data) {
                if (data && data.success === true && data.data && data.data.url) {
                    location.href = data.data.url;
                } else if (data && data.success === true && data.data && data.data.qrcode) {
                    $paymentBox.data('expires_at', data.data.expires_at);
                    $paymentBox.attr('data-expires_at', data.data.expires_at);
                    $paymentBox.data('code_url', data.data.qrcode);
                    $paymentBox.attr('data-code_url', data.data.qrcode);

                    if (queryTimer) {
                        clearTimeout(queryTimer);
                        queryTimer = null;
                    }

                    loopCnt = 50;
                    $qrcodeExpired.hide();
                    renderQrcode(function() {
                        startQrcodeCountdown();
                        wprs_woo_wechatpay_query_order();
                    });
                } else {
                    hideQrcodeLoading();
                    showQrcodeExpired();
                    window.alert(data && data.data ? data.data : '二维码重新生成失败，请稍后重试。');
                }
            },
            error  : function() {
                hideQrcodeLoading();
                showQrcodeExpired();
                window.alert('二维码重新生成失败，请稍后重试。');
            },
            complete: function() {
                $button.prop('disabled', false);
            },
        });
    });

})(jQuery);
