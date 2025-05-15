<?php

namespace Tourze\Workerman\RFC3489\Tests\Unit\Protocol;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Exception\StunException;
use Tourze\Workerman\RFC3489\Exception\TimeoutException;
use Tourze\Workerman\RFC3489\Exception\TransportException;
use Tourze\Workerman\RFC3489\Message\Attributes\MappedAddress;
use Tourze\Workerman\RFC3489\Message\StunMessage;
use Tourze\Workerman\RFC3489\Protocol\StunClient;
use Tourze\Workerman\RFC3489\Transport\StunTransport;

class StunClientTest extends TestCase
{
    /**
     * 测试正常情况下成功获取公网地址
     */
    public function testDiscoverPublicAddress_Success()
    {
        // 创建模拟的传输层
        $transport = $this->createMock(StunTransport::class);
        
        // 设置一个固定的事务ID
        $transactionId = str_repeat("\xAB", 16);
        
        // 模拟发送方法，捕获请求并保存事务ID
        $transport->expects($this->once())
            ->method('send')
            ->willReturnCallback(function (StunMessage $message) use (&$transactionId) {
                // 保存请求的事务ID
                $transactionId = $message->getTransactionId();
                return true;
            });
            
        // 创建一个响应消息，包含映射地址
        $mappedAddress = new MappedAddress('203.0.113.5', 12345);
        $response = new StunMessage(0x0101); // Binding Response
        $response->addAttribute($mappedAddress);
        
        // 模拟接收方法，返回响应
        $transport->expects($this->once())
            ->method('receive')
            ->willReturnCallback(function () use (&$transactionId, $response) {
                // 使用保存的事务ID
                $response->setTransactionId($transactionId);
                return [$response, '192.0.2.10', 3478];
            });
            
        // 创建客户端，注入模拟的传输层
        $client = new StunClient('192.0.2.10', 3478, $transport);
        
        // 执行方法
        $result = $client->discoverPublicAddress();
        
        // 验证结果
        $this->assertEquals(['203.0.113.5', 12345], $result);
    }
    
    /**
     * 测试发送请求失败的情况
     */
    public function testDiscoverPublicAddress_SendFailed()
    {
        // 创建模拟的传输层
        $transport = $this->createMock(StunTransport::class);
        
        // 设置模拟行为 - 发送失败
        $transport->expects($this->once())
            ->method('send')
            ->willReturn(false);
            
        $transport->expects($this->once())
            ->method('getLastError')
            ->willReturn('网络错误');
            
        // 创建客户端，注入模拟的传输层
        $client = new StunClient('192.0.2.10', 3478, $transport);
        
        // 预期异常
        $this->expectException(TransportException::class);
        
        // 执行方法
        $client->discoverPublicAddress();
    }
    
    /**
     * 测试接收响应超时的情况
     */
    public function testDiscoverPublicAddress_ReceiveTimeout()
    {
        // 创建模拟的传输层
        $transport = $this->createMock(StunTransport::class);
        
        // 设置模拟行为 - 发送成功但接收超时
        $transport->expects($this->once())
            ->method('send')
            ->willReturn(true);
            
        $transport->expects($this->once())
            ->method('receive')
            ->willReturn(null);
            
        $transport->expects($this->once())
            ->method('getLastError')
            ->willReturn(null);
            
        // 创建客户端，注入模拟的传输层
        $client = new StunClient('192.0.2.10', 3478, $transport);
        
        // 预期异常
        $this->expectException(TimeoutException::class);
        
        // 执行方法
        $client->discoverPublicAddress();
    }
    
    /**
     * 测试接收响应但事务ID不匹配的情况
     */
    public function testDiscoverPublicAddress_TransactionIdMismatch()
    {
        // 创建模拟的传输层
        $transport = $this->createMock(StunTransport::class);
        
        // 设置模拟行为 - 发送成功
        $transport->expects($this->once())
            ->method('send')
            ->willReturn(true);
            
        // 创建一个响应消息，但使用不同的事务ID
        $response = new StunMessage(0x0101); // Binding Response
        $response->setTransactionId(str_repeat("\xCD", 16)); // 不同的事务ID
        
        $transport->expects($this->once())
            ->method('receive')
            ->willReturn([$response, '192.0.2.10', 3478]);
            
        // 创建客户端，注入模拟的传输层
        $client = new StunClient('192.0.2.10', 3478, $transport);
        
        // 预期异常
        $this->expectException(StunException::class);
        $this->expectExceptionMessage('接收到的响应事务ID与请求不匹配');
        
        // 执行方法
        $client->discoverPublicAddress();
    }
    
    /**
     * 测试响应中没有映射地址信息的情况
     */
    public function testDiscoverPublicAddress_NoMappedAddress()
    {
        // 创建模拟的传输层
        $transport = $this->createMock(StunTransport::class);
        
        // 设置一个固定的事务ID
        $transactionId = str_repeat("\xAB", 16);
        
        // 模拟发送方法，捕获请求并保存事务ID
        $transport->expects($this->once())
            ->method('send')
            ->willReturnCallback(function (StunMessage $message) use (&$transactionId) {
                // 保存请求的事务ID
                $transactionId = $message->getTransactionId();
                return true;
            });
            
        // 创建一个没有映射地址的响应消息
        $response = new StunMessage(0x0101); // Binding Response
        
        // 模拟接收方法，返回响应
        $transport->expects($this->once())
            ->method('receive')
            ->willReturnCallback(function () use (&$transactionId, $response) {
                // 使用保存的事务ID
                $response->setTransactionId($transactionId);
                return [$response, '192.0.2.10', 3478];
            });
            
        // 创建客户端，注入模拟的传输层
        $client = new StunClient('192.0.2.10', 3478, $transport);
        
        // 预期异常
        $this->expectException(StunException::class);
        $this->expectExceptionMessage('响应中没有映射地址信息');
        
        // 执行方法
        $client->discoverPublicAddress();
    }
    
    /**
     * 测试服务器地址解析 - IP地址
     */
    public function testResolveServerAddress_IpAddress()
    {
        // 创建模拟的传输层
        $transport = $this->createMock(StunTransport::class);
        
        // 创建客户端，使用IP地址作为服务器地址
        $client = new StunClient('192.0.2.10', 3478, $transport);
        
        // 使用反射调用私有方法
        $method = new \ReflectionMethod(StunClient::class, 'resolveServerAddress');
        $method->setAccessible(true);
        
        // 执行方法
        $result = $method->invoke($client);
        
        // 验证结果
        $this->assertEquals('192.0.2.10', $result);
    }
    
    /**
     * 测试关闭客户端
     */
    public function testClose()
    {
        // 创建模拟的传输层
        $transport = $this->createMock(StunTransport::class);
        
        // 设置模拟行为
        $transport->expects($this->once())
            ->method('close');
            
        // 创建客户端，注入模拟的传输层
        $client = new StunClient('stun.example.com', 3478, $transport);
        
        // 执行方法
        $client->close();
    }
    
    /**
     * 测试获取传输层实例
     */
    public function testGetTransport()
    {
        // 创建模拟的传输层
        $transport = $this->createMock(StunTransport::class);
        
        // 创建客户端，注入模拟的传输层
        $client = new StunClient('stun.example.com', 3478, $transport);
        
        // 验证获取的传输层是注入的那个
        $this->assertSame($transport, $client->getTransport());
    }
    
    /**
     * 测试设置请求超时时间
     */
    public function testSetRequestTimeout()
    {
        // 创建模拟的传输层
        $transport = $this->createMock(StunTransport::class);
        
        // 创建客户端，注入模拟的传输层
        $client = new StunClient('stun.example.com', 3478, $transport);
        
        // 执行方法
        $result = $client->setRequestTimeout(10000);
        
        // 验证链式调用返回自身
        $this->assertSame($client, $result);
        
        // 使用反射验证属性值已更新
        $reflectionClass = new \ReflectionClass(StunClient::class);
        $reflectionProperty = $reflectionClass->getProperty('requestTimeout');
        $reflectionProperty->setAccessible(true);
        
        $this->assertEquals(10000, $reflectionProperty->getValue($client));
    }
}
