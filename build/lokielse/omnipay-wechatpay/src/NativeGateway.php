<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 23-August-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Omnipay\WechatPay;

/**
 * Class NativeGateway
 * @package Wenprise\Wechatpay\Omnipay\WechatPay
 */
class NativeGateway extends BaseAbstractGateway
{
    public function getName()
    {
        return 'WechatPay Native';
    }


    public function getTradeType()
    {
        return 'NATIVE';
    }


    /**
     * @param array $parameters
     *
     * @return \Wenprise\Wechatpay\Omnipay\WechatPay\Message\ShortenUrlRequest
     */
    public function shortenUrl($parameters = array())
    {
        return $this->createRequest('\Wenprise\Wechatpay\Omnipay\WechatPay\Message\ShortenUrlRequest', $parameters);
    }
}
