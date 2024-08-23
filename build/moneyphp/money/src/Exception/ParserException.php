<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 23-August-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Money\Exception;

use Wenprise\Wechatpay\Money\Exception;

/**
 * Thrown when a string cannot be parsed to a Money object.
 *
 * @author Frederik Bosch <f.bosch@genkgo.nl>
 */
final class ParserException extends \RuntimeException implements Exception
{
}
