<?php

namespace Tourze\Workerman\RFC3489\Protocol\Server;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Tourze\Workerman\RFC3489\Message\ErrorCode;
use Tourze\Workerman\RFC3489\Message\MessageClass;
use Tourze\Workerman\RFC3489\Message\MessageFactory;
use Tourze\Workerman\RFC3489\Message\MessageMethod;
use Tourze\Workerman\RFC3489\Message\StunMessage;
use Tourze\Workerman\RFC3489\Protocol\Server\Handler\StunMessageHandlerInterface;

/**
 * STUN消息路由器
 *
 * 负责将收到的STUN消息路由到相应的处理器
 */
class StunMessageRouter
{
    /**
     * 消息处理器映射
     *
     * @var array<int, StunMessageHandlerInterface>
     */
    private array $messageHandlers = [];

    /**
     * 日志记录器
     */
    private ?LoggerInterface $logger;

    /**
     * 创建一个消息路由器
     *
     * @param LoggerInterface|null $logger 日志记录器
     */
    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * 注册消息处理器
     *
     * @param MessageMethod $method 消息方法
     * @param StunMessageHandlerInterface $handler 处理器实例
     * @return self 当前实例，用于链式调用
     */
    public function registerHandler(MessageMethod $method, StunMessageHandlerInterface $handler): self
    {
        $this->messageHandlers[$method->value] = $handler;
        return $this;
    }

    /**
     * 路由消息到相应的处理器
     *
     * @param StunMessage $request 请求消息
     * @param string $clientIp 客户端IP地址
     * @param int $clientPort 客户端端口
     * @return StunMessage|null 响应消息，或null表示不需要响应
     */
    public function routeMessage(StunMessage $request, string $clientIp, int $clientPort): ?StunMessage
    {
        // 检查消息类型
        $class = $request->getClass();
        $method = $request->getMethod();

        if ($class !== MessageClass::REQUEST) {
            $this->logWarning("收到非请求消息，忽略");
            return null;
        }

        if ($method === null) {
            $this->logWarning("收到未知方法的消息，忽略");
            return null;
        }

        // 查找对应的处理器
        if (!isset($this->messageHandlers[$method->value])) {
            $this->logWarning("未找到处理器: " . $method->name);

            return MessageFactory::createErrorResponse(
                $request,
                ErrorCode::SERVER_ERROR,
                "不支持的方法: " . $method->name
            );
        }

        // 调用处理器
        $handler = $this->messageHandlers[$method->value];
        return $handler->handleMessage($request, $clientIp, $clientPort);
    }

    /**
     * 日志记录 - 警告级别
     *
     * @param string $message 日志消息
     * @return void
     */
    private function logWarning(string $message): void
    {
        if ($this->logger !== null) {
            $this->logger->log(LogLevel::WARNING, "[MessageRouter] $message");
        }
    }
}
