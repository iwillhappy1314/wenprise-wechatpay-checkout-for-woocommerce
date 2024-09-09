<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 09-September-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Money\Exchange;

use Exchanger\Contract\ExchangeRateProvider;
use Exchanger\CurrencyPair as ExchangerCurrencyPair;
use Exchanger\Exception\Exception as ExchangerException;
use Exchanger\ExchangeRateQuery;
use Wenprise\Wechatpay\Money\Currency;
use Wenprise\Wechatpay\Money\CurrencyPair;
use Wenprise\Wechatpay\Money\Exception\UnresolvableCurrencyPairException;
use Wenprise\Wechatpay\Money\Exchange;

/**
 * Provides a way to get exchange rate from a third-party source and return a currency pair.
 *
 * @author Maksim (Ellrion) Platonov <ellrion11@gmail.com>
 */
final class ExchangerExchange implements Exchange
{
    /**
     * @var ExchangeRateProvider
     */
    private $exchanger;

    public function __construct(ExchangeRateProvider $exchanger)
    {
        $this->exchanger = $exchanger;
    }

    /**
     * {@inheritdoc}
     */
    public function quote(Currency $baseCurrency, Currency $counterCurrency)
    {
        try {
            $query = new ExchangeRateQuery(
                new ExchangerCurrencyPair($baseCurrency->getCode(), $counterCurrency->getCode())
            );
            $rate = $this->exchanger->getExchangeRate($query);
        } catch (ExchangerException $e) {
            throw UnresolvableCurrencyPairException::createFromCurrencies($baseCurrency, $counterCurrency);
        }

        return new CurrencyPair($baseCurrency, $counterCurrency, $rate->getValue());
    }
}
