<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 09-September-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Money\Currencies;

use Wenprise\Wechatpay\Money\Currencies;
use Wenprise\Wechatpay\Money\Currency;
use Wenprise\Wechatpay\Money\Exception\UnknownCurrencyException;

/**
 * @author Frederik Bosch <f.bosch@genkgo.nl>
 */
final class BitcoinCurrencies implements Currencies
{
    const CODE = 'XBT';

    const SYMBOL = "\xC9\x83";

    /**
     * {@inheritdoc}
     */
    public function contains(Currency $currency)
    {
        return self::CODE === $currency->getCode();
    }

    /**
     * {@inheritdoc}
     */
    public function subunitFor(Currency $currency)
    {
        if ($currency->getCode() !== self::CODE) {
            throw new UnknownCurrencyException($currency->getCode().' is not bitcoin and is not supported by this currency repository');
        }

        return 8;
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new \ArrayIterator([new Currency(self::CODE)]);
    }
}
