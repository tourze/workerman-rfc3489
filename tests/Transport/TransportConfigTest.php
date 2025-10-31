<?php

namespace Tourze\Workerman\RFC3489\Tests\Transport;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Exception\InvalidArgumentException;
use Tourze\Workerman\RFC3489\Transport\TransportConfig;

/**
 * @internal
 */
#[CoversClass(TransportConfig::class)]
final class TransportConfigTest extends TestCase
{
    public function testCreateDefault(): void
    {
        $config = TransportConfig::createDefault();

        // 检查默认配置值
        $this->assertInstanceOf(TransportConfig::class, $config);
        $this->assertSame('0.0.0.0', $config->getBindIp());
        $this->assertSame(0, $config->getBindPort());
        $this->assertSame(8192, $config->getBufferSize());
        $this->assertSame(500, $config->getSendTimeout());
        $this->assertSame(500, $config->getReceiveTimeout());
        $this->assertSame(2, $config->getRetryCount());
        $this->assertSame(100, $config->getRetryInterval());
        $this->assertTrue($config->isBlocking());
    }

    public function testGetSetBindIp(): void
    {
        $config = new TransportConfig();

        // 检查默认值
        $this->assertSame('0.0.0.0', $config->getBindIp());

        // 设置新值
        $newIp = '127.0.0.1';
        $config->setBindIp($newIp);

        // 检查是否正确设置
        $this->assertSame($newIp, $config->getBindIp());

        // setter方法现在返回void，无法链式调用
        $config->setBindIp('192.168.1.1');
        $this->assertSame('192.168.1.1', $config->getBindIp());
    }

    public function testGetSetBindPort(): void
    {
        $config = new TransportConfig();

        // 检查默认值
        $this->assertSame(0, $config->getBindPort());

        // 设置新值
        $newPort = 12345;
        $config->setBindPort($newPort);

        // 检查是否正确设置
        $this->assertSame($newPort, $config->getBindPort());

        // setter方法现在返回void，无法链式调用
        $config->setBindPort(8080);
        $this->assertSame(8080, $config->getBindPort());
    }

    public function testGetSetBufferSize(): void
    {
        $config = new TransportConfig();

        // 检查默认值
        $this->assertSame(8192, $config->getBufferSize());

        // 设置新值
        $newSize = 16384;
        $config->setBufferSize($newSize);

        // 检查是否正确设置
        $this->assertSame($newSize, $config->getBufferSize());

        // setter方法现在返回void，无法链式调用
        $config->setBufferSize(4096);
        $this->assertSame(4096, $config->getBufferSize());
    }

    public function testSetBufferSizeInvalidValue(): void
    {
        $config = new TransportConfig();

        $this->expectException(InvalidArgumentException::class);
        $config->setBufferSize(-1); // 负值应该抛出异常
    }

    public function testGetSetSendTimeout(): void
    {
        $config = new TransportConfig();

        // 检查默认值
        $this->assertSame(500, $config->getSendTimeout());

        // 设置新值
        $newTimeout = 1000;
        $config->setSendTimeout($newTimeout);

        // 检查是否正确设置
        $this->assertSame($newTimeout, $config->getSendTimeout());

        // setter方法现在返回void，无法链式调用
        $config->setSendTimeout(2000);
        $this->assertSame(2000, $config->getSendTimeout());
    }

    public function testSetSendTimeoutInvalidValue(): void
    {
        $config = new TransportConfig();

        $this->expectException(InvalidArgumentException::class);
        $config->setSendTimeout(-1); // 负值应该抛出异常
    }

    public function testGetSetReceiveTimeout(): void
    {
        $config = new TransportConfig();

        // 检查默认值
        $this->assertSame(500, $config->getReceiveTimeout());

        // 设置新值
        $newTimeout = 1000;
        $config->setReceiveTimeout($newTimeout);

        // 检查是否正确设置
        $this->assertSame($newTimeout, $config->getReceiveTimeout());

        // setter方法现在返回void，无法链式调用
        $config->setReceiveTimeout(2000);
        $this->assertSame(2000, $config->getReceiveTimeout());
    }

    public function testSetReceiveTimeoutInvalidValue(): void
    {
        $config = new TransportConfig();

        $this->expectException(InvalidArgumentException::class);
        $config->setReceiveTimeout(-1); // 负值应该抛出异常
    }

    public function testGetSetRetryCount(): void
    {
        $config = new TransportConfig();

        // 检查默认值
        $this->assertSame(2, $config->getRetryCount());

        // 设置新值
        $newCount = 5;
        $config->setRetryCount($newCount);

        // 检查是否正确设置
        $this->assertSame($newCount, $config->getRetryCount());

        // setter方法现在返回void，无法链式调用
        $config->setRetryCount(3);
        $this->assertSame(3, $config->getRetryCount());
    }

    public function testSetRetryCountInvalidValue(): void
    {
        $config = new TransportConfig();

        $this->expectException(InvalidArgumentException::class);
        $config->setRetryCount(-1); // 负值应该抛出异常
    }

    public function testGetSetRetryInterval(): void
    {
        $config = new TransportConfig();

        // 检查默认值
        $this->assertSame(100, $config->getRetryInterval());

        // 设置新值
        $newInterval = 200;
        $config->setRetryInterval($newInterval);

        // 检查是否正确设置
        $this->assertSame($newInterval, $config->getRetryInterval());

        // setter方法现在返回void，无法链式调用
        $config->setRetryInterval(300);
        $this->assertSame(300, $config->getRetryInterval());
    }

    public function testSetRetryIntervalInvalidValue(): void
    {
        $config = new TransportConfig();

        $this->expectException(InvalidArgumentException::class);
        $config->setRetryInterval(-1); // 负值应该抛出异常
    }

    public function testGetSetBlocking(): void
    {
        $config = new TransportConfig();

        // 检查默认值
        $this->assertTrue($config->isBlocking());

        // 设置为false
        $config->setBlocking(false);
        $this->assertFalse($config->isBlocking());

        // 设置回true
        $config->setBlocking(true);
        $this->assertTrue($config->isBlocking());

        // setter方法现在返回void，无法链式调用
        $config->setBlocking(false);
        $this->assertFalse($config->isBlocking());
    }
}
