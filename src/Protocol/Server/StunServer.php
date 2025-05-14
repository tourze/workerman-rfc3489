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
     * 服务器绑定IP地址
     */
    private string $bindIp;

    /**
     * 服务器绑定端口
     */
    private int $bindPort;

    /**
     * 备用IP地址
     */
    private string $alternateIp;

    /**
     * 备用端口
     */
    private int $alternatePort;

    /**
     * STUN传输层
     */
    private StunTransport $transport;

    /**
     * 消息路由器
     */
    private StunMessageRouter $messageRouter;

    /**
     * 独立运行适配器
     */
    private StunServerStandaloneAdapter $standaloneAdapter;

    /**
     * Workerman适配器
     */
    private ?StunServerWorkermanAdapter $workermanAdapter = null;

    /**
     * 日志记录器
     */
    private ?LoggerInterface $logger;

    /**
     * 是否正在运行
     */
    private bool $running = false;

    /**
     * 创建一个新的STUN服务器
     *
     * @param string $bindIp 服务器绑定IP地址
     * @param int $bindPort 服务器绑定端口
     * @param string $alternateIp 备用IP地址
     * @param int $alternatePort 备用端口
     * @param StunTransport $transport 传输层实现
     * @param StunMessageRouter $messageRouter 消息路由器
     * @param StunServerStandaloneAdapter $standaloneAdapter 独立运行适配器
     * @param LoggerInterface|null $logger 日志记录器
     */
    public function __construct(
        string $bindIp,
        int $bindPort,
        string $alternateIp,
        int $alternatePort,
        StunTransport $transport,
        StunMessageRouter $messageRouter,
        StunServerStandaloneAdapter $standaloneAdapter,
        ?LoggerInterface $logger = null
    ) {
        $this->bindIp = $bindIp;
        $this->bindPort = $bindPort;
        $this->alternateIp = $alternateIp;
        $this->alternatePort = $alternatePort;
        $this->transport = $transport;
        $this->messageRouter = $messageRouter;
        $this->standaloneAdapter = $standaloneAdapter;
        $this->logger = $logger;
    }

    /**
     * 启动STUN服务器
     *
     * @param bool $daemon 是否以守护进程方式运行
     * @return void
     */
    public function start(bool $daemon = false): void
    {
        if ($this->running) {
            $this->logWarning("服务器已经在运行");
            return;
        }
        
        $this->logInfo("启动STUN服务器，监听 {$this->bindIp}:{$this->bindPort}");
        
        $this->running = true;
        
        // 如果使用Workerman
        if (class_exists('Workerman\Worker')) {
            $this->startWithWorkerman($daemon);
        } else {
            $this->standaloneAdapter->start();
        }
    }
    
    /**
     * 使用Workerman启动服务器
     *
     * @param bool $daemon 是否以守护进程方式运行
     * @return void
     */
    private function startWithWorkerman(bool $daemon = false): void
    {
        $this->logInfo("使用Workerman启动STUN服务器");
        
        // 确保传输层关闭，避免端口冲突
        $this->transport->close();
        
        // 如果Workerman适配器尚未创建，则创建它
        if ($this->workermanAdapter === null) {
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
     * 停止STUN服务器
     *
     * @return void
     */
    public function stop(): void
    {
        if (!$this->running) {
            return;
        }

        $this->logInfo("停止STUN服务器");

        // 如果使用Workerman适配器
        if ($this->workermanAdapter !== null) {
            $this->workermanAdapter->stop();
        } else {
            $this->standaloneAdapter->stop();
        }

        $this->running = false;
    }

    /**
     * 获取服务器绑定地址
     *
     * @return array|null 服务器绑定地址，格式为 [string $ip, int $port] 或 null 表示尚未绑定
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
     * 日志记录 - 信息级别
     *
     * @param string $message 日志消息
     * @return void
     */
    private function logInfo(string $message): void
    {
        if ($this->logger !== null) {
            $this->logger->log(LogLevel::INFO, "[StunServer] $message");
        }
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
            $this->logger->log(LogLevel::WARNING, "[StunServer] $message");
        }
    }
}
