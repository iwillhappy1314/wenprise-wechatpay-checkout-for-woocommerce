(function($) {
    var loopCnt = 50;
    var looptime = 300; //ms

    if (document.getElementById('js-wechatpay-confirm-modal')) {
        $.blockUI({
            message: $('#js-wechatpay-confirm-modal'),
            css    : {
                width : '500px',
                height: '400px',
            },
        });

        /**
         * 支付成功后，如果没有自动跳转，点击按钮查询订单并跳转支付结果
         */
        $('#js-wechatpay-success, #js-wechatpay-fail').click(function() {
            $.blockUI({message: '<div style="padding: 1rem;">订单查询中...</div>'});

            wprs_woo_wechatpay_query_order(true);
        });
    }

    function wprs_woo_wechatpay_query_order(manual = false) {
        var order_id = $('#js-wprs-wc-wechatpay').data('order_id');
        $.ajax({
            type   : 'GET',
            url    : WpWooWechatData.query_url,
            data   : {
                order_id: order_id,
            },
            success: function(data) {
                if (data && data.success === true || manual === true) {
                    location.href = data.data;
                } else {
                    if (loopCnt-- > 0) {
                        setTimeout(wprs_woo_wechatpay_query_order, looptime);
                    }
                }
            },
            error  : function(data) {
                if (loopCnt-- > 0) {
                    setTimeout(wprs_woo_wechatpay_query_order, looptime);
                }
            },
        });
    }

    wprs_woo_wechatpay_query_order();

    $('#js-wprs-wc-wechatpay').bind('click', function() {
        wprs_woo_wechatpay_query_order();
    });

    /**
     *
     * @type {{
     * to_wechatpay : (function() : boolean),
     * init : init,
     * $checkout_form : (jQuery|HTMLElement),
     * submit_error : submit_error,
     * blockOnSubmit : blockOnSubmit,
     * scroll_to_notices : scroll_to_notices,
     * detachUnloadEventsOnSubmit : detachUnloadEventsOnSubmit,
     * attachUnloadEventsOnSubmit : attachUnloadEventsOnSubmit}}
     */
    var wc_wechatpay_checkout = {
        $checkout_form: $('form.checkout'),

        init: function() {
            this.$checkout_form.on('checkout_place_order_wprs-wc-wechatpay', this.to_wechatpay);
        },

        to_wechatpay: function() {
            event.preventDefault();

            var $form = $(this);

            // 事先打开一个窗口，Ajax 成功后替换 location, 以解决弹出窗口被屏蔽的问题
            var wechatpay_window = window.open(WpWooWechatData.bridge_url, '_blank');

            $form.addClass('processing');

            wc_wechatpay_checkout.blockOnSubmit($form);

            // Attach event to block reloading the page when the form has been submitted
            wc_wechatpay_checkout.attachUnloadEventsOnSubmit();

            $.ajax({
                type    : 'POST',
                url     : wc_checkout_params.checkout_url,
                data    : $form.serialize(),
                dataType: 'json',
                success : function(result) {
                    // Detach the unload handler that prevents a reload / redirect
                    wc_wechatpay_checkout.detachUnloadEventsOnSubmit();

                    try {
                        if ('success' === result.result) {
                            if (-1 === result.redirect.indexOf('https://') || -1 ===
                                result.redirect.indexOf('http://')) {
                                wechatpay_window.location = result.payment_url;
                                wechatpay_window.focus();

                                window.location = result.redirect;

                                return false;
                            } else {
                                wechatpay_window.location = decodeURI(result.payment_url);
                                wechatpay_window.focus();

                                window.location = decodeURI(result.redirect);
                            }
                        } else if ('failure' === result.result) {
                            throw 'Result failure';
                        } else {
                            throw 'Invalid response';
                        }
                    } catch (err) {
                        // Reload page
                        if (true === result.reload) {
                            window.location.reload();
                            return;
                        }

                        // Trigger update in case we need a fresh nonce
                        if (true === result.refresh) {
                            $(document.body).trigger('update_checkout');
                        }

                        // Add new errors
                        if (result.messages) {
                            wc_wechatpay_checkout.submit_error(result.messages);
                        } else {
                            wc_wechatpay_checkout.submit_error(
                                '<div class="woocommerce-error">' + wc_checkout_params.i18n_checkout_error +
                                '</div>'); // eslint-disable-line max-len
                        }
                    }
                },
                error   : function(jqXHR, textStatus, errorThrown) {
                    // Detach the unload handler that prevents a reload / redirect
                    wc_wechatpay_checkout.detachUnloadEventsOnSubmit();

                    wc_wechatpay_checkout.submit_error('<div class="woocommerce-error">' + errorThrown + '</div>');
                },
            });

            return false;
        },

        blockOnSubmit: function($form) {
            var form_data = $form.data();

            if (1 !== form_data['blockUI.isBlocked']) {
                $form.block({
                    message   : null,
                    overlayCSS: {
                        background: '#fff',
                        opacity   : 0.6,
                    },
                });
            }
        },

        attachUnloadEventsOnSubmit: function() {
            $(window).on('beforeunload', this.handleUnloadEvent);
        },

        detachUnloadEventsOnSubmit: function() {
            $(window).unbind('beforeunload', this.handleUnloadEvent);
        },

        handleUnloadEvent: function(e) {
            // Modern browsers have their own standard generic messages that they will display.
            // Confirm, alert, prompt or custom message are not allowed during the unload event
            // Browsers will display their own standard messages

            // Check if the browser is Internet Explorer
            if ((navigator.userAgent.indexOf('MSIE') !== -1) || (!!document.documentMode)) {
                // IE handles unload events differently than modern browsers
                e.preventDefault();
                return undefined;
            }

            return true;
        },

        submit_error: function(error_message) {
            $('.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message').remove();
            wc_wechatpay_checkout.$checkout_form.prepend(
                '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">' + error_message + '</div>'); // eslint-disable-line max-len
            wc_wechatpay_checkout.$checkout_form.removeClass('processing').unblock();
            wc_wechatpay_checkout.$checkout_form.find('.input-text, select, input:checkbox').trigger('validate').blur();
            wc_wechatpay_checkout.scroll_to_notices();
            $(document.body).trigger('checkout_error');
        },

        scroll_to_notices: function() {
            var scrollElement = $('.woocommerce-NoticeGroup-updateOrderReview, .woocommerce-NoticeGroup-checkout');

            if (!scrollElement.length) {
                scrollElement = $('.form.checkout');
            }

            $.scroll_to_notices(scrollElement);
        },

    };

    console.log(typeof WpWooWechatData.bridge_url);

    if (WpWooWechatData.bridge_url) {
        wc_wechatpay_checkout.init();
    }

})(jQuery);