<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 09-September-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Http\Message\StreamFactory;

use Wenprise\Wechatpay\GuzzleHttp\Psr7\Utils;
use Wenprise\Wechatpay\Http\Message\StreamFactory;

if (!interface_exists(StreamFactory::class)) {
    throw new \LogicException('You cannot use "Wenprise\Wechatpay\Http\Message\MessageFactory\GuzzleStreamFactory" as the "php-http/message-factory" package is not installed. Try running "composer require php-http/message-factory". Note that this package is deprecated, use "psr/http-factory" instead');
}

/**
 * Creates Guzzle streams.
 *
 * @author Михаил Красильников <m.krasilnikov@yandex.ru>
 *
 * @deprecated This will be removed in php-http/message2.0. Consider using the official Guzzle PSR-17 factory
 */
final class GuzzleStreamFactory implements StreamFactory
{
    public function createStream($body = null)
    {
        if (class_exists(Utils::class)) {
            return Utils::streamFor($body);
        }

        // legacy support for guzzle/psr7 1.*
        return \Wenprise\Wechatpay\GuzzleHttp\Psr7\stream_for($body);
    }
}
