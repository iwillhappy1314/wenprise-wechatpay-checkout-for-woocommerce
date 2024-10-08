<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 09-September-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Omnipay\Common\Http;

use Wenprise\Wechatpay\Omnipay\Common\Http\Exception\NetworkException;
use Wenprise\Wechatpay\Omnipay\Common\Http\Exception\RequestException;
use Wenprise\Wechatpay\Psr\Http\Message\ResponseInterface;
use Wenprise\Wechatpay\Psr\Http\Message\StreamInterface;
use Wenprise\Wechatpay\Psr\Http\Message\UriInterface;

interface ClientInterface
{
    /**
     * Creates a new PSR-7 request.
     *
     * @param string                               $method
     * @param string|UriInterface                  $uri
     * @param array                                $headers
     * @param resource|string|StreamInterface|null $body
     * @param string                               $protocolVersion
     *
     * @throws RequestException when the HTTP client is passed a request that is invalid and cannot be sent.
     * @throws NetworkException if there is an error with the network or the remote server cannot be reached.
     *
     * @return ResponseInterface
     */
    public function request(
        $method,
        $uri,
        array $headers = [],
        $body = null,
        $protocolVersion = '1.1'
    );
}
