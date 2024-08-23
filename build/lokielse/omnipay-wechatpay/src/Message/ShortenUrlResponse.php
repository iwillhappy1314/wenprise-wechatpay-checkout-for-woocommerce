<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 23-August-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Omnipay\WechatPay\Message;

/**
 * Class ShortenUrlResponse
 *
 * @package Wenprise\Wechatpay\Omnipay\WechatPay\Message
 * @link    https://pay.weixin.qq.com/wiki/doc/api/micropay.php?chapter=9_9&index=8
 */
class ShortenUrlResponse extends BaseAbstractResponse
{
    public function getShortUrl()
    {
        $data = $this->getData();

        return isset($data['short_url']) ? $data['short_url'] : null;
    }
}
