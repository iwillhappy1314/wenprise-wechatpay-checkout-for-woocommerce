<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 09-September-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Http\Message\Formatter;

use Wenprise\Wechatpay\Http\Message\Formatter;
use Wenprise\Wechatpay\Psr\Http\Message\RequestInterface;
use Wenprise\Wechatpay\Psr\Http\Message\ResponseInterface;

/**
 * Normalize a request or a response into a string or an array.
 *
 * @author Joel Wurtz <joel.wurtz@gmail.com>
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
class SimpleFormatter implements Formatter
{
    public function formatRequest(RequestInterface $request)
    {
        return sprintf(
            '%s %s %s',
            $request->getMethod(),
            $request->getUri()->__toString(),
            $request->getProtocolVersion()
        );
    }

    public function formatResponse(ResponseInterface $response)
    {
        return sprintf(
            '%s %s %s',
            $response->getStatusCode(),
            $response->getReasonPhrase(),
            $response->getProtocolVersion()
        );
    }

    /**
     * Formats a response in context of its request.
     *
     * @return string
     */
    public function formatResponseForRequest(ResponseInterface $response, RequestInterface $request)
    {
        return $this->formatResponse($response);
    }
}
