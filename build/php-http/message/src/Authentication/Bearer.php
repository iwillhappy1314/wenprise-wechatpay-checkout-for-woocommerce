<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 09-September-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Http\Message\Authentication;

use Wenprise\Wechatpay\Http\Message\Authentication;
use Wenprise\Wechatpay\Psr\Http\Message\RequestInterface;

/**
 * Authenticate a PSR-7 Request using a token.
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
final class Bearer implements Authentication
{
    /**
     * @var string
     */
    private $token;

    /**
     * @param string $token
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    public function authenticate(RequestInterface $request)
    {
        $header = sprintf('Bearer %s', $this->token);

        return $request->withHeader('Authorization', $header);
    }
}
