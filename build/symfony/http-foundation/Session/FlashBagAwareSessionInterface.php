<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Modified by __root__ on 11-May-2026 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Symfony\Component\HttpFoundation\Session;

use Wenprise\Wechatpay\Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

/**
 * Interface for session with a flashbag.
 */
interface FlashBagAwareSessionInterface extends SessionInterface
{
    public function getFlashBag(): FlashBagInterface;
}
