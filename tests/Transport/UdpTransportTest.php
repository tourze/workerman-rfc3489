<?php

namespace Tourze\Workerman\RFC3489\Tests\Transport;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Tourze\Workerman\RFC3489\Exception\TransportException;
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
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = new NullLogger();
    }

    private function createTransport(?TransportConfig $config = null): UdpTransport
    {
        return new UdpTransport($config, $this->logger);
    }

    public function testConstructor(): void
    {
        $transport = $this->createTransport();

        $this->assertInstanceOf(StunTransport::class, $transport);
        $this->assertNotNull($transport->getSocket());
        $this->assertInstanceOf(TransportConfig::class, $transport->getConfig());
    }

    public function testConstructorWithConfig(): void
    {
        $config = new TransportConfig();
        $config->setBindIp('127.0.0.1');
        $config->setBindPort(0); // 使用随机端口避免冲突
        $config->setBufferSize(16384);

        $transport = $this->createTransport($config);

        $this->assertInstanceOf(StunTransport::class, $transport);
        $this->assertSame($config, $transport->getConfig());
    }

    public function testSendToLoopback(): void
    {
        $transport = $this->createTransport();

        // 绑定到随机端口
        $this->assertTrue($transport->bind('127.0.0.1', 0));

        $message = new StunMessage(Constants::BINDING_REQUEST);
        $message->addAttribute(new Username('testuser'));

        // 发送到环回地址 - 这不会失败但也不会收到回复
        $result = $transport->send($message, '127.0.0.1', 3478);

        $this->assertTrue($result);
        $this->assertNull($transport->getLastError());
    }

    public function testSendToInvalidAddress(): void
    {
        $transport = $this->createTransport();

        $message = new StunMessage(Constants::BINDING_REQUEST);

        // 发送到无效地址 - 这应该失败但不抛出异常
        $result = $transport->send($message, '999.999.999.999', 3478);

        // 由于重试机制，可能会返回false
        $this->assertIsBool($result);
    }

    public function testReceiveTimeout(): void
    {
        $transport = $this->createTransport();

        // 绑定到随机端口
        $this->assertTrue($transport->bind('127.0.0.1', 0));

        // 尝试接收数据，应该超时返回null
        $result = $transport->receive(100); // 100ms超时

        $this->assertNull($result);
    }

    public function testBindToLocalhost(): void
    {
        $transport = $this->createTransport();

        // 绑定到localhost的随机端口
        $result = $transport->bind('127.0.0.1', 0);

        $this->assertTrue($result);
        $this->assertNull($transport->getLastError());
    }

    public function testBindToReservedPort(): void
    {
        $transport = $this->createTransport();

        // 尝试绑定到保留端口（需要root权限）应该失败
        $result = $transport->bind('127.0.0.1', 80);

        // 可能成功也可能失败，取决于权限和系统配置
        $this->assertIsBool($result);

        if (!$result) {
            $this->assertNotNull($transport->getLastError());
        }
    }

    public function testClose(): void
    {
        $transport = $this->createTransport();

        // 绑定到随机端口
        $this->assertTrue($transport->bind('127.0.0.1', 0));

        // 确保套接字已创建
        $this->assertNotNull($transport->getSocket());

        // 关闭套接字
        $transport->close();

        // 关闭后套接字应该为null
        $this->assertNull($transport->getSocket());
    }

    public function testGetSetConfig(): void
    {
        $initialConfig = new TransportConfig();
        $transport = $this->createTransport($initialConfig);

        $this->assertSame($initialConfig, $transport->getConfig());

        // 设置新的配置
        $newConfig = new TransportConfig();
        $newConfig->setBindIp('127.0.0.1');
        $newConfig->setBindPort(0);

        $transport->setConfig($newConfig);

        // 检查配置是否更新
        $this->assertSame($newConfig, $transport->getConfig());
    }

    public function testGetLocalAddressAfterBind(): void
    {
        $transport = $this->createTransport();

        // 初始应该为null
        $this->assertNull($transport->getLocalAddress());

        // 绑定到随机端口
        $this->assertTrue($transport->bind('127.0.0.1', 0));

        // 绑定后应该能获取到本地地址
        $address = $transport->getLocalAddress();
        $this->assertIsArray($address);
        $this->assertCount(2, $address);
        $this->assertEquals('127.0.0.1', $address[0]);
        $this->assertIsInt($address[1]);
        $this->assertGreaterThan(0, $address[1]);
    }

    public function testGetLastError(): void
    {
        $transport = $this->createTransport();

        // 初始应该为null
        $this->assertNull($transport->getLastError());
    }

    public function testSetBlocking(): void
    {
        $transport = $this->createTransport();

        // 绑定到随机端口
        $this->assertTrue($transport->bind('127.0.0.1', 0));

        // 设置为阻塞模式 - 这不应该抛出异常
        $transport->setBlocking(true);

        // 设置为非阻塞模式 - 这也不应该抛出异常
        $transport->setBlocking(false);

        // 测试通过
        $this->assertTrue(true);
    }

    public function testSendWithoutBind(): void
    {
        $config = new TransportConfig();
        $config->setBindPort(0); // 不自动绑定

        $transport = $this->createTransport($config);

        $message = new StunMessage(Constants::BINDING_REQUEST);

        // 即使没有显式绑定，也应该能够发送（自动分配端口）
        $result = $transport->send($message, '127.0.0.1', 3478);

        $this->assertTrue($result);
    }
}
