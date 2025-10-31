<?php

namespace Tourze\Workerman\RFC3489\Transport;

use Tourze\Workerman\RFC3489\Message\StunMessage;

/**
 * STUN传输接口
 *
 * 定义STUN消息传输的基本接口
 */
interface StunTransport
{
    /**
     * 发送STUN消息
     *
     * @param StunMessage $message 要发送的消息
     * @param string      $ip      目标IP地址
     * @param int         $port    目标端口
     *
     * @return bool 是否发送成功
     */
    public function send(StunMessage $message, string $ip, int $port): bool;

    /**
     * 接收STUN消息
     *
     * @param int $timeout 接收超时时间（毫秒），0表示不超时
     *
     * @return array{0: StunMessage, 1: string, 2: int}|null 接收到的消息和源地址，格式为 [StunMessage, string $ip, int $port] 或 null
     */
    public function receive(int $timeout = 0): ?array;

    /**
     * 绑定到指定地址和端口
     *
     * @param string $ip   绑定的IP地址
     * @param int    $port 绑定的端口
     *
     * @return bool 是否绑定成功
     */
    public function bind(string $ip, int $port): bool;

    /**
     * 关闭传输
     */
    public function close(): void;

    /**
     * 设置是否阻塞模式
     *
     * @param bool $blocking 是否阻塞
     */
    public function setBlocking(bool $blocking): void;

    /**
     * 获取本地绑定地址
     *
     * @return array{0: string, 1: int}|null 本地地址，格式为 [string $ip, int $port] 或 null
     */
    public function getLocalAddress(): ?array;

    /**
     * 获取上次错误
     *
     * @return string|null 错误信息或null
     */
    public function getLastError(): ?string;
}
