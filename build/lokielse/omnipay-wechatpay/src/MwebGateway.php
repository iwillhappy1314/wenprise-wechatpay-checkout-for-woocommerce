<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 23-August-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Omnipay\WechatPay;

/**
 * Class MwebGateway
 * @package Wenprise\Wechatpay\Omnipay\WechatPay
 */
class MwebGateway extends BaseAbstractGateway
{
    public function getName()
    {
        return 'WechatPay Mweb';
    }


    public function getTradeType()
    {
        return 'MWEB';
    }
}
