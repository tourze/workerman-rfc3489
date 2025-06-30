<?php

namespace Tourze\Workerman\RFC3489\Tests\Unit\Transport;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Exception\InvalidArgumentException;
use Tourze\Workerman\RFC3489\Transport\TransportConfig;

class TransportConfigTest extends TestCase
{
    public function testCreateDefault()
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
    
    public function testGetSetBindIp()
    {
        $config = new TransportConfig();
        
        // 检查默认值
        $this->assertSame('0.0.0.0', $config->getBindIp());
        
        // 设置新值
        $newIp = '127.0.0.1';
        $config->setBindIp($newIp);
        
        // 检查是否正确设置
        $this->assertSame($newIp, $config->getBindIp());
        
        // 检查链式调用
        $result = $config->setBindIp('192.168.1.1');
        $this->assertSame($config, $result);
    }
    
    public function testGetSetBindPort()
    {
        $config = new TransportConfig();
        
        // 检查默认值
        $this->assertSame(0, $config->getBindPort());
        
        // 设置新值
        $newPort = 12345;
        $config->setBindPort($newPort);
        
        // 检查是否正确设置
        $this->assertSame($newPort, $config->getBindPort());
        
        // 检查链式调用
        $result = $config->setBindPort(8080);
        $this->assertSame($config, $result);
    }
    
    public function testGetSetBufferSize()
    {
        $config = new TransportConfig();
        
        // 检查默认值
        $this->assertSame(8192, $config->getBufferSize());
        
        // 设置新值
        $newSize = 16384;
        $config->setBufferSize($newSize);
        
        // 检查是否正确设置
        $this->assertSame($newSize, $config->getBufferSize());
        
        // 检查链式调用
        $result = $config->setBufferSize(4096);
        $this->assertSame($config, $result);
    }
    
    public function testSetBufferSize_InvalidValue()
    {
        $config = new TransportConfig();
        
        $this->expectException(InvalidArgumentException::class);
        $config->setBufferSize(-1); // 负值应该抛出异常
    }
    
    public function testGetSetSendTimeout()
    {
        $config = new TransportConfig();
        
        // 检查默认值
        $this->assertSame(500, $config->getSendTimeout());
        
        // 设置新值
        $newTimeout = 1000;
        $config->setSendTimeout($newTimeout);
        
        // 检查是否正确设置
        $this->assertSame($newTimeout, $config->getSendTimeout());
        
        // 检查链式调用
        $result = $config->setSendTimeout(2000);
        $this->assertSame($config, $result);
    }
    
    public function testSetSendTimeout_InvalidValue()
    {
        $config = new TransportConfig();
        
        $this->expectException(InvalidArgumentException::class);
        $config->setSendTimeout(-1); // 负值应该抛出异常
    }
    
    public function testGetSetReceiveTimeout()
    {
        $config = new TransportConfig();
        
        // 检查默认值
        $this->assertSame(500, $config->getReceiveTimeout());
        
        // 设置新值
        $newTimeout = 1000;
        $config->setReceiveTimeout($newTimeout);
        
        // 检查是否正确设置
        $this->assertSame($newTimeout, $config->getReceiveTimeout());
        
        // 检查链式调用
        $result = $config->setReceiveTimeout(2000);
        $this->assertSame($config, $result);
    }
    
    public function testSetReceiveTimeout_InvalidValue()
    {
        $config = new TransportConfig();
        
        $this->expectException(InvalidArgumentException::class);
        $config->setReceiveTimeout(-1); // 负值应该抛出异常
    }
    
    public function testGetSetRetryCount()
    {
        $config = new TransportConfig();
        
        // 检查默认值
        $this->assertSame(2, $config->getRetryCount());
        
        // 设置新值
        $newCount = 5;
        $config->setRetryCount($newCount);
        
        // 检查是否正确设置
        $this->assertSame($newCount, $config->getRetryCount());
        
        // 检查链式调用
        $result = $config->setRetryCount(3);
        $this->assertSame($config, $result);
    }
    
    public function testSetRetryCount_InvalidValue()
    {
        $config = new TransportConfig();
        
        $this->expectException(InvalidArgumentException::class);
        $config->setRetryCount(-1); // 负值应该抛出异常
    }
    
    public function testGetSetRetryInterval()
    {
        $config = new TransportConfig();
        
        // 检查默认值
        $this->assertSame(100, $config->getRetryInterval());
        
        // 设置新值
        $newInterval = 200;
        $config->setRetryInterval($newInterval);
        
        // 检查是否正确设置
        $this->assertSame($newInterval, $config->getRetryInterval());
        
        // 检查链式调用
        $result = $config->setRetryInterval(300);
        $this->assertSame($config, $result);
    }
    
    public function testSetRetryInterval_InvalidValue()
    {
        $config = new TransportConfig();
        
        $this->expectException(InvalidArgumentException::class);
        $config->setRetryInterval(-1); // 负值应该抛出异常
    }
    
    public function testGetSetBlocking()
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
        
        // 检查链式调用
        $result = $config->setBlocking(false);
        $this->assertSame($config, $result);
    }
} 