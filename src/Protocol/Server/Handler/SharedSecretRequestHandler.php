<?php

namespace Tourze\Workerman\RFC3489\Protocol\Server\Handler;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Tourze\Workerman\RFC3489\Message\ErrorCode;
use Tourze\Workerman\RFC3489\Message\MessageFactory;
use Tourze\Workerman\RFC3489\Message\StunMessage;

/**
 * STUN Shared Secret 请求处理器
 */
class SharedSecretRequestHandler implements StunMessageHandlerInterface
{
    /**
     * 创建一个Shared Secret请求处理器
     *
     * @param LoggerInterface|null $logger 日志记录器
     */
    public function __construct(private readonly ?LoggerInterface $logger = null)
    {
    }

    /**
     * 处理Shared Secret请求
     *
     * @param StunMessage $request    请求消息
     * @param string      $clientIp   客户端IP地址
     * @param int         $clientPort 客户端端口
     *
     * @return StunMessage|null 响应消息
     */
    public function handleMessage(StunMessage $request, string $clientIp, int $clientPort): ?StunMessage
    {
        $this->logInfo("收到来自 {$clientIp}:{$clientPort} 的Shared Secret请求");

        // 只允许TLS连接上的Shared Secret请求
        // 由于我们使用的是UDP传输，所以这里总是返回错误响应
        $this->logWarning("拒绝来自 {$clientIp}:{$clientPort} 的非TLS Shared Secret请求");

        return MessageFactory::createErrorResponse(
            $request,
            ErrorCode::UNAUTHORIZED,
            'Shared Secret请求必须在TLS连接上进行'
        );
    }

    /**
     * 日志记录 - 信息级别
     *
     * @param string $message 日志消息
     */
    private function logInfo(string $message): void
    {
        if (null !== $this->logger) {
            $this->logger->log(LogLevel::INFO, "[SharedSecretHandler] {$message}");
        }
    }

    /**
     * 日志记录 - 警告级别
     *
     * @param string $message 日志消息
     */
    private function logWarning(string $message): void
    {
        if (null !== $this->logger) {
            $this->logger->log(LogLevel::WARNING, "[SharedSecretHandler] {$message}");
        }
    }
}
