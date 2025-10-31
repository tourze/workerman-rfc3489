<?php

namespace Tourze\Workerman\RFC3489\Utils;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * STUN日志记录工具
 *
 * 实现PSR LoggerInterface接口的日志记录器
 */
class StunLogger extends AbstractLogger implements LoggerInterface
{
    /**
     * 内部日志记录器实例
     */
    private LoggerInterface $logger;

    /**
     * 构造函数
     *
     * @param LoggerInterface|null $logger 日志记录器实例，如果为null则使用NullLogger
     */
    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * 记录日志
     *
     * @param mixed              $level   日志级别
     * @param string|\Stringable $message 日志消息
     * @param array<mixed>       $context 上下文信息
     */
    public function log($level, $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }

    /**
     * 获取内部日志记录器实例
     *
     * @return LoggerInterface 日志记录器实例
     */
    public function getInternalLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * 设置内部日志记录器实例
     *
     * @param LoggerInterface $logger 日志记录器实例
     */
    public function setInternalLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
