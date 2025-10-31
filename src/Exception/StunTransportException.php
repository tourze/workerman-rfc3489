<?php

namespace Tourze\Workerman\RFC3489\Exception;

/**
 * STUN传输异常
 *
 * 当STUN传输过程中出现错误时抛出
 */
class StunTransportException extends StunException
{
    /**
     * @param string          $message  异常消息
     * @param int             $code     异常代码
     * @param \Throwable|null $previous 前一个异常
     */
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
