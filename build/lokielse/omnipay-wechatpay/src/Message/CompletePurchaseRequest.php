<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 23-August-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Omnipay\WechatPay\Message;

use Wenprise\Wechatpay\Omnipay\Common\Message\ResponseInterface;
use Wenprise\Wechatpay\Omnipay\WechatPay\Helper;

/**
 * Class CompletePurchaseRequest
 *
 * @package Wenprise\Wechatpay\Omnipay\WechatPay\Message
 * @link    https://pay.weixin.qq.com/wiki/doc/api/app/app.php?chapter=9_7&index=3
 * @method  CompletePurchaseResponse send()
 */
class CompletePurchaseRequest extends BaseAbstractRequest
{
    public function setRequestParams($requestParams)
    {
        $this->setParameter('request_params', $requestParams);
    }


    /**
     * Send the request with specified data
     *
     * @param  mixed $data The data to send
     *
     * @return ResponseInterface
     */
    public function sendData($data)
    {
        $data = $this->getData();
        $sign = Helper::sign($data, $this->getApiKey());

        $responseData = array();

        if (isset($data['sign']) && $data['sign'] && $sign === $data['sign']) {
            $responseData['sign_match'] = true;
        } else {
            $responseData['sign_match'] = false;
        }

        if ($responseData['sign_match'] && isset($data['result_code']) && $data['result_code'] == 'SUCCESS') {
            $responseData['paid'] = true;
        } else {
            $responseData['paid'] = false;
        }

        return $this->response = new CompletePurchaseResponse($this, $responseData);
    }


    /**
     * Get the raw data array for this message. The format of this varies from gateway to
     * gateway, but will usually be either an associative array, or a SimpleXMLElement.
     *
     * @return mixed
     */
    public function getData()
    {
        $data = $this->getRequestParams();

        if (is_string($data)) {
            $data = Helper::xml2array($data);
        }

        return $data;
    }


    public function getRequestParams()
    {
        return $this->getParameter('request_params');
    }
}
