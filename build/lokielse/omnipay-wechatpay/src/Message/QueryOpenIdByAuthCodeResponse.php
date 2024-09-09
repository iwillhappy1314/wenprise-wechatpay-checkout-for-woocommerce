<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 09-September-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Omnipay\WechatPay\Message;

/**
 * Class QueryOpenIdByAuthCodeResponse
 *
 * @package Wenprise\Wechatpay\Omnipay\WechatPay\Message
 * @link    https://pay.weixin.qq.com/wiki/doc/api/micropay.php?chapter=9_13&index=9
 */
class QueryOpenIdByAuthCodeResponse extends BaseAbstractResponse
{
    public function getOpenId()
    {
        $data = $this->getData();

        return isset($data['openid']) ? $data['openid'] : null;
    }
}
