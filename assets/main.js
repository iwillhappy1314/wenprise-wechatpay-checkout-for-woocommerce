(function($) {
  var loopCnt = 50;
  var looptime = 300; //ms

  function wprs_woo_wechatpay_query_order() {
    var order_id = $('#js-wprs-wc-wechatpay').data('order_id');
    $.ajax({
      type: 'GET',
      url : wc_checkout_params.ajax_url,
      data: {
        order_id: order_id,
        action  : 'wprs-wc-wechatpay-query-order',
      },
    }).done(function(data) {
      data = JSON.parse(data);
      if (data && data.success === true) {
        location.href = data.redirect;
      } else {
        if (loopCnt-- > 0) {
          setTimeout(wprs_woo_wechatpay_query_order, looptime);
        }
      }
    }).fail(function() {

    }).always(function() {
    });
  }

  $(function() {
    wprs_woo_wechatpay_query_order();
  });

})(jQuery);