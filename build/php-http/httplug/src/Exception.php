<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 09-September-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Http\Client;

use Wenprise\Wechatpay\Psr\Http\Client\ClientExceptionInterface as PsrClientException;

/**
 * Every HTTP Client related Exception must implement this interface.
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
interface Exception extends PsrClientException
{
}
