<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 09-September-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Money\Calculator;

use Wenprise\Wechatpay\Money\Calculator;
use Wenprise\Wechatpay\Money\Money;
use Wenprise\Wechatpay\Money\Number;

/**
 * @author Frederik Bosch <f.bosch@genkgo.nl>
 */
final class BcMathCalculator implements Calculator
{
    /**
     * @var string
     */
    private $scale;

    /**
     * @param int $scale
     */
    public function __construct($scale = 14)
    {
        $this->scale = $scale;
    }

    /**
     * {@inheritdoc}
     */
    public static function supported()
    {
        return extension_loaded('bcmath');
    }

    /**
     * {@inheritdoc}
     */
    public function compare($a, $b)
    {
        return bccomp($a, $b, $this->scale);
    }

    /**
     * {@inheritdoc}
     */
    public function add($amount, $addend)
    {
        return (string) Number::fromString(bcadd($amount, $addend, $this->scale));
    }

    /**
     * {@inheritdoc}
     *
     * @param $amount
     * @param $subtrahend
     *
     * @return string
     */
    public function subtract($amount, $subtrahend)
    {
        return (string) Number::fromString(bcsub($amount, $subtrahend, $this->scale));
    }

    /**
     * {@inheritdoc}
     */
    public function multiply($amount, $multiplier)
    {
        $multiplier = Number::fromNumber($multiplier);

        return bcmul($amount, (string) $multiplier, $this->scale);
    }

    /**
     * {@inheritdoc}
     */
    public function divide($amount, $divisor)
    {
        $divisor = Number::fromNumber($divisor);

        return bcdiv($amount, (string) $divisor, $this->scale);
    }

    /**
     * {@inheritdoc}
     */
    public function ceil($number)
    {
        $number = Number::fromNumber($number);

        if ($number->isInteger()) {
            return (string) $number;
        }

        if ($number->isNegative()) {
            return bcadd((string) $number, '0', 0);
        }

        return bcadd((string) $number, '1', 0);
    }

    /**
     * {@inheritdoc}
     */
    public function floor($number)
    {
        $number = Number::fromNumber($number);

        if ($number->isInteger()) {
            return (string) $number;
        }

        if ($number->isNegative()) {
            return bcadd((string) $number, '-1', 0);
        }

        return bcadd($number, '0', 0);
    }

    /**
     * {@inheritdoc}
     */
    public function absolute($number)
    {
        return ltrim($number, '-');
    }

    /**
     * {@inheritdoc}
     */
    public function round($number, $roundingMode)
    {
        $number = Number::fromNumber($number);

        if ($number->isInteger()) {
            return (string) $number;
        }

        if ($number->isHalf() === false) {
            return $this->roundDigit($number);
        }

        if (Money::ROUND_HALF_UP === $roundingMode) {
            return bcadd(
                (string) $number,
                $number->getIntegerRoundingMultiplier(),
                0
            );
        }

        if (Money::ROUND_HALF_DOWN === $roundingMode) {
            return bcadd((string) $number, '0', 0);
        }

        if (Money::ROUND_HALF_EVEN === $roundingMode) {
            if ($number->isCurrentEven()) {
                return bcadd((string) $number, '0', 0);
            }

            return bcadd(
                (string) $number,
                $number->getIntegerRoundingMultiplier(),
                0
            );
        }

        if (Money::ROUND_HALF_ODD === $roundingMode) {
            if ($number->isCurrentEven()) {
                return bcadd(
                    (string) $number,
                    $number->getIntegerRoundingMultiplier(),
                    0
                );
            }

            return bcadd((string) $number, '0', 0);
        }

        if (Money::ROUND_HALF_POSITIVE_INFINITY === $roundingMode) {
            if ($number->isNegative()) {
                return bcadd((string) $number, '0', 0);
            }

            return bcadd(
                (string) $number,
                $number->getIntegerRoundingMultiplier(),
                0
            );
        }

        if (Money::ROUND_HALF_NEGATIVE_INFINITY === $roundingMode) {
            if ($number->isNegative()) {
                return bcadd(
                    (string) $number,
                    $number->getIntegerRoundingMultiplier(),
                    0
                );
            }

            return bcadd(
                (string) $number,
                '0',
                0
            );
        }

        throw new \InvalidArgumentException('Unknown rounding mode');
    }

    /**
     * @return string
     */
    private function roundDigit(Number $number)
    {
        if ($number->isCloserToNext()) {
            return bcadd(
                (string) $number,
                $number->getIntegerRoundingMultiplier(),
                0
            );
        }

        return bcadd((string) $number, '0', 0);
    }

    /**
     * {@inheritdoc}
     */
    public function share($amount, $ratio, $total)
    {
        return $this->floor(bcdiv(bcmul($amount, $ratio, $this->scale), $total, $this->scale));
    }

    /**
     * {@inheritdoc}
     */
    public function mod($amount, $divisor)
    {
        return bcmod($amount, $divisor);
    }
}
