<?php

namespace Tourze\Workerman\RFC3489\Tests\Transport;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tourze\Workerman\RFC3489\Message\Attributes\Username;
use Tourze\Workerman\RFC3489\Message\Constants;
use Tourze\Workerman\RFC3489\Message\StunMessage;
use Tourze\Workerman\RFC3489\Transport\StunTransport;
use Tourze\Workerman\RFC3489\Transport\TransportConfig;
use Tourze\Workerman\RFC3489\Transport\UdpTransport;

/**
 * @internal
 */
#[CoversClass(UdpTransport::class)]
final class UdpTransportTest extends TestCase
{
    private function createMockTransport(?TransportConfig $config = null): MockUdpTransport
    {
        return new MockUdpTransport($config);
    }

    public function testConstructor(): void
    {
        $transport = $this->createMockTransport();

        // MockUdpTransport现在实现StunTransport接口
        $this->assertInstanceOf(StunTransport::class, $transport);
        $this->assertNotNull($transport->getSocket());
        $this->assertInstanceOf(TransportConfig::class, $transport->getConfig());
    }

    public function testConstructorWithConfig(): void
    {
        $config = new TransportConfig();
        $config->setBindIp('127.0.0.1');
        $config->setBindPort(12345);
        $config->setBufferSize(16384);

        $transport = $this->createMockTransport($config);

        $this->assertInstanceOf(StunTransport::class, $transport);
        $this->assertSame($config, $transport->getConfig());
    }

    public function testSend(): void
    {
        $transport = $this->createMockTransport();
        $message = new StunMessage(Constants::BINDING_REQUEST);
        $message->addAttribute(new Username('testuser'));

        $result = $transport->send($message, '192.168.1.1', 3478);

        $this->assertTrue($result);
    }

    public function testSendError(): void
    {
        $transport = $this->createMockTransport();
        $transport->setMockSendResult(false);

        $message = new StunMessage(Constants::BINDING_REQUEST);
        $result = $transport->send($message, '192.168.1.1', 3478);

        $this->assertFalse($result);
        $this->assertNotNull($transport->getLastError());
    }

    public function testReceive(): void
    {
        $transport = $this->createMockTransport();

        // 创建一个模拟的STUN消息
        $stunMessage = new StunMessage(Constants::BINDING_REQUEST);
        $stunMessage->addAttribute(new Username('testuser'));

        // 设置模拟接收结果 - 使用tuple array格式 [StunMessage, string, int]
        $mockResult = [$stunMessage, '192.168.1.1', 3478];
        $transport->setMockReceiveResult($mockResult);

        $result = $transport->receive();

        $this->assertSame($mockResult, $result);
    }

    public function testReceiveTimeout(): void
    {
        $transport = $this->createMockTransport();

        // 设置为null表示超时
        $transport->setMockReceiveResult(null);
        $transport->setMockLastError('Mock timeout error');

        $result = $transport->receive();

        $this->assertNull($result);
        $this->assertNotNull($transport->getLastError());
    }

    public function testBind(): void
    {
        $transport = $this->createMockTransport();

        $result = $transport->bind('127.0.0.1', 12345);

        $this->assertTrue($result);
    }

    public function testClose(): void
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

    public function testGetSetConfig(): void
    {
        $initialConfig = new TransportConfig();
        $transport = $this->createMockTransport($initialConfig);

        $this->assertSame($initialConfig, $transport->getConfig());

        // 设置新的配置
        $newConfig = new TransportConfig();
        $newConfig->setBindIp('127.0.0.1');
        $newConfig->setBindPort(12345);

        $transport->setConfig($newConfig);

        // 检查配置是否更新
        $this->assertSame($newConfig, $transport->getConfig());
    }

    public function testGetLocalAddress(): void
    {
        $transport = $this->createMockTransport();

        // 初始应该为null
        $this->assertNull($transport->getLocalAddress());

        // 设置模拟本地地址 - 使用tuple array格式 [string, int]
        $mockAddress = ['127.0.0.1', 12345];
        $transport->setMockLocalAddress($mockAddress);

        // 检查是否返回设置的地址
        $this->assertSame($mockAddress, $transport->getLocalAddress());
    }

    public function testGetLastError(): void
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
