<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 09-September-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Http\Message\Authentication;

use Wenprise\Wechatpay\Http\Message\Authentication;
use Wenprise\Wechatpay\Psr\Http\Message\RequestInterface;

class Header implements Authentication
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string|string[]
     */
    private $value;

    /**
     * @param string|string[] $value
     */
    public function __construct(string $name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    public function authenticate(RequestInterface $request)
    {
        return $request->withHeader($this->name, $this->value);
    }
}
