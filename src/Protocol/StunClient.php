<?php

namespace Tourze\Workerman\RFC3489\Protocol;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Tourze\Workerman\RFC3489\Exception\ProtocolException;
use Tourze\Workerman\RFC3489\Exception\TimeoutException;
use Tourze\Workerman\RFC3489\Exception\TransportException;
use Tourze\Workerman\RFC3489\Message\Attributes\MappedAddress;
use Tourze\Workerman\RFC3489\Message\AttributeType;
use Tourze\Workerman\RFC3489\Message\Constants;
use Tourze\Workerman\RFC3489\Message\MessageFactory;
use Tourze\Workerman\RFC3489\Transport\StunTransport;
use Tourze\Workerman\RFC3489\Transport\TransportConfig;
use Tourze\Workerman\RFC3489\Transport\UdpTransport;

/**
 * STUN客户端
 *
 * 实现RFC3489标准的STUN客户端功能，包括NAT类型检测和公网地址获取
 */
class StunClient
{
    /**
     * STUN传输接口
     */
    private StunTransport $transport;

    /**
     * 请求超时时间（毫秒）
     */
    private int $requestTimeout;

    /**
     * 创建一个新的STUN客户端
     *
     * @param string               $serverAddress  STUN服务器地址
     * @param int                  $serverPort     STUN服务器端口
     * @param StunTransport|null   $transport      传输层实现，默认使用UDP传输
     * @param LoggerInterface|null $logger         日志记录器
     * @param int                  $requestTimeout 请求超时时间（毫秒），默认为5000ms
     */
    public function __construct(
        private readonly string $serverAddress,
        private readonly int $serverPort = Constants::DEFAULT_PORT,
        ?StunTransport $transport = null,
        private readonly ?LoggerInterface $logger = null,
        int $requestTimeout = 5000,
    ) {
        $this->requestTimeout = $requestTimeout;

        if (null === $transport) {
            $config = new TransportConfig();
            $transport = new UdpTransport($config, $logger);
        }

        $this->transport = $transport;
    }

    /**
     * 发现公网IP地址和端口
     *
     * @return array{0: string, 1: int} 公网地址，格式为 [string $ip, int $port]
     *
     * @throws ProtocolException 如果发现过程中发生错误
     * @throws TimeoutException 如果请求超时
     * @throws TransportException 如果传输层发生错误
     */
    public function discoverPublicAddress(): array
    {
        try {
            $this->logInfo('开始发现公网地址');

            // 解析服务器地址（如果是域名）
            $serverIp = $this->resolveServerAddress();

            // 创建基本的Binding请求
            $request = MessageFactory::createBindingRequest();
            $this->logDebug('创建Binding请求: ' . bin2hex($request->getTransactionId()));

            // 发送请求
            $this->logInfo("发送Binding请求到 {$serverIp}:{$this->serverPort}");
            $result = $this->transport->send($request, $serverIp, $this->serverPort);

            if (!$result) {
                $error = $this->transport->getLastError() ?? '未知错误';
                throw TransportException::sendFailed($serverIp, $this->serverPort, $error);
            }

            // 接收响应
            $this->logInfo("等待响应，超时时间: {$this->requestTimeout}毫秒");
            $response = $this->transport->receive($this->requestTimeout);

            if (null === $response) {
                $error = $this->transport->getLastError();
                if (null !== $error) {
                    throw TransportException::receiveFailed($error);
                }
                throw TimeoutException::receiveTimeout($this->requestTimeout);
            }

            [$message, $responseIp, $responsePort] = $response;
            $this->logInfo("收到来自 {$responseIp}:{$responsePort} 的响应");

            // 验证事务ID
            if ($message->getTransactionId() !== $request->getTransactionId()) {
                $this->logWarning('响应事务ID不匹配，忽略此响应');
                throw ProtocolException::invalidTransaction($message->getTransactionId());
            }

            // 提取映射地址
            $mappedAddress = $message->getAttribute(AttributeType::MAPPED_ADDRESS);
            if (!$mappedAddress instanceof MappedAddress) {
                throw ProtocolException::messageHandlingFailed('发现公网地址', '响应中没有映射地址信息');
            }

            $publicIp = $mappedAddress->getIp();
            $publicPort = $mappedAddress->getPort();

            $this->logInfo("发现公网地址: {$publicIp}:{$publicPort}");

            return [$publicIp, $publicPort];
        } catch (TransportException|TimeoutException|ProtocolException $e) {
            // 重新抛出传输、超时和协议异常
            $this->logError('发现公网地址失败: ' . $e->getMessage());
            throw $e;
        } catch (\Throwable $e) {
            $this->logError('发现公网地址时发生未知错误: ' . $e->getMessage());
            throw ProtocolException::messageHandlingFailed('发现公网地址', $e->getMessage());
        }
    }

    /**
     * 解析服务器地址
     *
     * @return string 服务器IP地址
     *
     * @throws ProtocolException 如果无法解析服务器地址
     */
    private function resolveServerAddress(): string
    {
        // 如果已经是IP地址，直接返回
        if (false !== filter_var($this->serverAddress, FILTER_VALIDATE_IP)) {
            return $this->serverAddress;
        }

        $this->logInfo("解析STUN服务器域名: {$this->serverAddress}");

        // 尝试解析域名
        $ips = gethostbynamel($this->serverAddress);
        if (false === $ips || [] === $ips) {
            throw new ProtocolException("无法解析STUN服务器域名: {$this->serverAddress}");
        }

        $serverIp = $ips[0];
        $this->logInfo("解析STUN服务器域名成功: {$this->serverAddress} -> {$serverIp}");

        return $serverIp;
    }

    /**
     * 关闭客户端，释放资源
     */
    public function close(): void
    {
        $this->logInfo('关闭STUN客户端');
        $this->transport->close();
    }

    /**
     * 获取传输层实例
     *
     * @return StunTransport 传输层实例
     */
    public function getTransport(): StunTransport
    {
        return $this->transport;
    }

    /**
     * 设置请求超时时间
     *
     * @param int $timeout 超时时间（毫秒）
     */
    public function setRequestTimeout(int $timeout): void
    {
        $this->requestTimeout = $timeout;
    }

    /**
     * 日志记录 - 调试级别
     *
     * @param string $message 日志消息
     */
    private function logDebug(string $message): void
    {
        if (null !== $this->logger) {
            $this->logger->log(LogLevel::DEBUG, "[StunClient] {$message}");
        }
    }

    /**
     * 日志记录 - 信息级别
     *
     * @param string $message 日志消息
     */
    private function logInfo(string $message): void
    {
        if (null !== $this->logger) {
            $this->logger->log(LogLevel::INFO, "[StunClient] {$message}");
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
            $this->logger->log(LogLevel::WARNING, "[StunClient] {$message}");
        }
    }

    /**
     * 日志记录 - 错误级别
     *
     * @param string $message 日志消息
     */
    private function logError(string $message): void
    {
        if (null !== $this->logger) {
            $this->logger->log(LogLevel::ERROR, "[StunClient] {$message}");
        }
    }
}
