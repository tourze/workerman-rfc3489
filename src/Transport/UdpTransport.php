<?php

namespace Tourze\Workerman\RFC3489\Transport;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Tourze\Workerman\RFC3489\Exception\MessageFormatException;
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
     * UDP socket
     *
     * @var resource|null
     */
    private $socket = null;
    
    /**
     * 传输配置
     *
     * @var TransportConfig
     */
    private TransportConfig $config;
    
    /**
     * 是否已绑定
     *
     * @var bool
     */
    private bool $bound = false;
    
    /**
     * 上次错误信息
     *
     * @var string|null
     */
    private ?string $lastError = null;
    
    /**
     * 日志记录器
     *
     * @var LoggerInterface|null
     */
    private ?LoggerInterface $logger;
    
    /**
     * 创建一个新的UDP传输实例
     *
     * @param TransportConfig|null $config 传输配置，为null时使用默认配置
     * @param LoggerInterface|null $logger 日志记录器
     */
    public function __construct(?TransportConfig $config = null, ?LoggerInterface $logger = null)
    {
        $this->config = $config ?? TransportConfig::createDefault();
        $this->logger = $logger;
        
        $this->init();
    }
    
    /**
     * 初始化UDP socket
     *
     * @return void
     * @throws TransportException 如果创建socket失败
     */
    private function init(): void
    {
        if ($this->socket !== null) {
            return;
        }
        
        // 创建UDP socket
        $this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        
        if ($this->socket === false) {
            $error = socket_strerror(socket_last_error());
            $this->lastError = $error;
            $this->logError("创建UDP socket失败: $error");
            throw TransportException::connectionFailed('', 0, $error);
        }
        
        // 设置socket选项
        socket_set_option($this->socket, SOL_SOCKET, SO_RCVBUF, $this->config->getBufferSize());
        socket_set_option($this->socket, SOL_SOCKET, SO_SNDBUF, $this->config->getBufferSize());
        
        // 设置接收超时
        $timeout = $this->config->getReceiveTimeout();
        $seconds = intdiv($timeout, 1000);
        $microseconds = ($timeout % 1000) * 1000;
        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, [
            'sec' => $seconds,
            'usec' => $microseconds
        ]);
        
        // 设置发送超时
        $timeout = $this->config->getSendTimeout();
        $seconds = intdiv($timeout, 1000);
        $microseconds = ($timeout % 1000) * 1000;
        socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, [
            'sec' => $seconds,
            'usec' => $microseconds
        ]);
        
        // 如果配置了绑定地址和端口，则进行绑定
        if ($this->config->getBindPort() > 0) {
            $this->bind($this->config->getBindIp(), $this->config->getBindPort());
        }
        
        // 设置阻塞模式
        $this->setBlocking($this->config->isBlocking());
    }
    
    /**
     * {@inheritdoc}
     */
    public function send(StunMessage $message, string $ip, int $port): bool
    {
        if ($this->socket === null) {
            $this->init();
        }
        
        $data = $message->encode();
        $this->logDebug("发送STUN消息到 $ip:$port: " . bin2hex($data));
        
        $retryCount = $this->config->getRetryCount();
        $retryInterval = $this->config->getRetryInterval();
        
        for ($attempt = 0; $attempt <= $retryCount; $attempt++) {
            if ($attempt > 0) {
                $this->logInfo("重试发送STUN消息到 $ip:$port (尝试 $attempt/$retryCount)");
                usleep($retryInterval * 1000); // 毫秒转微秒
            }
            
            $result = @socket_sendto($this->socket, $data, strlen($data), 0, $ip, $port);
            
            if ($result !== false) {
                return true;
            }
            
            $error = socket_strerror(socket_last_error($this->socket));
            $this->lastError = $error;
            $this->logWarning("发送STUN消息到 $ip:$port 失败: $error");
        }
        
        $this->logError("发送STUN消息到 $ip:$port 失败，已重试 $retryCount 次");
        return false;
    }
    
    /**
     * {@inheritdoc}
     */
    public function receive(int $timeout = 0): ?array
    {
        if ($this->socket === null) {
            $this->init();
        }
        
        // 如果设置了自定义超时，则临时修改socket选项
        if ($timeout > 0) {
            $originalTimeout = socket_get_option($this->socket, SOL_SOCKET, SO_RCVTIMEO);
            $seconds = intdiv($timeout, 1000);
            $microseconds = ($timeout % 1000) * 1000;
            socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, [
                'sec' => $seconds,
                'usec' => $microseconds
            ]);
        }
        
        $buffer = '';
        $ip = '';
        $port = 0;
        
        try {
            $bufferSize = $this->config->getBufferSize();
            $result = @socket_recvfrom($this->socket, $buffer, $bufferSize, 0, $ip, $port);
            
            // 如果设置了自定义超时，则恢复原来的设置
            if ($timeout > 0) {
                socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, $originalTimeout);
            }
            
            if ($result === false) {
                $error = socket_strerror(socket_last_error($this->socket));
                $this->lastError = $error;
                
                // 如果是超时错误
                if (in_array(socket_last_error($this->socket), [SOCKET_ETIMEDOUT, SOCKET_EAGAIN, SOCKET_EINPROGRESS])) {
                    $this->logDebug("接收STUN消息超时");
                    return null;
                }
                
                $this->logWarning("接收STUN消息失败: $error");
                return null;
            }
            
            if ($result === 0) {
                $this->logDebug("接收到空数据");
                return null;
            }
            
            $this->logDebug("接收到来自 $ip:$port 的数据: " . bin2hex($buffer));
            
            // 尝试解析为STUN消息
            try {
                $message = StunMessage::decode($buffer);
                $this->logInfo("成功解析来自 $ip:$port 的STUN消息");
                
                return [$message, $ip, $port];
            } catch (MessageFormatException $e) {
                $this->lastError = $e->getMessage();
                $this->logWarning("来自 $ip:$port 的数据不是有效的STUN消息: " . $e->getMessage());
                return null;
            }
        } catch (\Throwable $e) {
            // 如果设置了自定义超时，确保恢复原来的设置
            if ($timeout > 0) {
                socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, $originalTimeout);
            }
            
            $this->lastError = $e->getMessage();
            $this->logError("接收STUN消息时发生异常: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function bind(string $ip, int $port): bool
    {
        if ($this->socket === null) {
            $this->init();
        }
        
        if ($this->bound) {
            $this->logWarning("UDP socket已经绑定，尝试重新绑定到 $ip:$port");
        }
        
        $this->logInfo("绑定UDP socket到 $ip:$port");
        
        $result = @socket_bind($this->socket, $ip, $port);
        
        if ($result === false) {
            $error = socket_strerror(socket_last_error($this->socket));
            $this->lastError = $error;
            $this->logError("绑定UDP socket到 $ip:$port 失败: $error");
            return false;
        }
        
        $this->bound = true;
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        if ($this->socket !== null) {
            $this->logInfo("关闭UDP socket");
            socket_close($this->socket);
            $this->socket = null;
            $this->bound = false;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function setBlocking(bool $blocking): void
    {
        if ($this->socket === null) {
            $this->init();
        }
        
        $this->logDebug("设置UDP socket为" . ($blocking ? "阻塞" : "非阻塞") . "模式");
        socket_set_nonblock($this->socket);
        
        if ($blocking) {
            socket_set_block($this->socket);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function getLocalAddress(): ?array
    {
        if ($this->socket === null || !$this->bound) {
            return null;
        }
        
        $ip = '';
        $port = 0;
        
        if (socket_getsockname($this->socket, $ip, $port)) {
            return [$ip, $port];
        }
        
        return null;
    }
    
    /**
     * {@inheritdoc}
     */
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
     * @return self 当前实例，用于链式调用
     */
    public function setConfig(TransportConfig $config): self
    {
        $this->config = $config;
        return $this;
    }
    
    /**
     * 获取socket资源
     *
     * @return resource|null socket资源
     */
    public function getSocket()
    {
        return $this->socket;
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
            $this->logger->log(LogLevel::DEBUG, "[UdpTransport] $message");
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
            $this->logger->log(LogLevel::INFO, "[UdpTransport] $message");
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
            $this->logger->log(LogLevel::WARNING, "[UdpTransport] $message");
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
            $this->logger->log(LogLevel::ERROR, "[UdpTransport] $message");
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
