<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 09-September-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Money\Exception;

use Wenprise\Wechatpay\Money\Currency;
use Wenprise\Wechatpay\Money\Exception;

/**
 * Thrown when there is no currency pair (rate) available for the given currencies.
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
final class UnresolvableCurrencyPairException extends \InvalidArgumentException implements Exception
{
    /**
     * Creates an exception from Currency objects.
     *
     * @return UnresolvableCurrencyPairException
     */
    public static function createFromCurrencies(Currency $baseCurrency, Currency $counterCurrency)
    {
        $message = sprintf(
            'Cannot resolve a currency pair for currencies: %s/%s',
            $baseCurrency->getCode(),
            $counterCurrency->getCode()
        );

        return new self($message);
    }
}
