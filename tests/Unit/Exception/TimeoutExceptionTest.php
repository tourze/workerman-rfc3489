<?php

namespace Tourze\Workerman\RFC3489\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Exception\TimeoutException;
use Tourze\Workerman\RFC3489\Exception\StunException;

/**
 * TimeoutException 测试类
 */
class TimeoutExceptionTest extends TestCase
{
    public function testInheritance()
    {
        $exception = new TimeoutException();
        
        $this->assertInstanceOf(StunException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }
    
    public function testDefaultMessage()
    {
        $exception = new TimeoutException();
        
        $this->assertSame('操作超时', $exception->getMessage());
    }
    
    public function testCustomMessage()
    {
        $message = 'Custom timeout message';
        $exception = new TimeoutException($message);
        
        $this->assertSame($message, $exception->getMessage());
    }
    
    public function testTimeout()
    {
        $timeout = 5000;
        $exception = new TimeoutException('test', $timeout);
        
        $this->assertSame($timeout, $exception->getTimeout());
    }
    
    public function testDefaultTimeout()
    {
        $exception = new TimeoutException();
        
        $this->assertSame(0, $exception->getTimeout());
    }
    
    public function testReceiveTimeout()
    {
        $timeout = 3000;
        $exception = TimeoutException::receiveTimeout($timeout);
        
        $this->assertSame("接收消息超时（{$timeout}毫秒）", $exception->getMessage());
        $this->assertSame($timeout, $exception->getTimeout());
        $this->assertSame(2001, $exception->getCode());
    }
    
    public function testSendTimeout()
    {
        $timeout = 2000;
        $exception = TimeoutException::sendTimeout($timeout);
        
        $this->assertSame("发送消息超时（{$timeout}毫秒）", $exception->getMessage());
        $this->assertSame($timeout, $exception->getTimeout());
        $this->assertSame(2002, $exception->getCode());
    }
    
    public function testTransactionTimeout()
    {
        $transactionId = 'test123';
        $timeout = 4000;
        $exception = TimeoutException::transactionTimeout($transactionId, $timeout);
        
        $hexId = bin2hex($transactionId);
        $this->assertSame("事务 $hexId 超时（{$timeout}毫秒）", $exception->getMessage());
        $this->assertSame($timeout, $exception->getTimeout());
        $this->assertSame(2003, $exception->getCode());
    }
}