<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Modified by __root__ on 11-May-2026 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Symfony\Component\HttpFoundation\RequestMatcher;

use Wenprise\Wechatpay\Symfony\Component\HttpFoundation\Request;
use Wenprise\Wechatpay\Symfony\Component\HttpFoundation\RequestMatcherInterface;

/**
 * Checks the presence of HTTP headers in a Request.
 *
 * @author Alexandre Daubois <alex.daubois@gmail.com>
 */
class HeaderRequestMatcher implements RequestMatcherInterface
{
    /**
     * @var string[]
     */
    private array $headers;

    /**
     * @param string[]|string $headers A header or a list of headers
     *                                 Strings can contain a comma-delimited list of headers
     */
    public function __construct(array|string $headers)
    {
        $this->headers = array_reduce((array) $headers, static fn (array $headers, string $header) => array_merge($headers, preg_split('/\s*,\s*/', $header)), []);
    }

    public function matches(Request $request): bool
    {
        if (!$this->headers) {
            return true;
        }

        foreach ($this->headers as $header) {
            if (!$request->headers->has($header)) {
                return false;
            }
        }

        return true;
    }
}
