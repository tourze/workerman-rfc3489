<?php

namespace Tourze\Workerman\RFC3489\Protocol\Server;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Tourze\Workerman\RFC3489\Exception\MessageFormatException;
use Tourze\Workerman\RFC3489\Exception\ProtocolException;
use Tourze\Workerman\RFC3489\Transport\StunTransport;

/**
 * STUN服务器独立运行适配器
 *
 * 用于在不依赖Workerman的环境中运行STUN服务器
 */
class StunServerStandaloneAdapter
{
    /**
     * STUN传输层
     */
    private StunTransport $transport;

    /**
     * 绑定IP地址
     */
    private string $bindIp;

    /**
     * 绑定端口
     */
    private int $bindPort;

    /**
     * 消息路由器
     */
    private StunMessageRouter $messageRouter;

    /**
     * 日志记录器
     */
    private ?LoggerInterface $logger;

    /**
     * 是否正在运行
     */
    private bool $running = false;

    /**
     * 创建一个独立运行适配器
     *
     * @param StunTransport $transport 传输层实例
     * @param string $bindIp 绑定IP地址
     * @param int $bindPort 绑定端口
     * @param StunMessageRouter $messageRouter 消息路由器
     * @param LoggerInterface|null $logger 日志记录器
     */
    public function __construct(
        StunTransport $transport,
        string $bindIp,
        int $bindPort,
        StunMessageRouter $messageRouter,
        ?LoggerInterface $logger = null
    ) {
        $this->transport = $transport;
        $this->bindIp = $bindIp;
        $this->bindPort = $bindPort;
        $this->messageRouter = $messageRouter;
        $this->logger = $logger;
    }

    /**
     * 启动服务器
     *
     * @return void
     * @throws ProtocolException 如果无法绑定到指定地址和端口
     */
    public function start(): void
    {
        if ($this->running) {
            $this->logWarning("服务器已经在运行");
            return;
        }

        $this->logInfo("使用独立模式启动STUN服务器，监听 {$this->bindIp}:{$this->bindPort}");

        // 绑定到指定地址和端口
        if (!$this->transport->bind($this->bindIp, $this->bindPort)) {
            $error = $this->transport->getLastError() ?? "未知错误";
            $this->logError("无法绑定到 {$this->bindIp}:{$this->bindPort} - $error");
            throw ProtocolException::serverConfigurationError("无法绑定到指定地址和端口: $error");
        }

        $this->running = true;

        // 主循环
        $this->runMainLoop();
    }

    /**
     * 运行主循环
     *
     * @return void
     */
    private function runMainLoop(): void
    {
        // 主循环
        while ($this->running) {
            try {
                // 接收消息
                $result = $this->transport->receive();

                if ($result !== null) {
                    [$request, $clientIp, $clientPort] = $result;

                    $this->logDebug("收到来自 $clientIp:$clientPort 的STUN消息");

                    // 路由消息
                    $response = $this->messageRouter->routeMessage($request, $clientIp, $clientPort);

                    if ($response !== null) {
                        // 发送响应
                        $this->transport->send($response, $clientIp, $clientPort);

                        $this->logDebug("发送STUN响应到 $clientIp:$clientPort");
                    }
                }

            } catch (MessageFormatException $e) {
                $this->logWarning("收到无效STUN消息: " . $e->getMessage());
            } catch (\Throwable $e) {
                $this->logError("处理消息时发生错误: " . $e->getMessage());
            }

            // 短暂休眠，避免CPU占用过高
            usleep(1000); // 1ms
        }
    }

    /**
     * 停止服务器
     *
     * @return void
     */
    public function stop(): void
    {
        if (!$this->running) {
            return;
        }

        $this->logInfo("停止STUN服务器");
        $this->running = false;

        // 关闭传输层
        $this->transport->close();
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
     * 日志记录 - 调试级别
     *
     * @param string $message 日志消息
     * @return void
     */
    private function logDebug(string $message): void
    {
        if ($this->logger !== null) {
            $this->logger->log(LogLevel::DEBUG, "[StandaloneAdapter] $message");
        }
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
            $this->logger->log(LogLevel::INFO, "[StandaloneAdapter] $message");
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
            $this->logger->log(LogLevel::WARNING, "[StandaloneAdapter] $message");
        }
    }

    /**
     * 日志记录 - 错误级别
     *
     * @param string $message 日志消息
     * @return void
     */
    private function logError(string $message): void
    {
        if ($this->logger !== null) {
            $this->logger->log(LogLevel::ERROR, "[StandaloneAdapter] $message");
        }
    }
}
