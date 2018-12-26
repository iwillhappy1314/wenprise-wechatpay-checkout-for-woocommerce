/**
 * 调用微信支付
 */
function wprs_wc_call_wechat_pay() {
  wx.config({
    debug    : false,
    appId    : WpWooWechatPaySign.appId,
    timestamp: WpWooWechatPaySign.timestamp,
    nonceStr : WpWooWechatPaySign.nonceStr,
    signature: WpWooWechatPaySign.signature,
    jsApiList: ['chooseWXPay'],
  });

  wx.ready(function() {

    var params = {
      'timestamp': WpWooWechatPayOrder.timeStamp,
      'nonceStr' : WpWooWechatPayOrder.nonceStr,
      'package'  : WpWooWechatPayOrder.package,
      'signType' : WpWooWechatPayOrder.signType,
      'paySign'  : WpWooWechatPayOrder.paySign,
      'success'  : function(res) {
        //alert(JSON.stringify(res))
        if (res.errMsg === 'chooseWXPay:ok') {
          window.location.href = WpWooWechatData.return_url;
        } else {
          alert('支付失败');
        }
      },
      'cancel'   : function(res) {
        alert('支付取消');
      },
      'fail'     : function(res) {
        alert('支付失败');
      },
    };

    wx.chooseWXPay(params);

  });

  wx.error(function(res) {
    alert(res.err_msg);
  });
}

wprs_wc_call_wechat_pay();
