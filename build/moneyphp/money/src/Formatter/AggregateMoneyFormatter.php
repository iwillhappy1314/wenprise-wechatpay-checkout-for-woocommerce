<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 09-September-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Money\Formatter;

use Wenprise\Wechatpay\Money\Exception\FormatterException;
use Wenprise\Wechatpay\Money\Money;
use Wenprise\Wechatpay\Money\MoneyFormatter;

/**
 * Formats a Money object using other Money formatters.
 *
 * @author Frederik Bosch <f.bosch@genkgo.nl>
 */
final class AggregateMoneyFormatter implements MoneyFormatter
{
    /**
     * @var MoneyFormatter[]
     */
    private $formatters = [];

    /**
     * @param MoneyFormatter[] $formatters
     */
    public function __construct(array $formatters)
    {
        if (empty($formatters)) {
            throw new \InvalidArgumentException(sprintf('Initialize an empty %s is not possible', self::class));
        }

        foreach ($formatters as $currencyCode => $formatter) {
            if (false === $formatter instanceof MoneyFormatter) {
                throw new \InvalidArgumentException('All formatters must implement '.MoneyFormatter::class);
            }

            $this->formatters[$currencyCode] = $formatter;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function format(Money $money)
    {
        $currencyCode = $money->getCurrency()->getCode();

        if (isset($this->formatters[$currencyCode])) {
            return $this->formatters[$currencyCode]->format($money);
        }

        if (isset($this->formatters['*'])) {
            return $this->formatters['*']->format($money);
        }

        throw new FormatterException('No formatter found for currency '.$currencyCode);
    }
}
