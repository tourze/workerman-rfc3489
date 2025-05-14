<?php

namespace Tourze\Workerman\RFC3489\Exception;

/**
 * 协议逻辑异常
 *
 * 表示在STUN协议处理逻辑中发生的错误
 */
class ProtocolException extends StunException
{
    /**
     * 创建一个新的协议逻辑异常
     *
     * @param string $message 异常消息
     * @param int $code 异常代码
     * @param \Throwable|null $previous 上一个异常
     */
    public function __construct(string $message = "协议逻辑错误", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * 创建一个无效状态异常
     *
     * @param string $state 当前状态
     * @param string $expected 期望的状态
     * @return self 异常实例
     */
    public static function invalidState(string $state, string $expected): self
    {
        return new self("无效的协议状态: $state, 期望: $expected", 3001);
    }

    /**
     * 创建一个无效事务异常
     *
     * @param string $transactionId 事务ID
     * @return self 异常实例
     */
    public static function invalidTransaction(string $transactionId): self
    {
        $hexId = bin2hex($transactionId);
        return new self("无效的事务ID: $hexId", 3002);
    }

    /**
     * 创建一个未注册处理器异常
     *
     * @param string $method 消息方法
     * @return self 异常实例
     */
    public static function handlerNotRegistered(string $method): self
    {
        return new self("未注册处理器: $method", 3003);
    }

    /**
     * 创建一个无法处理消息异常
     *
     * @param string $method 消息方法
     * @param string $reason 原因
     * @return self 异常实例
     */
    public static function cannotHandleMessage(string $method, string $reason): self
    {
        return new self("无法处理消息: $method - $reason", 3004);
    }

    /**
     * 创建一个消息处理失败异常
     *
     * @param string $method 消息方法
     * @param string $error 错误详情
     * @return self 异常实例
     */
    public static function messageHandlingFailed(string $method, string $error): self
    {
        return new self("消息处理失败: $method - $error", 3005);
    }

    /**
     * 创建一个认证失败异常
     *
     * @param string $reason 原因
     * @return self 异常实例
     */
    public static function authenticationFailed(string $reason): self
    {
        return new self("认证失败: $reason", 3006);
    }

    /**
     * 创建一个服务器配置异常
     *
     * @param string $error 错误详情
     * @return self 异常实例
     */
    public static function serverConfigurationError(string $error): self
    {
        return new self("服务器配置错误: $error", 3007);
    }

    /**
     * 创建一个不支持的操作异常
     *
     * @param string $operation 操作名称
     * @return self 异常实例
     */
    public static function unsupportedOperation(string $operation): self
    {
        return new self("不支持的操作: $operation", 3008);
    }
} 