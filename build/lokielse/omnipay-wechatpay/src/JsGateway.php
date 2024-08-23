<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 23-August-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Omnipay\WechatPay;

/**
 * Class JsGateway
 * @package Wenprise\Wechatpay\Omnipay\WechatPay
 */
class JsGateway extends BaseAbstractGateway
{
    public function getName()
    {
        return 'WechatPay JS API/MP';
    }


    public function getTradeType()
    {
        return 'JSAPI';
    }
}
