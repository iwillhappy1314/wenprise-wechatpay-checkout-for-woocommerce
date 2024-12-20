<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 09-September-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Wenprise\Wechatpay\Http\Message\Encoding;

use Wenprise\Wechatpay\Clue\StreamFilter as Filter;
use Wenprise\Wechatpay\Psr\Http\Message\StreamInterface;

/**
 * Stream inflate (RFC 1951).
 *
 * @author Joel Wurtz <joel.wurtz@gmail.com>
 */
class InflateStream extends FilteredStream
{
    /**
     * @param int $level
     */
    public function __construct(StreamInterface $stream, $level = -1)
    {
        if (!extension_loaded('zlib')) {
            throw new \RuntimeException('The zlib extension must be enabled to use this stream');
        }

        parent::__construct($stream, ['window' => -15]);

        // @deprecated will be removed in 2.0
        $this->writeFilterCallback = Filter\fun($this->writeFilter(), ['window' => -15, 'level' => $level]);
    }

    protected function readFilter(): string
    {
        return 'zlib.inflate';
    }

    protected function writeFilter(): string
    {
        return 'zlib.deflate';
    }
}
