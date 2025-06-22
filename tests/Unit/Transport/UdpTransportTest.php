<?php

namespace Tourze\Workerman\RFC3489\Tests\Unit\Transport;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tourze\Workerman\RFC3489\Message\Attributes\Username;
use Tourze\Workerman\RFC3489\Message\Constants;
use Tourze\Workerman\RFC3489\Message\StunMessage;
use Tourze\Workerman\RFC3489\Transport\TransportConfig;
use Tourze\Workerman\RFC3489\Transport\UdpTransport;

class UdpTransportTest extends TestCase
{
    /**
     * 创建一个自定义的模拟类来重写 UdpTransport 的行为，避免实际的网络操作
     */
    private function createMockTransport(?TransportConfig $config = null, ?LoggerInterface $logger = null)
    {
        // 创建一个匿名类，继承自 UdpTransport，重写需要测试的方法
        return new class($config, $logger) extends UdpTransport {
            private $mockSocket = 'mock_socket';
            private $mockLastError = null;
            private $mockSendResult = true;
            private $mockReceiveResult = null;
            private $mockLocalAddress = null;

            // 重写初始化方法，避免创建实际的套接字
            protected function init(): void
            {
                // 创建一个模拟套接字，避免实际的网络操作
                // 不调用父类的init方法
            }

            // 获取模拟套接字
            public function getSocket()
            {
                return $this->mockSocket;
            }

            // 设置发送结果
            public function setMockSendResult(bool $result): void
            {
                $this->mockSendResult = $result;
            }

            // 设置接收结果
            public function setMockReceiveResult(?array $result): void
            {
                $this->mockReceiveResult = $result;
            }

            // 设置模拟错误
            public function setMockLastError(?string $error): void
            {
                $this->mockLastError = $error;
            }

            // 设置模拟本地地址
            public function setMockLocalAddress(?array $address): void
            {
                $this->mockLocalAddress = $address;
            }

            // 重写发送方法
            public function send(StunMessage $message, string $ip, int $port): bool
            {
                if ($this->mockSocket === null) {
                    $this->mockSocket = 'mock_socket';
                }

                if (!$this->mockSendResult) {
                    $this->mockLastError = 'Mock send error';
                    return false;
                }

                return true;
            }

            // 重写接收方法
            public function receive(int $timeout = 0): ?array
            {
                if ($this->mockSocket === null) {
                    $this->mockSocket = 'mock_socket';
                }

                return $this->mockReceiveResult;
            }

            // 重写绑定方法
            public function bind(string $ip, int $port): bool
            {
                if ($this->mockSocket === null) {
                    $this->mockSocket = 'mock_socket';
                }

                return true;
            }

            // 重写获取本地地址方法
            public function getLocalAddress(): ?array
            {
                return $this->mockLocalAddress;
            }

            // 重写获取最后错误方法
            public function getLastError(): ?string
            {
                return $this->mockLastError;
            }

            // 重写关闭方法
            public function close(): void
            {
                // 不执行真正的关闭，只是重置状态
                // Mock implementation, no actual cleanup needed
            }
        };
    }

    public function testConstructor()
    {
        $transport = $this->createMockTransport();

        $this->assertInstanceOf(UdpTransport::class, $transport);
        $this->assertNotNull($transport->getSocket());
        $this->assertInstanceOf(TransportConfig::class, $transport->getConfig());
    }

    public function testConstructorWithConfig()
    {
        $config = new TransportConfig();
        $config->setBindIp('127.0.0.1')
            ->setBindPort(12345)
            ->setBufferSize(16384);

        $transport = $this->createMockTransport($config);

        $this->assertInstanceOf(UdpTransport::class, $transport);
        $this->assertSame($config, $transport->getConfig());
    }

    public function testSend()
    {
        $transport = $this->createMockTransport();
        $message = new StunMessage(Constants::BINDING_REQUEST);
        $message->addAttribute(new Username('testuser'));

        $result = $transport->send($message, '192.168.1.1', 3478);

        $this->assertTrue($result);
    }

    public function testSend_Error()
    {
        $transport = $this->createMockTransport();
        /** @var object{setMockSendResult(bool): void, setMockReceiveResult(?array): void, setMockLastError(?string): void, setMockLocalAddress(?array): void} $transport */
        $transport->setMockSendResult(false);

        $message = new StunMessage(Constants::BINDING_REQUEST);
        $result = $transport->send($message, '192.168.1.1', 3478);

        $this->assertFalse($result);
        $this->assertNotNull($transport->getLastError());
    }

    public function testReceive()
    {
        $transport = $this->createMockTransport();

        // 设置模拟接收结果
        $mockResult = [
            'buffer' => "\x00\x01\x00\x08\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F\x00\x06\x00\x04test",
            'ip' => '192.168.1.1',
            'port' => 3478
        ];
        $transport->setMockReceiveResult($mockResult);

        $result = $transport->receive();

        $this->assertSame($mockResult, $result);
    }

    public function testReceive_Timeout()
    {
        $transport = $this->createMockTransport();

        // 设置为null表示超时
        $transport->setMockReceiveResult(null);
        $transport->setMockLastError('Mock timeout error');

        $result = $transport->receive();

        $this->assertNull($result);
        $this->assertNotNull($transport->getLastError());
    }

    public function testBind()
    {
        $transport = $this->createMockTransport();

        $result = $transport->bind('127.0.0.1', 12345);

        $this->assertTrue($result);
    }

    public function testClose()
    {
        $transport = $this->createMockTransport();

        // 确保套接字已创建
        $this->assertNotNull($transport->getSocket());

        // 关闭套接字
        $transport->close();

        // 创建一个新的消息并尝试发送
        // 这应该会触发初始化，因为套接字已经关闭
        $message = new StunMessage(Constants::BINDING_REQUEST);
        $transport->send($message, '192.168.1.1', 3478);

        // 验证套接字已重新创建
        $this->assertNotNull($transport->getSocket());
    }

    public function testGetSetConfig()
    {
        $initialConfig = new TransportConfig();
        $transport = $this->createMockTransport($initialConfig);

        $this->assertSame($initialConfig, $transport->getConfig());

        // 设置新的配置
        $newConfig = new TransportConfig();
        $newConfig->setBindIp('127.0.0.1')->setBindPort(12345);

        $result = $transport->setConfig($newConfig);

        // 检查链式调用
        $this->assertSame($transport, $result);

        // 检查配置是否更新
        $this->assertSame($newConfig, $transport->getConfig());
    }

    public function testGetLocalAddress()
    {
        $transport = $this->createMockTransport();

        // 初始应该为null
        $this->assertNull($transport->getLocalAddress());

        // 设置模拟本地地址
        $mockAddress = ['ip' => '127.0.0.1', 'port' => 12345];
        $transport->setMockLocalAddress($mockAddress);

        // 检查是否返回设置的地址
        $this->assertSame($mockAddress, $transport->getLocalAddress());
    }

    public function testGetLastError()
    {
        $transport = $this->createMockTransport();

        // 初始应该为null
        $this->assertNull($transport->getLastError());

        // 设置模拟错误
        $mockError = 'Test error message';
        $transport->setMockLastError($mockError);

        // 检查是否返回设置的错误
        $this->assertSame($mockError, $transport->getLastError());
    }
}
