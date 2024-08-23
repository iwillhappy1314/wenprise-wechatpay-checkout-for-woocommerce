<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 23-August-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Omnipay\WechatPay\Message;

use Wenprise\Wechatpay\Omnipay\WechatPay\Helper;

/**
 * Class CreateMicroOrderResponse
 *
 * @package Wenprise\Wechatpay\Omnipay\WechatPay\Message
 * @link    https://pay.weixin.qq.com/wiki/doc/api/micropay.php?chapter=9_10&index=1
 */
class CreateMicroOrderResponse extends BaseAbstractResponse
{

    /**
     * @var CreateOrderRequest
     */
    protected $request;


    public function getOrderData()
    {
        if ($this->isSuccessful()) {
            $data = [
                'app_id'    => $this->request->getAppId(),
                'mch_id'    => $this->request->getMchId(),
                'prepay_id' => $this->getPrepayId(),
                'package'   => 'Sign=WXPay',
                'nonce'     => md5(uniqid()),
                'timestamp' => time() . '',
            ];

            $data['sign'] = Helper::sign($data, $this->request->getApiKey());
        } else {
            $data = null;
        }

        return $data;
    }


    public function getPrepayId()
    {
        if ($this->isSuccessful()) {
            return $this->getData()['prepay_id'];
        } else {
            return null;
        }
    }


    public function getCodeUrl()
    {
        if ($this->isSuccessful() && $this->request->getTradeType() == 'NATIVE') {
            return $this->getData()['code_url'];
        } else {
            return null;
        }
    }
}
