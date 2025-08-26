<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 09-September-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Omnipay\WechatPay\Message;

use Wenprise\Wechatpay\Omnipay\Common\Message\AbstractResponse;

/**
 * Class CompleteRefundResponse
 *
 * @package Wenprise\Wechatpay\Omnipay\WechatPay\Message
 * @link    https://pay.weixin.qq.com/wiki/doc/api/app/app.php?chapter=9_16&index=10
 */
class CompleteRefundResponse extends AbstractResponse
{
    public function isSuccessful()
    {
        return $this->isRefunded();
    }

    public function isRefunded()
    {
        $data = $this->getData();

        return $data['refunded'];
    }


    public function isSignMatch()
    {
        $data = $this->getData();

        return $data['sign_match'];
    }


    public function getRequestData()
    {
        return $this->request->getData();
    }
}
