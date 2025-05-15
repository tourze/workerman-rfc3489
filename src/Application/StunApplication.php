<?php

namespace Tourze\Workerman\RFC3489\Application;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Tourze\Workerman\RFC3489\Exception\StunException;

/**
 * STUN应用基类
 *
 * 为STUN客户端和服务器应用提供通用功能
 */
abstract class StunApplication
{
    /**
     * 应用配置
     */
    protected StunConfig $config;

    /**
     * 日志记录器
     */
    protected ?LoggerInterface $logger;

    /**
     * 是否正在运行
     */
    protected bool $running = false;

    /**
     * 创建一个新的STUN应用
     *
     * @param StunConfig|array $config 应用配置或配置数组
     * @param LoggerInterface|null $logger 日志记录器
     */
    public function __construct($config, ?LoggerInterface $logger = null)
    {
        // 如果传入的是数组，则创建配置对象
        if (is_array($config)) {
            $config = new StunConfig($config);
        } elseif (!$config instanceof StunConfig) {
            throw new \InvalidArgumentException('配置必须是StunConfig对象或配置数组');
        }

        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * 启动应用
     *
     * @param bool $daemon 是否以守护进程方式运行
     * @return void
     * @throws StunException 如果启动失败
     */
    abstract public function start(bool $daemon = false): void;

    /**
     * 停止应用
     *
     * @return void
     */
    abstract public function stop(): void;

    /**
     * 重新启动应用
     *
     * @param bool $daemon 是否以守护进程方式运行
     * @return void
     * @throws StunException 如果重启失败
     */
    public function restart(bool $daemon = false): void
    {
        $this->stop();
        $this->start($daemon);
    }

    /**
     * 检查应用是否正在运行
     *
     * @return bool 如果应用正在运行则返回true
     */
    public function isRunning(): bool
    {
        return $this->running;
    }

    /**
     * 获取应用配置
     *
     * @return StunConfig 应用配置
     */
    public function getConfig(): StunConfig
    {
        return $this->config;
    }

    /**
     * 设置应用配置
     *
     * @param StunConfig|array $config 应用配置或配置数组
     * @return self 当前实例，用于链式调用
     */
    public function setConfig($config): self
    {
        if (is_array($config)) {
            $config = new StunConfig($config);
        } elseif (!$config instanceof StunConfig) {
            throw new \InvalidArgumentException('配置必须是StunConfig对象或配置数组');
        }

        $this->config = $config;
        return $this;
    }

    /**
     * 获取日志记录器
     *
     * @return LoggerInterface|null 日志记录器，如果没有设置则返回null
     */
    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    /**
     * 设置日志记录器
     *
     * @param LoggerInterface|null $logger 日志记录器
     * @return self 当前实例，用于链式调用
     */
    public function setLogger(?LoggerInterface $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * 日志记录 - 调试级别
     *
     * @param string $message 日志消息
     * @return void
     */
    protected function logDebug(string $message): void
    {
        if ($this->logger !== null) {
            $this->logger->log(LogLevel::DEBUG, "[StunApp] $message");
        }
    }

    /**
     * 日志记录 - 信息级别
     *
     * @param string $message 日志消息
     * @return void
     */
    protected function logInfo(string $message): void
    {
        if ($this->logger !== null) {
            $this->logger->log(LogLevel::INFO, "[StunApp] $message");
        }
    }

    /**
     * 日志记录 - 警告级别
     *
     * @param string $message 日志消息
     * @return void
     */
    protected function logWarning(string $message): void
    {
        if ($this->logger !== null) {
            $this->logger->log(LogLevel::WARNING, "[StunApp] $message");
        }
    }

    /**
     * 日志记录 - 错误级别
     *
     * @param string $message 日志消息
     * @return void
     */
    protected function logError(string $message): void
    {
        if ($this->logger !== null) {
            $this->logger->log(LogLevel::ERROR, "[StunApp] $message");
        }
    }
}
