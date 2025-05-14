<?php

namespace Tourze\Workerman\RFC3489\Protocol\Server;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Tourze\Workerman\RFC3489\Exception\MessageFormatException;
use Tourze\Workerman\RFC3489\Message\StunMessage;
use Workerman\Connection\ConnectionInterface;
use Workerman\Worker;

/**
 * STUN服务器 Workerman 适配器
 *
 * 负责将 STUN 服务器与 Workerman 框架集成
 */
class StunServerWorkermanAdapter
{
    /**
     * 绑定IP地址
     */
    private string $bindIp;

    /**
     * 绑定端口
     */
    private int $bindPort;

    /**
     * 日志记录器
     */
    private ?LoggerInterface $logger;

    /**
     * 消息路由器
     */
    private StunMessageRouter $messageRouter;

    /**
     * Workerman Worker实例
     */
    private ?Worker $worker = null;

    /**
     * 创建一个Workerman适配器
     *
     * @param string $bindIp 绑定IP地址
     * @param int $bindPort 绑定端口
     * @param StunMessageRouter $messageRouter 消息路由器
     * @param LoggerInterface|null $logger 日志记录器
     */
    public function __construct(
        string $bindIp,
        int $bindPort,
        StunMessageRouter $messageRouter,
        ?LoggerInterface $logger = null
    ) {
        $this->bindIp = $bindIp;
        $this->bindPort = $bindPort;
        $this->messageRouter = $messageRouter;
        $this->logger = $logger;
    }

    /**
     * 启动服务器
     *
     * @param bool $daemon 是否以守护进程方式运行
     * @return void
     */
    public function start(bool $daemon = false): void
    {
        $this->logInfo("使用Workerman启动STUN服务器，监听 {$this->bindIp}:{$this->bindPort}");

        // 创建UDP Worker
        $this->worker = new Worker("udp://{$this->bindIp}:{$this->bindPort}");
        $this->worker->name = 'STUN Server';

        // 设置进程数
        $this->worker->count = 1;

        // 设置是否守护进程运行
        Worker::$daemonize = $daemon;

        // 设置消息回调
        $this->worker->onMessage = function (ConnectionInterface $connection, $data) {
            try {
                // 解析STUN消息
                $request = StunMessage::decode($data);

                // 获取客户端IP和端口
                $clientIp = $connection->getRemoteIp();
                $clientPort = $connection->getRemotePort();

                $this->logDebug("收到来自 $clientIp:$clientPort 的STUN消息");

                // 路由消息
                $response = $this->messageRouter->routeMessage($request, $clientIp, $clientPort);

                if ($response !== null) {
                    // 编码响应并发送
                    $encodedResponse = $response->encode();
                    $connection->send($encodedResponse);

                    $this->logDebug("发送STUN响应到 $clientIp:$clientPort");
                }

            } catch (MessageFormatException $e) {
                $this->logWarning("收到无效STUN消息: " . $e->getMessage());
            } catch (\Throwable $e) {
                $this->logError("处理消息时发生错误: " . $e->getMessage());
            }
        };

        // 启动worker
        Worker::runAll();
    }

    /**
     * 停止服务器
     *
     * @return void
     */
    public function stop(): void
    {
        if ($this->worker !== null) {
            $this->logInfo("停止STUN服务器");
            Worker::stopAll();
            $this->worker = null;
        }
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
            $this->logger->log(LogLevel::DEBUG, "[WorkermanAdapter] $message");
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
            $this->logger->log(LogLevel::INFO, "[WorkermanAdapter] $message");
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
            $this->logger->log(LogLevel::WARNING, "[WorkermanAdapter] $message");
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
            $this->logger->log(LogLevel::ERROR, "[WorkermanAdapter] $message");
        }
    }
}
