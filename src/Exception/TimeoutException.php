<?php

namespace Tourze\Workerman\RFC3489\Exception;

/**
 * 超时异常
 *
 * 表示在STUN操作中发生的超时错误
 */
class TimeoutException extends StunException
{
    /**
     * 创建一个新的超时异常
     *
     * @param string          $message  异常消息
     * @param int             $timeout  超时时间（毫秒）
     * @param int             $code     异常代码
     * @param \Throwable|null $previous 上一个异常
     */
    public function __construct(string $message = '操作超时', private readonly int $timeout = 0, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * 获取超时时间
     *
     * @return int 超时时间（毫秒）
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * 创建一个接收超时异常
     *
     * @param int $timeout 超时时间（毫秒）
     *
     * @return self 异常实例
     */
    public static function receiveTimeout(int $timeout): self
    {
        return new self("接收消息超时（{$timeout}毫秒）", $timeout, 2001);
    }

    /**
     * 创建一个发送超时异常
     *
     * @param int $timeout 超时时间（毫秒）
     *
     * @return self 异常实例
     */
    public static function sendTimeout(int $timeout): self
    {
        return new self("发送消息超时（{$timeout}毫秒）", $timeout, 2002);
    }

    /**
     * 创建一个事务超时异常
     *
     * @param string $transactionId 事务ID
     * @param int    $timeout       超时时间（毫秒）
     *
     * @return self 异常实例
     */
    public static function transactionTimeout(string $transactionId, int $timeout): self
    {
        $hexId = bin2hex($transactionId);

        return new self("事务 {$hexId} 超时（{$timeout}毫秒）", $timeout, 2003);
    }
}
