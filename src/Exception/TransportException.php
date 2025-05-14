<?php

namespace Tourze\Workerman\RFC3489\Exception;

/**
 * 传输层异常
 *
 * 表示在STUN消息传输过程中发生的错误
 */
class TransportException extends StunException
{
    /**
     * 创建一个新的传输层异常
     *
     * @param string $message 异常消息
     * @param int $code 异常代码
     * @param \Throwable|null $previous 上一个异常
     */
    public function __construct(string $message = "传输层错误", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * 创建一个连接失败异常
     *
     * @param string $ip IP地址
     * @param int $port 端口
     * @param string|null $error 错误详情
     * @return self 异常实例
     */
    public static function connectionFailed(string $ip, int $port, ?string $error = null): self
    {
        $message = "无法连接到 $ip:$port";
        if ($error !== null) {
            $message .= " - $error";
        }
        
        return new self($message, 1001);
    }

    /**
     * 创建一个绑定失败异常
     *
     * @param string $ip IP地址
     * @param int $port 端口
     * @param string|null $error 错误详情
     * @return self 异常实例
     */
    public static function bindFailed(string $ip, int $port, ?string $error = null): self
    {
        $message = "无法绑定到 $ip:$port";
        if ($error !== null) {
            $message .= " - $error";
        }
        
        return new self($message, 1002);
    }

    /**
     * 创建一个发送失败异常
     *
     * @param string $ip 目标IP地址
     * @param int $port 目标端口
     * @param string|null $error 错误详情
     * @return self 异常实例
     */
    public static function sendFailed(string $ip, int $port, ?string $error = null): self
    {
        $message = "发送消息到 $ip:$port 失败";
        if ($error !== null) {
            $message .= " - $error";
        }
        
        return new self($message, 1003);
    }

    /**
     * 创建一个接收失败异常
     *
     * @param string|null $error 错误详情
     * @return self 异常实例
     */
    public static function receiveFailed(?string $error = null): self
    {
        $message = "接收消息失败";
        if ($error !== null) {
            $message .= " - $error";
        }
        
        return new self($message, 1004);
    }

    /**
     * 创建一个超时异常
     *
     * @param int $timeout 超时时间（毫秒）
     * @return self 异常实例
     */
    public static function timeout(int $timeout): self
    {
        return new self("接收消息超时（{$timeout}毫秒）", 1005);
    }

    /**
     * 创建一个无效数据异常
     *
     * @param string|null $error 错误详情
     * @return self 异常实例
     */
    public static function invalidData(?string $error = null): self
    {
        $message = "接收到无效数据";
        if ($error !== null) {
            $message .= " - $error";
        }
        
        return new self($message, 1006);
    }
}
