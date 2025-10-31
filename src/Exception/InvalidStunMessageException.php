<?php

namespace Tourze\Workerman\RFC3489\Exception;

/**
 * 无效的STUN消息异常
 *
 * 当收到或构造无效的STUN消息时抛出
 */
class InvalidStunMessageException extends StunException
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
