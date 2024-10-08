<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 09-September-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Money\Formatter;

use Wenprise\Wechatpay\Money\Currencies;
use Wenprise\Wechatpay\Money\Currencies\BitcoinCurrencies;
use Wenprise\Wechatpay\Money\Exception\FormatterException;
use Wenprise\Wechatpay\Money\Money;
use Wenprise\Wechatpay\Money\MoneyFormatter;
use Wenprise\Wechatpay\Money\Number;

/**
 * Formats Money to Bitcoin currency.
 *
 * @author Frederik Bosch <f.bosch@genkgo.nl>
 */
final class BitcoinMoneyFormatter implements MoneyFormatter
{
    /**
     * @var int
     */
    private $fractionDigits;

    /**
     * @var Currencies
     */
    private $currencies;

    /**
     * @param int $fractionDigits
     */
    public function __construct($fractionDigits, Currencies $currencies)
    {
        $this->fractionDigits = $fractionDigits;
        $this->currencies = $currencies;
    }

    /**
     * {@inheritdoc}
     */
    public function format(Money $money)
    {
        if (BitcoinCurrencies::CODE !== $money->getCurrency()->getCode()) {
            throw new FormatterException('Bitcoin Formatter can only format Bitcoin currency');
        }

        $valueBase = $money->getAmount();
        $negative = false;

        if ('-' === $valueBase[0]) {
            $negative = true;
            $valueBase = substr($valueBase, 1);
        }

        $subunit = $this->currencies->subunitFor($money->getCurrency());
        $valueBase = Number::roundMoneyValue($valueBase, $this->fractionDigits, $subunit);
        $valueLength = strlen($valueBase);

        if ($valueLength > $subunit) {
            $formatted = substr($valueBase, 0, $valueLength - $subunit);

            if ($subunit) {
                $formatted .= '.';
                $formatted .= substr($valueBase, $valueLength - $subunit);
            }
        } else {
            $formatted = '0.'.str_pad('', $subunit - $valueLength, '0').$valueBase;
        }

        if ($this->fractionDigits === 0) {
            $formatted = substr($formatted, 0, strpos($formatted, '.'));
        } elseif ($this->fractionDigits > $subunit) {
            $formatted .= str_pad('', $this->fractionDigits - $subunit, '0');
        } elseif ($this->fractionDigits < $subunit) {
            $lastDigit = strpos($formatted, '.') + $this->fractionDigits + 1;
            $formatted = substr($formatted, 0, $lastDigit);
        }

        $formatted = BitcoinCurrencies::SYMBOL.$formatted;

        if (true === $negative) {
            $formatted = '-'.$formatted;
        }

        return $formatted;
    }
}
