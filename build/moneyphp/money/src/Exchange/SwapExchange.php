<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 09-September-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Money\Exchange;

use Exchanger\Exception\Exception as ExchangerException;
use Wenprise\Wechatpay\Money\Currency;
use Wenprise\Wechatpay\Money\CurrencyPair;
use Wenprise\Wechatpay\Money\Exception\UnresolvableCurrencyPairException;
use Wenprise\Wechatpay\Money\Exchange;
use Swap\Swap;

/**
 * Provides a way to get exchange rate from a third-party source and return a currency pair.
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
final class SwapExchange implements Exchange
{
    /**
     * @var Swap
     */
    private $swap;

    public function __construct(Swap $swap)
    {
        $this->swap = $swap;
    }

    /**
     * {@inheritdoc}
     */
    public function quote(Currency $baseCurrency, Currency $counterCurrency)
    {
        try {
            $rate = $this->swap->latest($baseCurrency->getCode().'/'.$counterCurrency->getCode());
        } catch (ExchangerException $e) {
            throw UnresolvableCurrencyPairException::createFromCurrencies($baseCurrency, $counterCurrency);
        }

        return new CurrencyPair($baseCurrency, $counterCurrency, $rate->getValue());
    }
}
