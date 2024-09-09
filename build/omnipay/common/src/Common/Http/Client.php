<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 09-September-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Omnipay\Common\Http;

use function Wenprise\Wechatpay\GuzzleHttp\Psr7\str;
use Wenprise\Wechatpay\Http\Client\HttpClient;
use Wenprise\Wechatpay\Http\Discovery\HttpClientDiscovery;
use Wenprise\Wechatpay\Http\Discovery\MessageFactoryDiscovery;
use Wenprise\Wechatpay\Http\Message\RequestFactory;
use Wenprise\Wechatpay\Omnipay\Common\Http\Exception\NetworkException;
use Wenprise\Wechatpay\Omnipay\Common\Http\Exception\RequestException;
use Wenprise\Wechatpay\Psr\Http\Message\RequestInterface;
use Wenprise\Wechatpay\Psr\Http\Message\ResponseInterface;
use Wenprise\Wechatpay\Psr\Http\Message\StreamInterface;
use Wenprise\Wechatpay\Psr\Http\Message\UriInterface;

class Client implements ClientInterface
{
    /**
     * The Http Client which implements `public function sendRequest(RequestInterface $request)`
     * Note: Will be changed to PSR-18 when released
     *
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var RequestFactory
     */
    private $requestFactory;

    public function __construct($httpClient = null, RequestFactory $requestFactory = null)
    {
        $this->httpClient = $httpClient ?: HttpClientDiscovery::find();
        $this->requestFactory = $requestFactory ?: MessageFactoryDiscovery::find();
    }

    /**
     * @param $method
     * @param $uri
     * @param array $headers
     * @param string|array|resource|StreamInterface|null $body
     * @param string $protocolVersion
     * @return ResponseInterface
     * @throws \Wenprise\Wechatpay\Http\Client\Exception
     */
    public function request(
        $method,
        $uri,
        array $headers = [],
        $body = null,
        $protocolVersion = '1.1'
    ) {
        $request = $this->requestFactory->createRequest($method, $uri, $headers, $body, $protocolVersion);

        return $this->sendRequest($request);
    }

    /**
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws \Wenprise\Wechatpay\Http\Client\Exception
     */
    private function sendRequest(RequestInterface $request)
    {
        try {
            return $this->httpClient->sendRequest($request);
        } catch (\Wenprise\Wechatpay\Http\Client\Exception\NetworkException $networkException) {
            throw new NetworkException($networkException->getMessage(), $request, $networkException);
        } catch (\Exception $exception) {
            throw new RequestException($exception->getMessage(), $request, $exception);
        }
    }
}
