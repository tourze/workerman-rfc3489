<?php

namespace Tourze\Workerman\RFC3489\Protocol\NatDetection;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Tourze\Workerman\RFC3489\Exception\TimeoutException;
use Tourze\Workerman\RFC3489\Exception\TransportException;
use Tourze\Workerman\RFC3489\Message\StunMessage;
use Tourze\Workerman\RFC3489\Transport\StunTransport;
use Tourze\Workerman\RFC3489\Utils\IpUtils;

/**
 * STUN请求发送器
 * 
 * 负责发送STUN请求和接收响应
 */
class StunRequestSender
{
    /**
     * STUN传输接口
     */
    private StunTransport $transport;

    /**
     * 日志记录器
     */
    private ?LoggerInterface $logger;

    /**
     * 超时时间（毫秒）
     */
    private int $timeout;

    /**
     * 构造函数
     *
     * @param StunTransport $transport 传输层接口
     * @param int $timeout 超时时间（毫秒）
     * @param LoggerInterface|null $logger 日志记录器
     */
    public function __construct(StunTransport $transport, int $timeout = 5000, ?LoggerInterface $logger = null)
    {
        $this->transport = $transport;
        $this->timeout = $timeout;
        $this->logger = $logger;
    }

    /**
     * 获取传输层接口
     */
    public function getTransport(): StunTransport
    {
        return $this->transport;
    }

    /**
     * 获取日志记录器
     */
    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    /**
     * 记录消息
     */
    public function log(string $level, string $message): void
    {
        if ($this->logger === null) {
            return;
        }

        $logLevel = match ($level) {
            'debug' => LogLevel::DEBUG,
            'info' => LogLevel::INFO,
            'warning' => LogLevel::WARNING,
            'error' => LogLevel::ERROR,
            default => LogLevel::INFO,
        };

        $this->logger->log($logLevel, "[StunSender] $message");
    }

    /**
     * 发送STUN请求并等待响应
     *
     * @param StunMessage $request 请求消息
     * @param string $destIp 目标IP地址
     * @param int $destPort 目标端口
     * @return StunMessage|null 如果成功则返回响应消息，否则返回null
     * @throws TimeoutException 如果请求超时
     * @throws TransportException 如果传输过程中发生错误
     */
    public function sendRequest(StunMessage $request, string $destIp, int $destPort): ?StunMessage
    {
        $this->log('debug', "发送请求到 $destIp:$destPort");

        $startTime = microtime(true);

        // 解析域名为IP地址（如果是域名的话）
        $resolvedDestIp = $destIp;
        if (!filter_var($destIp, FILTER_VALIDATE_IP)) {
            $ips = gethostbynamel($destIp);
            if (!empty($ips)) {
                $resolvedDestIp = $ips[0];
                $this->log('debug', "将域名 $destIp 解析为IP地址 $resolvedDestIp");
            }
        }

        // 检查目标IP是否为0.0.0.0，这是一个无效的目标地址
        if ($resolvedDestIp === '0.0.0.0') {
            $this->log('warning', "目标IP为0.0.0.0，这是一个无效的目标地址");
            return null;
        }

        // 确保传输已关闭并重新绑定
        $this->transport->close();
        if (!$this->transport->bind('0.0.0.0', 0)) {
            throw new TransportException("无法绑定传输");
        }

        // 发送请求
        if (!$this->transport->send($request, $destIp, $destPort)) {
            $error = $this->transport->getLastError() ?? "未知错误";
            throw new TransportException("发送请求失败: $error");
        }

        // 等待响应
        while (true) {
            // 检查是否超时
            if ((microtime(true) - $startTime) * 1000 > $this->timeout) {
                throw new TimeoutException("等待响应超时");
            }

            // 尝试接收响应
            $result = $this->transport->receive($this->timeout);

            if ($result === null) {
                continue;
            }

            [$response, $responseIp, $responsePort] = $result;

            // 检查响应是否来自预期的服务器
            // 使用解析后的IP地址进行比较，或者如果响应IP在解析结果中也接受
            if (!filter_var($destIp, FILTER_VALIDATE_IP)) {
                // 如果原始目标是域名，尝试获取所有可能的IP
                $allIps = gethostbynamel($destIp) ?: [];
                if (in_array($responseIp, $allIps, true)) {
                    // 响应来自域名解析的其中一个IP，接受它
                    $this->log('debug', "接受来自 $responseIp 的响应，它是 $destIp 域名的解析结果之一");
                } else {
                    $this->log('warning', "收到来自未知IP的响应: $responseIp (域名 $destIp 解析结果不包含此IP)");
                    continue;
                }
            } else if (!IpUtils::ipEquals($responseIp, $destIp)) {
                // 如果原始目标就是IP地址，直接比较
                $this->log('warning', "收到来自未知IP的响应: $responseIp (预期: $destIp)");
                continue;
            }

            // 验证响应事务ID
            if ($response->getTransactionId() !== $request->getTransactionId()) {
                $this->log('warning', "收到事务ID不匹配的响应");
                continue;
            }

            $elapsedMs = (microtime(true) - $startTime) * 1000;
            $this->log('debug', "收到响应，耗时: " . round($elapsedMs) . "ms");

            return $response;
        }
    }
    
    /**
     * 关闭传输连接
     */
    public function close(): void
    {
        try {
            $this->transport->close();
        } catch (\Throwable $e) {
            $this->log('warning', "关闭传输连接时发生错误: " . $e->getMessage());
        }
    }
}
