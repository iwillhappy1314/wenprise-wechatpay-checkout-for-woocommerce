# Wenprise WeChatPay Payment Gateway For WooCommerce #
Contributors: iwillhappy1314
Donate link: https://www.wpzhiku.com/
Tags: Alipay, WooCommerce, woocommerce, payment, payment gateway, gateway, 微信, 微信支付, Wechat payment gateway, Wechat gateway, credit card, pay, online payment, shop, e-commerce, ecommerce
Requires PHP: 5.6.0
Requires at least: 3.9
Tested up to: 5.0
Stable tag: 1.0.3
License: GPL-2.0+

Wechat payment gateway for WooCommerce, WooCommerce 微信全功能支付网关。

## Description ##
**功能更全面的 WooCommerce 免费微信支付网关**，企业版，需要微信企业认证才可以使用。支持功能如下：

* 支持所有 WooCommerce 产品类型
* PC 端扫描二维码支付
* 移动端浏览器 H5 调起微信支付
* 微信端公众号支付，微信端微信自动登录，可兼容其他微信登录插件
* 在 WooCommerce 订单中直接通过微信退款，退款原路返回
* 货币不是人民币时，可以设置一个固定汇率


### Support 技术支持 ###

Email: amos@wpcio.com

## Installation ##

1. 上传插件到`/wp-content/plugins/` 目录，或在 WordPress 安装插件界面搜索 "Wenprise WeChatPay Gateway For WooCommerce"，点击安装。
2. 在插件管理菜单激活插件

## Upgrade Notice ##

更新之前，请先备份数据库。


## Frequently Asked Questions ##

### 怎么兼容其他微信登录插件？ ###
如果已经使用了其他微信登录插件，可以通过`wprs_wc_wechat_open_id` 这个 Filter 来修改支付插件使用的 open_id，修改下面代码中获取 open_id 的代码为对应登录插件中的代码即可。
```php
add_filter('wprs_wc_wechat_open_id', function(){
    $open_id = '';
    return $open_id;
});
```

## Screenshots ##
* Setting
* payment

## Changelog ##

### 1.0.3 ###
* 初次发布
* 降低 PHP 版本需求

### 1.0 ###
* 初次发布