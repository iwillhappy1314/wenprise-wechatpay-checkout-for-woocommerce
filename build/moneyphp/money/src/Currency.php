<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 09-September-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Money;

/**
 * Currency Value Object.
 *
 * Holds Currency specific data.
 *
 * @author Mathias Verraes
 *
 * @psalm-immutable
 */
final class Currency implements \JsonSerializable
{
    /**
     * Currency code.
     *
     * @var string
     */
    private $code;

    /**
     * @param string $code
     */
    public function __construct($code)
    {
        if (!is_string($code)) {
            throw new \InvalidArgumentException('Currency code should be string');
        }

        if ($code === '') {
            throw new \InvalidArgumentException('Currency code should not be empty string');
        }

        $this->code = $code;
    }

    /**
     * Returns the currency code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Checks whether this currency is the same as an other.
     *
     * @return bool
     */
    public function equals(Currency $other)
    {
        return $this->code === $other->code;
    }

    /**
     * Checks whether this currency is available in the passed context.
     *
     * @return bool
     */
    public function isAvailableWithin(Currencies $currencies)
    {
        return $currencies->contains($this);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->code;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->code;
    }
}
