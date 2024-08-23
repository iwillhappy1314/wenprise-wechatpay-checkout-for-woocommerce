<?php
/**
 * Message Interface
 *
 * @license MIT
 * Modified by __root__ on 23-August-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Omnipay\Common\Message;

/**
 * Message Interface
 *
 * This interface class defines the standard functions that any Omnipay message
 * interface needs to be able to provide.
 */
interface MessageInterface
{
    /**
     * Get the raw data array for this message. The format of this varies from gateway to
     * gateway, but will usually be either an associative array, or a SimpleXMLElement.
     *
     * @return mixed
     */
    public function getData();
}