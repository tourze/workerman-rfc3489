<?php

namespace Tourze\Workerman\RFC3489\Transport;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Tourze\Workerman\RFC3489\Exception\MessageFormatException;
use Tourze\Workerman\RFC3489\Exception\StunTransportException;
use Tourze\Workerman\RFC3489\Exception\TransportException;
use Tourze\Workerman\RFC3489\Message\StunMessage;

/**
 * UDP传输实现
 *
 * 基于Workerman的UDP传输实现
 */
class UdpTransport implements StunTransport
{
    /**
     * UDP套接字
     *
     * @var \Socket|null
     */
    private $socket;

    /**
     * 传输配置
     */
    private TransportConfig $config;

    /**
     * 是否已绑定
     */
    private bool $bound = false;

    /**
     * 上次错误信息
     */
    private ?string $lastError = null;

    /**
     * 创建一个新的UDP传输实例
     *
     * @param TransportConfig|null $config 传输配置，为null时使用默认配置
     * @param LoggerInterface|null $logger 日志记录器
     */
    public function __construct(
        ?TransportConfig $config = null,
        private readonly ?LoggerInterface $logger = null,
    ) {
        $this->config = $config ?? TransportConfig::createDefault();

        $this->init();
    }

    /**
     * 初始化UDP socket
     *
     * @throws TransportException 如果创建socket失败
     */
    private function init(): void
    {
        if (null !== $this->socket) {
            return;
        }

        // 创建UDP socket
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

        if (false === $socket) {
            $error = socket_strerror(socket_last_error());
            $this->lastError = $error;
            $this->logError("创建UDP socket失败: {$error}");
            throw TransportException::connectionFailed('', 0, $error);
        }

        $this->socket = $socket;

        // 设置socket选项
        if (null === $this->socket) {
            throw new TransportException('Socket为null，无法设置选项');
        }

        socket_set_option($this->socket, SOL_SOCKET, SO_RCVBUF, $this->config->getBufferSize());
        socket_set_option($this->socket, SOL_SOCKET, SO_SNDBUF, $this->config->getBufferSize());

        // 设置接收超时
        $timeout = $this->config->getReceiveTimeout();
        $seconds = intdiv($timeout, 1000);
        $microseconds = ($timeout % 1000) * 1000;
        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, [
            'sec' => $seconds,
            'usec' => $microseconds,
        ]);

        // 设置发送超时
        $timeout = $this->config->getSendTimeout();
        $seconds = intdiv($timeout, 1000);
        $microseconds = ($timeout % 1000) * 1000;
        socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, [
            'sec' => $seconds,
            'usec' => $microseconds,
        ]);

        // 如果配置了绑定地址和端口，则进行绑定
        if ($this->config->getBindPort() > 0) {
            $this->bind($this->config->getBindIp(), $this->config->getBindPort());
        }

        // 设置阻塞模式
        $this->setBlocking($this->config->isBlocking());
    }

    public function send(StunMessage $message, string $ip, int $port): bool
    {
        if (null === $this->socket) {
            $this->init();
        }

        if (null === $this->socket) {
            throw new StunTransportException('无法初始化UDP socket');
        }

        $data = $message->encode();
        $this->logDebug("发送STUN消息到 {$ip}:{$port}: " . bin2hex($data));

        $retryCount = $this->config->getRetryCount();
        $retryInterval = $this->config->getRetryInterval();

        for ($attempt = 0; $attempt <= $retryCount; ++$attempt) {
            if ($attempt > 0) {
                $this->logInfo("重试发送STUN消息到 {$ip}:{$port} (尝试 {$attempt}/{$retryCount})");
                usleep($retryInterval * 1000); // 毫秒转微秒
            }

            if (null === $this->socket) {
                throw new StunTransportException('Socket为null，无法发送数据');
            }

            $result = @socket_sendto($this->socket, $data, strlen($data), 0, $ip, $port);

            if (false !== $result) {
                return true;
            }

            if (null === $this->socket) {
                throw new StunTransportException('Socket为null，无法获取错误信息');
            }

            $error = socket_strerror(socket_last_error($this->socket));
            $this->lastError = $error;
            $this->logWarning("发送STUN消息到 {$ip}:{$port} 失败: {$error}");
        }

        $this->logError("发送STUN消息到 {$ip}:{$port} 失败，已重试 {$retryCount} 次");

        return false;
    }

    /**
     * 接收STUN消息
     *
     * @param int $timeout 接收超时时间（毫秒），0表示不超时
     *
     * @return array{0: StunMessage, 1: string, 2: int}|null 接收到的消息和源地址，格式为 [StunMessage, string $ip, int $port] 或 null
     */
    public function receive(int $timeout = 0): ?array
    {
        if (null === $this->socket) {
            $this->init();
        }

        if (null === $this->socket) {
            throw new StunTransportException('无法初始化UDP socket');
        }

        $originalTimeout = $this->applyCustomTimeoutIfNeeded($timeout);

        try {
            $result = $this->receiveRawData();
            $this->restoreTimeoutIfNeeded($timeout, $originalTimeout);

            return $this->processReceivedData($result);
        } catch (\Throwable $e) {
            $this->restoreTimeoutIfNeeded($timeout, $originalTimeout);
            $this->handleReceiveException($e);

            return null;
        }
    }

    /**
     * 应用自定义超时并返回原始设置（如果需要）
     *
     * @param int $timeout 超时时间（毫秒）
     *
     * @return array<string, int>|null 原始超时设置，如果未设置自定义超时则返回null
     */
    private function applyCustomTimeoutIfNeeded(int $timeout): ?array
    {
        if ($timeout <= 0) {
            return null;
        }

        if (null === $this->socket) {
            throw new StunTransportException('Socket为null，无法获取接收超时选项');
        }

        $originalTimeout = socket_get_option($this->socket, SOL_SOCKET, SO_RCVTIMEO);
        if (!is_array($originalTimeout)) {
            $originalTimeout = null;
        }

        $seconds = intdiv($timeout, 1000);
        $microseconds = ($timeout % 1000) * 1000;

        if (null === $this->socket) {
            throw new StunTransportException('Socket为null，无法设置接收超时选项');
        }

        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, [
            'sec' => $seconds,
            'usec' => $microseconds,
        ]);

        return $originalTimeout;
    }

    /**
     * 恢复原始超时设置（如果需要）
     *
     * @param int                          $timeout         超时时间（毫秒）
     * @param array<string, int>|null $originalTimeout 原始超时设置
     */
    private function restoreTimeoutIfNeeded(int $timeout, ?array $originalTimeout): void
    {
        if ($timeout > 0 && null !== $originalTimeout) {
            if (null === $this->socket) {
                throw new StunTransportException('Socket为null，无法恢复接收超时选项');
            }

            socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, $originalTimeout);
        }
    }

    /**
     * 接收原始数据
     *
     * @return array{0: int|false, 1: string, 2: string, 3: int} [result, buffer, ip, port]
     */
    private function receiveRawData(): array
    {
        $buffer = '';
        $ip = '';
        $port = 0;
        $bufferSize = $this->config->getBufferSize();

        if (null === $this->socket) {
            throw new StunTransportException('Socket为null，无法接收数据');
        }

        $result = @socket_recvfrom($this->socket, $buffer, $bufferSize, 0, $ip, $port);

        return [$result, $buffer, $ip, $port];
    }

    /**
     * 处理接收到的数据
     *
     * @param array{0: int|false, 1: string, 2: string, 3: int} $receiveResult [result, buffer, ip, port]
     *
     * @return array{0: StunMessage, 1: string, 2: int}|null 解析后的消息或null
     */
    private function processReceivedData(array $receiveResult): ?array
    {
        [$result, $buffer, $ip, $port] = $receiveResult;

        if (false === $result) {
            return $this->handleReceiveError();
        }

        if (0 === $result) {
            $this->logDebug('接收到空数据');

            return null;
        }

        $this->logDebug("接收到来自 {$ip}:{$port} 的数据: " . bin2hex($buffer));

        return $this->parseStunMessage($buffer, $ip, $port);
    }

    /**
     * 处理接收错误
     */
    private function handleReceiveError(): null
    {
        if (null === $this->socket) {
            $this->lastError = 'Socket为null，无法获取错误信息';
            $this->logWarning('Socket为null，无法获取错误信息');

            return null;
        }

        $error = socket_strerror(socket_last_error($this->socket));
        $this->lastError = $error;

        // 如果是超时错误
        if (in_array(socket_last_error($this->socket), [SOCKET_ETIMEDOUT, SOCKET_EAGAIN, SOCKET_EINPROGRESS], true)) {
            $this->logDebug('接收STUN消息超时');

            return null;
        }

        $this->logWarning("接收STUN消息失败: {$error}");

        return null;
    }

    /**
     * 解析STUN消息
     *
     * @param string $buffer STUN消息数据
     * @param string $ip     发送方IP
     * @param int    $port   发送方端口
     *
     * @return array{0: StunMessage, 1: string, 2: int}|null 解析后的消息或null
     */
    private function parseStunMessage(string $buffer, string $ip, int $port): ?array
    {
        try {
            $message = StunMessage::decode($buffer);
            $this->logInfo("成功解析来自 {$ip}:{$port} 的STUN消息");

            return [$message, $ip, $port];
        } catch (MessageFormatException $e) {
            $this->lastError = $e->getMessage();
            $this->logWarning("来自 {$ip}:{$port} 的数据不是有效的STUN消息: " . $e->getMessage());

            return null;
        }
    }

    /**
     * 处理接收异常
     *
     * @param \Throwable $e 异常
     */
    private function handleReceiveException(\Throwable $e): void
    {
        $this->lastError = $e->getMessage();
        $this->logError('接收STUN消息时发生异常: ' . $e->getMessage());
    }

    public function bind(string $ip, int $port): bool
    {
        if (null === $this->socket) {
            $this->init();
        }

        if (null === $this->socket) {
            throw new StunTransportException('无法初始化UDP socket');
        }

        if ($this->bound) {
            $this->logWarning("UDP socket已经绑定，尝试重新绑定到 {$ip}:{$port}");
        }

        $this->logInfo("绑定UDP socket到 {$ip}:{$port}");

        if (null === $this->socket) {
            throw new StunTransportException('Socket为null，无法绑定地址');
        }

        $result = @socket_bind($this->socket, $ip, $port);

        if (false === $result) {
            if (null === $this->socket) {
                throw new StunTransportException('Socket为null，无法获取绑定错误信息');
            }

            $error = socket_strerror(socket_last_error($this->socket));
            $this->lastError = $error;
            $this->logError("绑定UDP socket到 {$ip}:{$port} 失败: {$error}");

            return false;
        }

        $this->bound = true;

        return true;
    }

    public function close(): void
    {
        if (null !== $this->socket) {
            $this->logInfo('关闭UDP socket');
            assert(null !== $this->socket);
            socket_close($this->socket);
            $this->socket = null;
            $this->bound = false;
        }
    }

    public function setBlocking(bool $blocking): void
    {
        if (null === $this->socket) {
            $this->init();
        }

        if (null === $this->socket) {
            throw new StunTransportException('无法初始化UDP socket');
        }

        $this->logDebug('设置UDP socket为' . ($blocking ? '阻塞' : '非阻塞') . '模式');
        assert(null !== $this->socket);
        socket_set_nonblock($this->socket);

        if ($blocking) {
            socket_set_block($this->socket);
        }
    }

    /**
     * 获取本地地址
     *
     * @return array{0: string, 1: int}|null [IP, 端口] 或 null
     */
    public function getLocalAddress(): ?array
    {
        if (null === $this->socket || !$this->bound) {
            return null;
        }

        $ip = '';
        $port = 0;

        if (null !== $this->socket && socket_getsockname($this->socket, $ip, $port)) {
            return [$ip, $port];
        }

        return null;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * 获取配置
     *
     * @return TransportConfig 传输配置
     */
    public function getConfig(): TransportConfig
    {
        return $this->config;
    }

    /**
     * 设置配置
     *
     * @param TransportConfig $config 传输配置
     */
    public function setConfig(TransportConfig $config): void
    {
        $this->config = $config;
    }

    /**
     * 获取socket资源
     *
     * @return \Socket|null socket资源
     */
    public function getSocket()
    {
        return $this->socket;
    }

    /**
     * 日志记录 - 调试级别
     *
     * @param string $message 日志消息
     */
    private function logDebug(string $message): void
    {
        if (null !== $this->logger) {
            $this->logger->log(LogLevel::DEBUG, "[UdpTransport] {$message}");
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
            $this->logger->log(LogLevel::INFO, "[UdpTransport] {$message}");
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
            $this->logger->log(LogLevel::WARNING, "[UdpTransport] {$message}");
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
            $this->logger->log(LogLevel::ERROR, "[UdpTransport] {$message}");
        }
    }

    /**
     * 析构函数，确保资源被释放
     */
    public function __destruct()
    {
        $this->close();
    }
}
