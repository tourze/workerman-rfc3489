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
     * @param StunTransport        $transport 传输层接口
     * @param int                  $timeout   超时时间（毫秒）
     * @param LoggerInterface|null $logger    日志记录器
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
        if (null === $this->logger) {
            return;
        }

        $logLevel = match ($level) {
            'debug' => LogLevel::DEBUG,
            'info' => LogLevel::INFO,
            'warning' => LogLevel::WARNING,
            'error' => LogLevel::ERROR,
            default => LogLevel::INFO,
        };

        $this->logger->log($logLevel, "[StunSender] {$message}");
    }

    /**
     * 发送STUN请求并等待响应
     *
     * @param StunMessage $request  请求消息
     * @param string      $destIp   目标IP地址
     * @param int         $destPort 目标端口
     *
     * @return StunMessage|null 如果成功则返回响应消息，否则返回null
     *
     * @throws TimeoutException   如果请求超时
     * @throws TransportException 如果传输过程中发生错误
     */
    public function sendRequest(StunMessage $request, string $destIp, int $destPort): ?StunMessage
    {
        $this->log('debug', "发送请求到 {$destIp}:{$destPort}");

        $startTime = microtime(true);
        $resolvedDestIp = $this->resolveDestinationIp($destIp);

        if ('0.0.0.0' === $resolvedDestIp) {
            $this->log('warning', '目标IP为0.0.0.0，这是一个无效的目标地址');

            return null;
        }

        $this->setupTransport();
        $this->sendRequestMessage($request, $destIp, $destPort);

        return $this->waitForResponse($request, $destIp, $startTime);
    }

    /**
     * 解析目标IP地址
     *
     * @param string $destIp 目标IP或域名
     *
     * @return string 解析后的IP地址
     */
    private function resolveDestinationIp(string $destIp): string
    {
        if (false !== filter_var($destIp, FILTER_VALIDATE_IP)) {
            return $destIp;
        }

        $ips = gethostbynamel($destIp);
        if (false !== $ips && count($ips) > 0) {
            $resolvedIp = $ips[0];
            $this->log('debug', "将域名 {$destIp} 解析为IP地址 {$resolvedIp}");

            return $resolvedIp;
        }

        return $destIp;
    }

    /**
     * 设置传输
     *
     * @throws TransportException 如果传输设置失败
     */
    private function setupTransport(): void
    {
        $this->transport->close();
        if (!$this->transport->bind('0.0.0.0', 0)) {
            throw new TransportException('无法绑定传输');
        }
    }

    /**
     * 发送请求消息
     *
     * @param StunMessage $request  请求消息
     * @param string      $destIp   目标IP地址
     * @param int         $destPort 目标端口
     *
     * @throws TransportException 如果发送失败
     */
    private function sendRequestMessage(StunMessage $request, string $destIp, int $destPort): void
    {
        if (!$this->transport->send($request, $destIp, $destPort)) {
            $error = $this->transport->getLastError() ?? '未知错误';
            throw new TransportException("发送请求失败: {$error}");
        }
    }

    /**
     * 等待响应
     *
     * @param StunMessage $request   原始请求
     * @param string      $destIp    目标IP地址
     * @param float       $startTime 开始时间
     *
     * @return StunMessage 响应消息
     *
     * @throws TimeoutException 如果超时
     */
    private function waitForResponse(StunMessage $request, string $destIp, float $startTime): StunMessage
    {
        while (true) {
            $this->checkTimeout($startTime);

            $result = $this->transport->receive($this->timeout);
            if (null === $result) {
                continue;
            }

            [$response, $responseIp, $responsePort] = $result;

            if (!$this->isValidResponse($response, $request, $responseIp, $destIp)) {
                continue;
            }

            $elapsedMs = (microtime(true) - $startTime) * 1000;
            $this->log('debug', '收到响应，耗时: ' . round($elapsedMs) . 'ms');

            return $response;
        }
    }

    /**
     * 检查是否超时
     *
     * @param float $startTime 开始时间
     *
     * @throws TimeoutException 如果超时
     */
    private function checkTimeout(float $startTime): void
    {
        if ((microtime(true) - $startTime) * 1000 > $this->timeout) {
            throw new TimeoutException('等待响应超时');
        }
    }

    /**
     * 验证响应是否有效
     *
     * @param StunMessage $response   响应消息
     * @param StunMessage $request    原始请求
     * @param string      $responseIp 响应IP
     * @param string      $destIp     目标IP
     *
     * @return bool 响应是否有效
     */
    private function isValidResponse(StunMessage $response, StunMessage $request, string $responseIp, string $destIp): bool
    {
        if (!$this->isResponseFromExpectedServer($responseIp, $destIp)) {
            return false;
        }

        if ($response->getTransactionId() !== $request->getTransactionId()) {
            $this->log('warning', '收到事务ID不匹配的响应');

            return false;
        }

        return true;
    }

    /**
     * 检查响应是否来自预期的服务器
     *
     * @param string $responseIp 响应IP
     * @param string $destIp     目标IP
     *
     * @return bool 是否来自预期服务器
     */
    private function isResponseFromExpectedServer(string $responseIp, string $destIp): bool
    {
        if (false === filter_var($destIp, FILTER_VALIDATE_IP)) {
            return $this->isResponseFromDomainIp($responseIp, $destIp);
        }

        if (!IpUtils::ipEquals($responseIp, $destIp)) {
            $this->log('warning', "收到来自未知IP的响应: {$responseIp} (预期: {$destIp})");

            return false;
        }

        return true;
    }

    /**
     * 检查响应是否来自域名解析的IP
     *
     * @param string $responseIp 响应IP
     * @param string $domainName 域名
     *
     * @return bool 是否来自域名解析的IP
     */
    private function isResponseFromDomainIp(string $responseIp, string $domainName): bool
    {
        $allIps = gethostbynamel($domainName);
        if (false === $allIps) {
            $allIps = [];
        }

        if (in_array($responseIp, $allIps, true)) {
            $this->log('debug', "接受来自 {$responseIp} 的响应，它是 {$domainName} 域名的解析结果之一");

            return true;
        }

        $this->log('warning', "收到来自未知IP的响应: {$responseIp} (域名 {$domainName} 解析结果不包含此IP)");

        return false;
    }

    /**
     * 关闭传输连接
     */
    public function close(): void
    {
        try {
            $this->transport->close();
        } catch (\Throwable $e) {
            $this->log('warning', '关闭传输连接时发生错误: ' . $e->getMessage());
        }
    }
}
