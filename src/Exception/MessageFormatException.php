<?php

namespace Tourze\Workerman\RFC3489\Exception;

/**
 * STUN消息格式异常
 * 
 * 当消息格式不正确或解析失败时抛出此异常
 */
class MessageFormatException extends StunException
{
    /**
     * 创建一个新的消息格式异常
     * 
     * @param string $message 异常消息
     * @param int $code 异常代码
     * @param \Throwable|null $previous 前一个异常
     */
    public function __construct(string $message = "STUN消息格式错误", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
