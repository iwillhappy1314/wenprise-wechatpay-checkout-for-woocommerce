<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 09-September-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Money;

use Wenprise\Wechatpay\Money\Exception\UnresolvableCurrencyPairException;

/**
 * Provides a way to get exchange rate from a third-party source and return a currency pair.
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
interface Exchange
{
    /**
     * Returns a currency pair for the passed currencies with the rate coming from a third-party source.
     *
     * @return CurrencyPair
     *
     * @throws UnresolvableCurrencyPairException When there is no currency pair (rate) available for the given currencies
     */
    public function quote(Currency $baseCurrency, Currency $counterCurrency);
}
