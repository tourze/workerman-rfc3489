<?php

namespace Tourze\Workerman\RFC3489\Transport;

use Tourze\Workerman\RFC3489\Exception\InvalidArgumentException;

/**
 * 传输配置类
 *
 * 用于配置STUN传输层参数
 */
class TransportConfig
{
    /**
     * 创建一个新的传输配置
     *
     * @param string $bindIp         本地绑定IP地址，默认为0.0.0.0（所有接口）
     * @param int    $bindPort       本地绑定端口，默认为0（随机端口）
     * @param bool   $blocking       是否阻塞模式，默认为true
     * @param int    $sendTimeout    发送超时（毫秒），默认为500
     * @param int    $receiveTimeout 接收超时（毫秒），默认为500
     * @param int    $retryCount     重试次数，默认为2
     * @param int    $retryInterval  重试间隔（毫秒），默认为100
     * @param int    $bufferSize     缓冲区大小，默认为8192
     */
    public function __construct(
        private string $bindIp = '0.0.0.0',
        private int $bindPort = 0,
        private bool $blocking = true,
        private int $sendTimeout = 500,
        private int $receiveTimeout = 500,
        private int $retryCount = 2,
        private int $retryInterval = 100,
        private int $bufferSize = 8192,
    ) {
    }

    /**
     * 获取本地绑定IP地址
     *
     * @return string 本地绑定IP地址
     */
    public function getBindIp(): string
    {
        return $this->bindIp;
    }

    /**
     * 设置本地绑定IP地址
     *
     * @param string $bindIp 本地绑定IP地址
     */
    public function setBindIp(string $bindIp): void
    {
        $this->bindIp = $bindIp;
    }

    /**
     * 获取本地绑定端口
     *
     * @return int 本地绑定端口
     */
    public function getBindPort(): int
    {
        return $this->bindPort;
    }

    /**
     * 设置本地绑定端口
     *
     * @param int $bindPort 本地绑定端口
     */
    public function setBindPort(int $bindPort): void
    {
        $this->bindPort = $bindPort;
    }

    /**
     * 是否阻塞模式
     *
     * @return bool 是否阻塞模式
     */
    public function isBlocking(): bool
    {
        return $this->blocking;
    }

    /**
     * 设置是否阻塞模式
     *
     * @param bool $blocking 是否阻塞模式
     */
    public function setBlocking(bool $blocking): void
    {
        $this->blocking = $blocking;
    }

    /**
     * 获取发送超时
     *
     * @return int 发送超时（毫秒）
     */
    public function getSendTimeout(): int
    {
        return $this->sendTimeout;
    }

    /**
     * 设置发送超时
     *
     * @param int $sendTimeout 发送超时（毫秒）
     *
     * @throws InvalidArgumentException 如果超时值无效
     */
    public function setSendTimeout(int $sendTimeout): void
    {
        if ($sendTimeout < 0) {
            throw new InvalidArgumentException('发送超时不能为负数');
        }

        $this->sendTimeout = $sendTimeout;
    }

    /**
     * 获取接收超时
     *
     * @return int 接收超时（毫秒）
     */
    public function getReceiveTimeout(): int
    {
        return $this->receiveTimeout;
    }

    /**
     * 设置接收超时
     *
     * @param int $receiveTimeout 接收超时（毫秒）
     *
     * @throws InvalidArgumentException 如果超时值无效
     */
    public function setReceiveTimeout(int $receiveTimeout): void
    {
        if ($receiveTimeout < 0) {
            throw new InvalidArgumentException('接收超时不能为负数');
        }

        $this->receiveTimeout = $receiveTimeout;
    }

    /**
     * 获取重试次数
     *
     * @return int 重试次数
     */
    public function getRetryCount(): int
    {
        return $this->retryCount;
    }

    /**
     * 设置重试次数
     *
     * @param int $retryCount 重试次数
     *
     * @throws InvalidArgumentException 如果重试次数无效
     */
    public function setRetryCount(int $retryCount): void
    {
        if ($retryCount < 0) {
            throw new InvalidArgumentException('重试次数不能为负数');
        }

        $this->retryCount = $retryCount;
    }

    /**
     * 获取重试间隔
     *
     * @return int 重试间隔（毫秒）
     */
    public function getRetryInterval(): int
    {
        return $this->retryInterval;
    }

    /**
     * 设置重试间隔
     *
     * @param int $retryInterval 重试间隔（毫秒）
     *
     * @throws InvalidArgumentException 如果重试间隔无效
     */
    public function setRetryInterval(int $retryInterval): void
    {
        if ($retryInterval < 0) {
            throw new InvalidArgumentException('重试间隔不能为负数');
        }

        $this->retryInterval = $retryInterval;
    }

    /**
     * 获取缓冲区大小
     *
     * @return int 缓冲区大小
     */
    public function getBufferSize(): int
    {
        return $this->bufferSize;
    }

    /**
     * 设置缓冲区大小
     *
     * @param int $bufferSize 缓冲区大小
     *
     * @throws InvalidArgumentException 如果缓冲区大小无效
     */
    public function setBufferSize(int $bufferSize): void
    {
        if ($bufferSize < 0) {
            throw new InvalidArgumentException('缓冲区大小不能为负数');
        }

        $this->bufferSize = $bufferSize;
    }

    /**
     * 创建默认配置
     *
     * @return self 默认配置实例
     */
    public static function createDefault(): self
    {
        return new self();
    }
}
