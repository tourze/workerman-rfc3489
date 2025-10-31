<?php

namespace Tourze\Workerman\RFC3489\Protocol\Server;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Tourze\Workerman\RFC3489\Transport\StunTransport;

/**
 * STUN服务器
 *
 * 实现RFC3489标准的STUN服务器功能
 */
class StunServer
{
    /**
     * Workerman适配器
     */
    private mixed $workermanAdapter = null;

    /**
     * 是否正在运行
     */
    private bool $running = false;

    /**
     * 创建一个新的STUN服务器
     *
     * @param string                      $bindIp            服务器绑定IP地址
     * @param int                         $bindPort          服务器绑定端口
     * @param string                      $alternateIp       备用IP地址
     * @param int                         $alternatePort     备用端口
     * @param StunTransport               $transport         传输层实现
     * @param StunMessageRouter           $messageRouter     消息路由器
     * @param StunServerStandaloneAdapter $standaloneAdapter 独立运行适配器
     * @param LoggerInterface|null        $logger            日志记录器
     */
    public function __construct(
        private readonly string $bindIp,
        private readonly int $bindPort,
        private readonly string $alternateIp,
        private readonly int $alternatePort,
        private readonly StunTransport $transport,
        private readonly StunMessageRouter $messageRouter,
        private readonly StunServerStandaloneAdapter $standaloneAdapter,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * 启动STUN服务器
     *
     * @param bool $daemon 是否以守护进程方式运行
     */
    public function start(bool $daemon = false): void
    {
        if ($this->running) {
            $this->logWarning('服务器已经在运行');

            return;
        }

        $this->logInfo("启动STUN服务器，监听 {$this->bindIp}:{$this->bindPort}");

        $this->running = true;

        // 如果使用Workerman且不是在测试环境中
        if (class_exists('Workerman\Worker') && !$this->isTestEnvironment()) {
            $this->startWithWorkerman($daemon);
        } else {
            $this->standaloneAdapter->start();
        }
    }

    /**
     * 使用Workerman启动服务器
     *
     * @param bool $daemon 是否以守护进程方式运行
     */
    private function startWithWorkerman(bool $daemon = false): void
    {
        $this->logInfo('使用Workerman启动STUN服务器');

        // 确保传输层关闭，避免端口冲突
        $this->transport->close();

        // 如果Workerman适配器尚未创建，则创建它
        if (null === $this->workermanAdapter) {
            $this->workermanAdapter = new StunServerWorkermanAdapter(
                $this->bindIp,
                $this->bindPort,
                $this->messageRouter,
                $this->logger
            );
        }

        // 启动Workerman适配器
        $this->workermanAdapter->start($daemon);
    }

    /**
     * 检查是否在测试环境中
     */
    private function isTestEnvironment(): bool
    {
        return defined('PHPUNIT_COMPOSER_INSTALL') || defined('PHPUNIT_VERSION') || str_contains($_ENV['APP_ENV'] ?? '', 'test');
    }

    /**
     * 停止STUN服务器
     */
    public function stop(): void
    {
        if (!$this->running) {
            return;
        }

        $this->logInfo('停止STUN服务器');

        // 如果使用Workerman适配器
        if (null !== $this->workermanAdapter) {
            $this->workermanAdapter->stop();
        } else {
            $this->standaloneAdapter->stop();
        }

        $this->running = false;
    }

    /**
     * 获取服务器绑定地址
     *
     * @return array{0: string, 1: int}|null 服务器绑定地址，格式为 [string $ip, int $port] 或 null 表示尚未绑定
     */
    public function getBindAddress(): ?array
    {
        return $this->transport->getLocalAddress();
    }

    /**
     * 检查服务器是否正在运行
     *
     * @return bool 如果服务器正在运行则返回true
     */
    public function isRunning(): bool
    {
        return $this->running;
    }

    /**
     * 获取传输层
     *
     * @return StunTransport 传输层实例
     */
    public function getTransport(): StunTransport
    {
        return $this->transport;
    }

    /**
     * 获取备用IP地址
     *
     * @return string 备用IP地址
     */
    public function getAlternateIp(): string
    {
        return $this->alternateIp;
    }

    /**
     * 获取备用端口
     *
     * @return int 备用端口
     */
    public function getAlternatePort(): int
    {
        return $this->alternatePort;
    }

    /**
     * 日志记录 - 信息级别
     *
     * @param string $message 日志消息
     */
    private function logInfo(string $message): void
    {
        if (null !== $this->logger) {
            $this->logger->log(LogLevel::INFO, "[StunServer] {$message}");
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
            $this->logger->log(LogLevel::WARNING, "[StunServer] {$message}");
        }
    }
}
