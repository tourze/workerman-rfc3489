<?php

namespace Tourze\Workerman\RFC3489\Exception;

/**
 * STUN IP地址异常
 *
 * 当处理STUN IP地址时出现错误时抛出
 */
class StunIpException extends StunException
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
