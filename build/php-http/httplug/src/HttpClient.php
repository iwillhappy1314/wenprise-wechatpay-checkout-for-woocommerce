<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 09-September-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Http\Client;

use Wenprise\Wechatpay\Psr\Http\Client\ClientInterface;

/**
 * {@inheritdoc}
 *
 * Provide the Httplug HttpClient interface for BC.
 * You should typehint Psr\Http\Client\ClientInterface in new code
 *
 * @deprecated since version 2.4, use Wenprise\Wechatpay\Psr\Http\Client\ClientInterface instead; see https://www.php-fig.org/psr/psr-18/
 */
interface HttpClient extends ClientInterface
{
}
