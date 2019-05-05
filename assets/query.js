(function($) {
  var loopCnt = 50;
  var looptime = 300; //ms

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

})(jQuery);