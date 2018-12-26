(function($) {
  var loopCnt = 50;
  var looptime = 300; //ms

  function wprs_woo_wechatpay_query_order() {
    var order_id = $('#js-wprs-wc-wechatpay').data('order_id');
    $.ajax({
      type   : 'GET',
      url    : WpWooWechatData.query_url,
      data   : {
        order_id: order_id,
      },
      success: function(data) {
        if (data && data.success === true) {
          location.href = data.redirect;
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

})(jQuery);