<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 09-September-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Http\Message;

use Wenprise\Wechatpay\Psr\Http\Message\UriInterface;

/**
 * Factory for PSR-7 URI.
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 *
 * @deprecated since version 1.1, use Wenprise\Wechatpay\Psr\Http\Message\UriFactoryInterface instead.
 */
interface UriFactory
{
    /**
     * Creates an PSR-7 URI.
     *
     * @param string|UriInterface $uri
     *
     * @return UriInterface
     *
     * @throws \InvalidArgumentException if the $uri argument can not be converted into a valid URI
     */
    public function createUri($uri);
}
