<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 09-September-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Money\PHPUnit;

use Wenprise\Wechatpay\Money\Currencies\AggregateCurrencies;
use Wenprise\Wechatpay\Money\Currencies\BitcoinCurrencies;
use Wenprise\Wechatpay\Money\Currencies\ISOCurrencies;
use Wenprise\Wechatpay\Money\Formatter\IntlMoneyFormatter;
use Wenprise\Wechatpay\Money\Money;
use SebastianBergmann\Comparator\ComparisonFailure;

/**
 * The comparator is for comparing Money objects in PHPUnit tests.
 *
 * Add this to your bootstrap file:
 *
 * \SebastianBergmann\Comparator\Factory::getInstance()->register(new \Wenprise\Wechatpay\Money\PHPUnit\Comparator());
 */
final class Comparator extends \SebastianBergmann\Comparator\Comparator
{
    /**
     * @var IntlMoneyFormatter
     */
    private $formatter;

    public function __construct()
    {
        parent::__construct();

        $currencies = new AggregateCurrencies([
            new ISOCurrencies(),
            new BitcoinCurrencies(),
        ]);

        $numberFormatter = new \NumberFormatter('en_US', \NumberFormatter::CURRENCY);
        $this->formatter = new IntlMoneyFormatter($numberFormatter, $currencies);
    }

    public function accepts($expected, $actual)
    {
        return $expected instanceof Money && $actual instanceof Wenprise\Wechatpay\Money;
    }

    /**
     * @param Money $expected
     * @param Money $actual
     * @param float $delta
     * @param bool  $canonicalize
     * @param bool  $ignoreCase
     */
    public function assertEquals(
        $expected,
        $actual,
        $delta = 0.0,
        $canonicalize = false,
        $ignoreCase = false,
        array &$processed = []
    ) {
        if (!$expected->equals($actual)) {
            throw new ComparisonFailure($expected, $actual, $this->formatter->format($expected), $this->formatter->format($actual), false, 'Failed asserting that two Money objects are equal.');
        }
    }
}
