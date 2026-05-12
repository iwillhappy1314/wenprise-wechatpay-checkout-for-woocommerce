(function($) {
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
        var val = $paymentBox.data('expires_at');
        if (!val) {
            return 0;
        }

        // 处理日期字符串格式 (如 "2026-05-12 14:37:42")
        if (typeof val === 'string' && val.indexOf('-') !== -1) {
            return Math.floor(Date.parse(val.replace(/-/g, '/')) / 1000);
        }

        return parseInt(val, 10) || 0;
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

    var order_id = $paymentBox.attr('data-order_id');
    var order_key = $paymentBox.attr('data-order_key');
    var query_attempts = 0;

    function wprs_woo_wechatpay_query_order(manual) {
        if (typeof manual === 'undefined') {
            manual = false;
        }

        if (!order_id || !order_key) {
            return;
        }

        query_attempts++;
        if (query_attempts > 100 && !manual) {
            return;
        }

        $.ajax({
            type    : 'GET',
            url     : WpWooWechatData.query_url,
            data    : {
                order_id : order_id,
                order_key: order_key,
                t        : Date.now(), // 增加时间戳，防止移动端浏览器缓存请求
            },
            dataType: 'json',
            complete: function(xhr) {
                var data = xhr.responseJSON;

                if (data && data.success === true && data.data) {
                    location.href = data.data;
                } else {
                    if (!manual) {
                        setTimeout(function() {
                            wprs_woo_wechatpay_query_order();
                        }, 2000);
                    }
                }
            },
        });
    }

    if ($paymentBox.length) {
        wprs_woo_wechatpay_query_order();
    }

    /**
     * 支付成功后，如果没有自动跳转，点击按钮查询订单并跳转支付结果
     */
    $('#js-wechatpay-success, #js-wechatpay-fail').click(function() {
        $.blockUI({message: '<div style="padding: 1rem;">订单查询中...</div>'});

        wprs_woo_wechatpay_query_order(true);
    });

    $refreshQrcode.click(function() {
        var $button = $(this);
        var loopCount;

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

                    loopCount = 128;
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
