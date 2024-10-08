<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 09-September-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Money\Exchange;

use Wenprise\Wechatpay\Money\Calculator;
use Wenprise\Wechatpay\Money\Calculator\BcMathCalculator;
use Wenprise\Wechatpay\Money\Calculator\GmpCalculator;
use Wenprise\Wechatpay\Money\Calculator\PhpCalculator;
use Wenprise\Wechatpay\Money\Currencies;
use Wenprise\Wechatpay\Money\Currency;
use Wenprise\Wechatpay\Money\CurrencyPair;
use Wenprise\Wechatpay\Money\Exception\UnresolvableCurrencyPairException;
use Wenprise\Wechatpay\Money\Exchange;

/**
 * Provides a way to get an exchange rate through a minimal set of intermediate conversions.
 *
 * @author Michael Cordingley <Michael.Cordingley@gmail.com>
 */
final class IndirectExchange implements Exchange
{
    /**
     * @var Calculator
     */
    private static $calculator;

    /**
     * @var array
     */
    private static $calculators = [
        BcMathCalculator::class,
        GmpCalculator::class,
        PhpCalculator::class,
    ];

    /**
     * @var Currencies
     */
    private $currencies;

    /**
     * @var Exchange
     */
    private $exchange;

    public function __construct(Exchange $exchange, Currencies $currencies)
    {
        $this->exchange = $exchange;
        $this->currencies = $currencies;
    }

    /**
     * @param string $calculator
     */
    public static function registerCalculator($calculator)
    {
        if (is_a($calculator, Calculator::class, true) === false) {
            throw new \InvalidArgumentException('Calculator must implement '.Calculator::class);
        }

        array_unshift(self::$calculators, $calculator);
    }

    /**
     * {@inheritdoc}
     */
    public function quote(Currency $baseCurrency, Currency $counterCurrency)
    {
        try {
            return $this->exchange->quote($baseCurrency, $counterCurrency);
        } catch (UnresolvableCurrencyPairException $exception) {
            $rate = array_reduce($this->getConversions($baseCurrency, $counterCurrency), function ($carry, CurrencyPair $pair) {
                return static::getCalculator()->multiply($carry, $pair->getConversionRatio());
            }, '1.0');

            return new CurrencyPair($baseCurrency, $counterCurrency, $rate);
        }
    }

    /**
     * @return CurrencyPair[]
     *
     * @throws UnresolvableCurrencyPairException
     */
    private function getConversions(Currency $baseCurrency, Currency $counterCurrency)
    {
        $startNode = $this->initializeNode($baseCurrency);
        $startNode->discovered = true;

        $nodes = [$baseCurrency->getCode() => $startNode];

        $frontier = new \SplQueue();
        $frontier->enqueue($startNode);

        while ($frontier->count()) {
            /** @var \stdClass $currentNode */
            $currentNode = $frontier->dequeue();

            /** @var Currency $currentCurrency */
            $currentCurrency = $currentNode->currency;

            if ($currentCurrency->equals($counterCurrency)) {
                return $this->reconstructConversionChain($nodes, $currentNode);
            }

            /** @var Currency $candidateCurrency */
            foreach ($this->currencies as $candidateCurrency) {
                if (!isset($nodes[$candidateCurrency->getCode()])) {
                    $nodes[$candidateCurrency->getCode()] = $this->initializeNode($candidateCurrency);
                }

                /** @var \stdClass $node */
                $node = $nodes[$candidateCurrency->getCode()];

                if (!$node->discovered) {
                    try {
                        // Check if the candidate is a neighbor. This will throw an exception if it isn't.
                        $this->exchange->quote($currentCurrency, $candidateCurrency);

                        $node->discovered = true;
                        $node->parent = $currentNode;

                        $frontier->enqueue($node);
                    } catch (UnresolvableCurrencyPairException $exception) {
                        // Not a neighbor. Move on.
                    }
                }
            }
        }

        throw UnresolvableCurrencyPairException::createFromCurrencies($baseCurrency, $counterCurrency);
    }

    /**
     * @return \stdClass
     */
    private function initializeNode(Currency $currency)
    {
        $node = new \stdClass();

        $node->currency = $currency;
        $node->discovered = false;
        $node->parent = null;

        return $node;
    }

    /**
     * @return CurrencyPair[]
     */
    private function reconstructConversionChain(array $currencies, \stdClass $goalNode)
    {
        $current = $goalNode;
        $conversions = [];

        while ($current->parent) {
            $previous = $currencies[$current->parent->currency->getCode()];
            $conversions[] = $this->exchange->quote($previous->currency, $current->currency);
            $current = $previous;
        }

        return array_reverse($conversions);
    }

    /**
     * @return Calculator
     */
    private function getCalculator()
    {
        if (null === self::$calculator) {
            self::$calculator = self::initializeCalculator();
        }

        return self::$calculator;
    }

    /**
     * @return Calculator
     *
     * @throws \RuntimeException If cannot find calculator for money calculations
     */
    private static function initializeCalculator()
    {
        $calculators = self::$calculators;

        foreach ($calculators as $calculator) {
            /** @var Calculator $calculator */
            if ($calculator::supported()) {
                return new $calculator();
            }
        }

        throw new \RuntimeException('Cannot find calculator for money calculations');
    }
}
