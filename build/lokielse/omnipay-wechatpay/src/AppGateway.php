<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 23-August-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Omnipay\WechatPay;

/**
 * Class AppGateway
 * @package Wenprise\Wechatpay\Omnipay\WechatPay
 */
class AppGateway extends BaseAbstractGateway
{
    public function getName()
    {
        return 'WechatPay App';
    }


    public function getTradeType()
    {
        return 'APP';
    }
}
