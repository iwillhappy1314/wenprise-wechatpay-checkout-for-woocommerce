<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 09-September-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Money\Parser;

use Wenprise\Wechatpay\Money\Currencies;
use Wenprise\Wechatpay\Money\Currency;
use Wenprise\Wechatpay\Money\Exception\ParserException;
use Wenprise\Wechatpay\Money\Money;
use Wenprise\Wechatpay\Money\MoneyParser;
use Wenprise\Wechatpay\Money\Number;

/**
 * Parses a string into a Money object using intl extension.
 *
 * @author Frederik Bosch <f.bosch@genkgo.nl>
 */
final class IntlMoneyParser implements MoneyParser
{
    /**
     * @var \NumberFormatter
     */
    private $formatter;

    /**
     * @var Currencies
     */
    private $currencies;

    public function __construct(\NumberFormatter $formatter, Currencies $currencies)
    {
        $this->formatter = $formatter;
        $this->currencies = $currencies;
    }

    /**
     * {@inheritdoc}
     */
    public function parse($money, $forceCurrency = null)
    {
        if (!is_string($money)) {
            throw new ParserException('Formatted raw money should be string, e.g. $1.00');
        }

        $currency = null;
        $decimal = $this->formatter->parseCurrency($money, $currency);

        if (false === $decimal) {
            throw new ParserException('Cannot parse '.$money.' to Money. '.$this->formatter->getErrorMessage());
        }

        if (null !== $forceCurrency) {
            $currency = $forceCurrency;
        } else {
            $currency = new Currency($currency);
        }

        /*
         * This conversion is only required whilst currency can be either a string or a
         * Currency object.
         */
        if (!$currency instanceof Currency) {
            @trigger_error('Passing a currency as string is deprecated since 3.1 and will be removed in 4.0. Please pass a '.Currency::class.' instance instead.', E_USER_DEPRECATED);
            $currency = new Currency($currency);
        }

        $decimal = (string) $decimal;
        $subunit = $this->currencies->subunitFor($currency);
        $decimalPosition = strpos($decimal, '.');

        if (false !== $decimalPosition) {
            $decimalLength = strlen($decimal);
            $fractionDigits = $decimalLength - $decimalPosition - 1;
            $decimal = str_replace('.', '', $decimal);
            $decimal = Number::roundMoneyValue($decimal, $subunit, $fractionDigits);

            if ($fractionDigits > $subunit) {
                $decimal = substr($decimal, 0, $decimalPosition + $subunit);
            } elseif ($fractionDigits < $subunit) {
                $decimal .= str_pad('', $subunit - $fractionDigits, '0');
            }
        } else {
            $decimal .= str_pad('', $subunit, '0');
        }

        if ('-' === $decimal[0]) {
            $decimal = '-'.ltrim(substr($decimal, 1), '0');
        } else {
            $decimal = ltrim($decimal, '0');
        }

        if ('' === $decimal) {
            $decimal = '0';
        }

        return new Money($decimal, $currency);
    }
}
