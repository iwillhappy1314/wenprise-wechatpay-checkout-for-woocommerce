<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 09-September-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Money\Parser;

use Wenprise\Wechatpay\Money\Currencies\BitcoinCurrencies;
use Wenprise\Wechatpay\Money\Currency;
use Wenprise\Wechatpay\Money\Exception\ParserException;
use Wenprise\Wechatpay\Money\Money;
use Wenprise\Wechatpay\Money\MoneyParser;

/**
 * Parses Bitcoin currency to Money.
 *
 * @author Frederik Bosch <f.bosch@genkgo.nl>
 */
final class BitcoinMoneyParser implements MoneyParser
{
    /**
     * @var int
     */
    private $fractionDigits;

    /**
     * @param int $fractionDigits
     */
    public function __construct($fractionDigits)
    {
        $this->fractionDigits = $fractionDigits;
    }

    /**
     * {@inheritdoc}
     */
    public function parse($money, $forceCurrency = null)
    {
        if (is_string($money) === false) {
            throw new ParserException('Formatted raw money should be string, e.g. $1.00');
        }

        if (strpos($money, BitcoinCurrencies::SYMBOL) === false) {
            throw new ParserException('Value cannot be parsed as Bitcoin');
        }

        if ($forceCurrency === null) {
            $forceCurrency = new Currency(BitcoinCurrencies::CODE);
        }

        /*
         * This conversion is only required whilst currency can be either a string or a
         * Currency object.
         */
        $currency = $forceCurrency;
        if (!$currency instanceof Currency) {
            @trigger_error('Passing a currency as string is deprecated since 3.1 and will be removed in 4.0. Please pass a '.Currency::class.' instance instead.', E_USER_DEPRECATED);
            $currency = new Currency($currency);
        }

        $decimal = str_replace(BitcoinCurrencies::SYMBOL, '', $money);
        $decimalSeparator = strpos($decimal, '.');

        if (false !== $decimalSeparator) {
            $decimal = rtrim($decimal, '0');
            $lengthDecimal = strlen($decimal);
            $decimal = str_replace('.', '', $decimal);
            $decimal .= str_pad('', ($lengthDecimal - $decimalSeparator - $this->fractionDigits - 1) * -1, '0');
        } else {
            $decimal .= str_pad('', $this->fractionDigits, '0');
        }

        if (substr($decimal, 0, 1) === '-') {
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
