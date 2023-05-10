# Wenprise WeChatPay Payment Gateway For WooCommerce #
Contributors: iwillhappy1314
Donate link: https://www.wpzhiku.com/
Tags: Alipay, WooCommerce, woocommerce, payment, payment gateway, gateway, 微信, 微信支付, Wechat payment gateway, Wechat gateway, credit card, pay, online payment, shop, e-commerce, ecommerce
Requires PHP: 7.1
Requires at least: 4.7
Tested up to: 6.2
WC requires at least: 3.5
WC tested up to: 7.7
Stable tag: 1.2.0
License: GPL-2.0+

Wechat payment gateway for WooCommerce, WooCommerce 微信免费全功能支付网关。

## Description ##
**功能更全面的 WooCommerce 免费微信支付网关**，企业版，需要微信企业认证才可以使用。支持功能如下：

* 支持所有 WooCommerce 产品类型
* PC 端扫描二维码支付
* 移动端浏览器 H5 调起微信支付
* 微信端公众号支付，需要安装微信登录插件，设置 open_id
* 支持作为小程序付款后端使用
* 在 WooCommerce 订单中直接通过微信退款，退款原路返回
* 货币不是人民币时，可以设置一个固定汇率
* 兼容 Wenprise Security 登录插件
* 兼容讯虎登录插件

### 付费设置服务 ###
如果你不想自己动手设置，或者自己设置有困难，可以购买我们的付费设置服务。
[WooCommerce支付宝插件设置服务](https://www.wpzhiku.com/product/woocommerce-zhi-fu-bao-cha/)

### 支付宝支付网关 ###
[Wenprise Alipay Payment Gateway For WooCommerce](https://wordpress.org/plugins/wenprise-alipay-checkout-for-woocommerce/)

### Support 技术支持 ###

Email: amos@wpcio.com

## Installation ##

1. 上传插件到`/wp-content/plugins/` 目录，或在 WordPress 安装插件界面搜索 "Wenprise WeChatPay Gateway For WooCommerce"，点击安装。
2. 在插件管理菜单激活插件

## Upgrade Notice ##

更新之前，请先备份数据库。


## Frequently Asked Questions ##


### 无法在微信公众号中支付？在微信中支付，提示「微信支付配置错误」？ ###

在微信公众号中，需要获取 open_id 才能使用此插件进行支付，如果您的网站已经实现了微信公众号授权登录，请参考下一个问题中的代码进行兼容。


### 怎么兼容其他微信登录插件？ ###
如果已经使用了其他微信登录插件，可以通过`wprs_wc_wechat_open_id` 这个 Filter 来修改支付插件使用的 open_id，修改下面代码中获取 open_id 的代码为对应登录插件中的代码即可。
```php
    add_filter('wprs_wc_wechat_open_id', function(){
        $open_id = '';
        return $open_id;
    });
```

### 怎么使用小程序登录功能？ ###

在小程序中，发送请求到url：/wc-api/wprs-wc-wechatpay-mini-app-login
```
wx.login({
	success(res) {
		if (res.code) {

			// 请求登录后端，获取open_id 和 session_key
			wx.request({
				url : config.getRootUrl + '/wc-api/wprs-wc-wechatpay-mini-app-login',
				data: {
					code: res.code,
				},
				success(res) {

					// 保存小程序登录信息
					wx.setStorageSync('open_id', res.data.data.openid);

					// 请求支付插件获取支付信息
					wx.request({
						url   : config.getRootUrl +  'wc-api/wprs-wc-wechatpay-mini-app-bridge',
						method: 'POST',
						data  : {
							open_id : res.data.data.openid,
							from    : 'mini_app',
							order_id: payData.order_id,
						},
						success(res) {

							var payment_data = res.data.data;

							// 发送支付请求，在小程序中调起支付
							wx.requestPayment({
								timeStamp: payment_data.timeStamp,
								nonceStr : payment_data.nonceStr,
								package  : decodeURIComponent(payment_data.package),
								signType : 'MD5',
								paySign  : payment_data.paySign,
								success(res) {
									console.log('支付成功', res);
									// 支付成功以后，再跳回webview页，并把支付成功状态传回去
									wx.navigateTo({
										url: '../webview/webview?src=' + encodeURI(payment_data.return_url),
									});
								},
								fail(res) {
									console.log('支付失败', res);
								},
							});

						},
					});

				},
			});
		} else {
			console.log('登录失败！' + res.errMsg);
		}
	},
});
```


## Screenshots ##
* Setting
* payment

## Changelog ##
### 1.2.0 ###
* 兼容性升级

### 1.1.2 ###
* 兼容讯虎登录插件
* 兼容 Wenprise Security 登录插件

### 1.0.15 ###
* 移动端浏览器支付增加跳转中间页，解决某些情况下无法验证支付状态的问题。

### 1.0.14 ###
* 更新 readme

### 1.0.13 ###
* 小错误修复

### 1.0.12 ###
* 优化订单号显示方式
* 添加订单号前缀设置选项
* 微信登录启用设置问题修复

### 1.0.10 ###
* Wechat auth bugfix

### 1.0.9 ###
* 添加微信登录失败时的提示信息

### 1.0.8 ###
* Bugfix

### 1.0.6 ###
* Bugfix

### 1.0.4 ###
* 修复某些情况下图标不显示的问题

### 1.0.3 ###
* 初次发布
* 降低 PHP 版本需求

### 1.0 ###
* 初次发布
