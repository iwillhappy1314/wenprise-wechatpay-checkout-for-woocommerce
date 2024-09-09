<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 09-September-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Omnipay\WechatPay;

/**
 * Class PosGateway
 * @package Wenprise\Wechatpay\Omnipay\WechatPay
 */
class PosGateway extends BaseAbstractGateway
{
    public function getName()
    {
        return 'WechatPay Pos';
    }


    /**
     * @param array $parameters
     *
     * @return \Wenprise\Wechatpay\Omnipay\WechatPay\Message\CreateOrderRequest
     */
    public function purchase($parameters = array())
    {
        $parameters['trade_type'] = $this->getTradeType();

        return $this->createRequest('\Wenprise\Wechatpay\Omnipay\WechatPay\Message\CreateMicroOrderRequest', $parameters);
    }


    /**
     * @param array $parameters
     *
     * @return \Wenprise\Wechatpay\Omnipay\WechatPay\Message\QueryOpenIdByAuthCodeRequest
     */
    public function queryOpenId($parameters = array())
    {
        return $this->createRequest('\Wenprise\Wechatpay\Omnipay\WechatPay\Message\QueryOpenIdByAuthCodeRequest', $parameters);
    }
}
