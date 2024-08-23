<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 23-August-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Omnipay\WechatPay\Message;

use Wenprise\Wechatpay\Omnipay\Common\Exception\InvalidRequestException;
use Wenprise\Wechatpay\Omnipay\Common\Message\ResponseInterface;
use Wenprise\Wechatpay\Omnipay\WechatPay\Helper;

/**
 * Class ShortenUrlRequest
 *
 * @package Wenprise\Wechatpay\Omnipay\WechatPay\Message
 * @link    https://pay.weixin.qq.com/wiki/doc/api/micropay.php?chapter=9_9&index=8
 * @method  ShortenUrlResponse send()
 */
class ShortenUrlRequest extends BaseAbstractRequest
{
    protected $endpoint = 'https://api.mch.weixin.qq.com/tools/shorturl';


    /**
     * Get the raw data array for this message. The format of this varies from gateway to
     * gateway, but will usually be either an associative array, or a SimpleXMLElement.
     * @return mixed
     * @throws InvalidRequestException
     */
    public function getData()
    {
        $this->validate('app_id', 'mch_id', 'long_url');

        $data = array(
            'appid'     => $this->getAppId(),
            'mch_id'    => $this->getMchId(),
            'sub_mch_id'=> $this->getSubMchId(),
            'long_url'  => $this->getLongUrl(),
            'nonce_str' => md5(uniqid()),
        );

        $data = array_filter($data);

        $data['sign'] = Helper::sign($data, $this->getApiKey());

        return $data;
    }


    /**
     * @return mixed
     */
    public function getLongUrl()
    {
        return $this->getParameter('long_url');
    }


    /**
     * @param mixed $longUrl
     */
    public function setLongUrl($longUrl)
    {
        $this->setParameter('long_url', $longUrl);
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
        $request      = $this->httpClient->request('POST', $this->endpoint, [], Helper::array2xml($data));
        $response     = $request->getBody();
        $responseData = Helper::xml2array($response);

        return $this->response = new ShortenUrlResponse($this, $responseData);
    }
}
