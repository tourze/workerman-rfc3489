<?php

namespace Tourze\Workerman\RFC3489\Tests\Transport;

use Tourze\Workerman\RFC3489\Message\StunMessage;
use Tourze\Workerman\RFC3489\Transport\StunTransport;
use Tourze\Workerman\RFC3489\Transport\TransportConfig;

/**
 * 用于测试的模拟 UdpTransport 类
 */
class MockUdpTransport implements StunTransport
{
    private mixed $mockSocket = 'mock_socket';

    private ?string $mockLastError = null;

    private bool $mockSendResult = true;

    /**
     * @var array{0: StunMessage, 1: string, 2: int}|null
     */
    private ?array $mockReceiveResult = null;

    /**
     * @var array{0: string, 1: int}|null
     */
    private ?array $mockLocalAddress = null;

    private TransportConfig $config;

    public function __construct(?TransportConfig $config = null)
    {
        $this->config = $config ?? new TransportConfig();
    }

    public function getSocket(): mixed
    {
        return $this->mockSocket;
    }

    public function setMockSendResult(bool $result): void
    {
        $this->mockSendResult = $result;
    }

    /**
     * @param array{0: StunMessage, 1: string, 2: int}|null $result
     */
    public function setMockReceiveResult(?array $result): void
    {
        $this->mockReceiveResult = $result;
    }

    public function setMockLastError(?string $error): void
    {
        $this->mockLastError = $error;
    }

    /**
     * @param array{0: string, 1: int}|null $address
     */
    public function setMockLocalAddress(?array $address): void
    {
        $this->mockLocalAddress = $address;
    }

    public function send(StunMessage $message, string $ip, int $port): bool
    {
        if (null === $this->mockSocket) {
            $this->mockSocket = 'mock_socket';
        }

        if (!$this->mockSendResult) {
            $this->mockLastError = 'Mock send error';

            return false;
        }

        return true;
    }

    /**
     * @return array{0: StunMessage, 1: string, 2: int}|null
     */
    public function receive(int $timeout = 0): ?array
    {
        if (null === $this->mockSocket) {
            $this->mockSocket = 'mock_socket';
        }

        return $this->mockReceiveResult;
    }

    public function bind(string $ip, int $port): bool
    {
        if (null === $this->mockSocket) {
            $this->mockSocket = 'mock_socket';
        }

        return true;
    }

    /**
     * @return array{0: string, 1: int}|null
     */
    public function getLocalAddress(): ?array
    {
        return $this->mockLocalAddress;
    }

    public function getLastError(): ?string
    {
        return $this->mockLastError;
    }

    public function close(): void
    {
        // Mock implementation, no actual cleanup needed
    }

    public function setBlocking(bool $blocking): void
    {
        // Mock implementation, no actual socket blocking setting needed
    }

    public function getConfig(): TransportConfig
    {
        return $this->config;
    }

    public function setConfig(TransportConfig $config): void
    {
        $this->config = $config;
    }
}
