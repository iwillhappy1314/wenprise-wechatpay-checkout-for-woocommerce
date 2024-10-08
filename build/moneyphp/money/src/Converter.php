<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 09-September-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Money;

/**
 * Provides a way to convert Money to Money in another Currency using an exchange rate.
 *
 * @author Frederik Bosch <f.bosch@genkgo.nl>
 */
final class Converter
{
    /**
     * @var Currencies
     */
    private $currencies;

    /**
     * @var Exchange
     */
    private $exchange;

    public function __construct(Currencies $currencies, Exchange $exchange)
    {
        $this->currencies = $currencies;
        $this->exchange = $exchange;
    }

    /**
     * @param int $roundingMode
     *
     * @return Money
     */
    public function convert(Money $money, Currency $counterCurrency, $roundingMode = Money::ROUND_HALF_UP)
    {
        $baseCurrency = $money->getCurrency();
        $ratio = $this->exchange->quote($baseCurrency, $counterCurrency)->getConversionRatio();

        $baseCurrencySubunit = $this->currencies->subunitFor($baseCurrency);
        $counterCurrencySubunit = $this->currencies->subunitFor($counterCurrency);
        $subunitDifference = $baseCurrencySubunit - $counterCurrencySubunit;

        $ratio = (string) Number::fromFloat($ratio)->base10($subunitDifference);

        $counterValue = $money->multiply($ratio, $roundingMode);

        return new Money($counterValue->getAmount(), $counterCurrency);
    }
}
