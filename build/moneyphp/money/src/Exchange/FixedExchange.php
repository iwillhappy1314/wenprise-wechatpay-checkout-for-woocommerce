<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 23-August-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Money\Exchange;

use Wenprise\Wechatpay\Money\Currency;
use Wenprise\Wechatpay\Money\CurrencyPair;
use Wenprise\Wechatpay\Money\Exception\UnresolvableCurrencyPairException;
use Wenprise\Wechatpay\Money\Exchange;

/**
 * Provides a way to get exchange rate from a static list (array).
 *
 * @author Frederik Bosch <f.bosch@genkgo.nl>
 */
final class FixedExchange implements Exchange
{
    /**
     * @var array
     */
    private $list;

    public function __construct(array $list)
    {
        $this->list = $list;
    }

    /**
     * {@inheritdoc}
     */
    public function quote(Currency $baseCurrency, Currency $counterCurrency)
    {
        if (isset($this->list[$baseCurrency->getCode()][$counterCurrency->getCode()])) {
            return new CurrencyPair(
                $baseCurrency,
                $counterCurrency,
                $this->list[$baseCurrency->getCode()][$counterCurrency->getCode()]
            );
        }

        throw UnresolvableCurrencyPairException::createFromCurrencies($baseCurrency, $counterCurrency);
    }
}
