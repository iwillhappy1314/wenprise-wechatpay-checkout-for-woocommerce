jQuery(document).ready(function($) {

  wx.config({
    debug    : false,
    appId    : WpWooWechatPaySign.appId,
    timestamp: WpWooWechatPaySign.timestamp,
    nonceStr : WpWooWechatPaySign.nonceStr,
    signature: WpWooWechatPaySign.signature,
    jsApiList: ['chooseWXPay'],
  });

  wx.ready(function() {

    $.ajax({
      url     : '',
      type    : 'POST',
      dataType: 'json',
      //data    : $('#wepay').serialize(),
      success : function() {
        var params = {
          'timestamp': WpWooWechatPayOrder.timeStamp,
          'nonceStr' : WpWooWechatPayOrder.nonceStr,
          'package'  : WpWooWechatPayOrder.package,
          'signType' : WpWooWechatPayOrder.signType,
          'paySign'  : WpWooWechatPayOrder.paySign,
        };
        wx.chooseWXPay(params);
      },
      error   : function(order) {
        alert(order.message);
      },
    });

    return false;

  });

});