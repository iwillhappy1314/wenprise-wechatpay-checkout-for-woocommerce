<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 23-August-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Money\Currencies;

use Wenprise\Wechatpay\Money\Currencies;
use Wenprise\Wechatpay\Money\Currency;
use Wenprise\Wechatpay\Money\Exception\UnknownCurrencyException;

/**
 * Aggregates several currency repositories.
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
final class AggregateCurrencies implements Currencies
{
    /**
     * @var Currencies[]
     */
    private $currencies;

    /**
     * @param Currencies[] $currencies
     */
    public function __construct(array $currencies)
    {
        foreach ($currencies as $c) {
            if (false === $c instanceof Currencies) {
                throw new \InvalidArgumentException('All currency repositories must implement '.Currencies::class);
            }
        }

        $this->currencies = $currencies;
    }

    /**
     * {@inheritdoc}
     */
    public function contains(Currency $currency)
    {
        foreach ($this->currencies as $currencies) {
            if ($currencies->contains($currency)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function subunitFor(Currency $currency)
    {
        foreach ($this->currencies as $currencies) {
            if ($currencies->contains($currency)) {
                return $currencies->subunitFor($currency);
            }
        }

        throw new UnknownCurrencyException('Cannot find currency '.$currency->getCode());
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        $iterator = new \AppendIterator();

        foreach ($this->currencies as $currencies) {
            $iterator->append($currencies->getIterator());
        }

        return $iterator;
    }
}