<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 09-September-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Money\Formatter;

use Wenprise\Wechatpay\Money\Currencies;
use Wenprise\Wechatpay\Money\Money;
use Wenprise\Wechatpay\Money\MoneyFormatter;

/**
 * Formats a Money object as a decimal string.
 *
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 */
final class DecimalMoneyFormatter implements MoneyFormatter
{
    /**
     * @var Currencies
     */
    private $currencies;

    public function __construct(Currencies $currencies)
    {
        $this->currencies = $currencies;
    }

    /**
     * {@inheritdoc}
     */
    public function format(Money $money)
    {
        $valueBase = $money->getAmount();
        $negative = false;

        if ($valueBase[0] === '-') {
            $negative = true;
            $valueBase = substr($valueBase, 1);
        }

        $subunit = $this->currencies->subunitFor($money->getCurrency());
        $valueLength = strlen($valueBase);

        if ($valueLength > $subunit) {
            $formatted = substr($valueBase, 0, $valueLength - $subunit);
            $decimalDigits = substr($valueBase, $valueLength - $subunit);

            if (strlen($decimalDigits) > 0) {
                $formatted .= '.'.$decimalDigits;
            }
        } else {
            $formatted = '0.'.str_pad('', $subunit - $valueLength, '0').$valueBase;
        }

        if ($negative === true) {
            $formatted = '-'.$formatted;
        }

        return $formatted;
    }
}
