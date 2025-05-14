<?php

namespace Tourze\Workerman\RFC3489\Exception;

use Exception;

/**
 * STUN异常基类
 * 
 * 所有STUN相关异常的基类，提供基本异常功能
 */
class StunException extends Exception
{
    /**
     * 创建一个新的STUN异常
     * 
     * @param string $message 异常消息
     * @param int $code 异常代码
     * @param \Throwable|null $previous 前一个异常
     */
    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
