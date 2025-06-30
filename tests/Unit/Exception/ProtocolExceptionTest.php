<?php

namespace Tourze\Workerman\RFC3489\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Exception\ProtocolException;
use Tourze\Workerman\RFC3489\Exception\StunException;

/**
 * ProtocolException 测试类
 */
class ProtocolExceptionTest extends TestCase
{
    public function testInheritance()
    {
        $exception = new ProtocolException();
        
        $this->assertInstanceOf(StunException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }
    
    public function testDefaultMessage()
    {
        $exception = new ProtocolException();
        
        $this->assertSame('协议逻辑错误', $exception->getMessage());
    }
    
    public function testCustomMessage()
    {
        $message = 'Custom protocol error';
        $exception = new ProtocolException($message);
        
        $this->assertSame($message, $exception->getMessage());
    }
    
    public function testInvalidState()
    {
        $state = 'INVALID';
        $expected = 'VALID';
        $exception = ProtocolException::invalidState($state, $expected);
        
        $this->assertSame("无效的协议状态: $state, 期望: $expected", $exception->getMessage());
        $this->assertSame(3001, $exception->getCode());
    }
    
    public function testInvalidTransaction()
    {
        $transactionId = 'test123';
        $exception = ProtocolException::invalidTransaction($transactionId);
        
        $hexId = bin2hex($transactionId);
        $this->assertSame("无效的事务ID: $hexId", $exception->getMessage());
        $this->assertSame(3002, $exception->getCode());
    }
    
    public function testHandlerNotRegistered()
    {
        $method = 'BINDING';
        $exception = ProtocolException::handlerNotRegistered($method);
        
        $this->assertSame("未注册处理器: $method", $exception->getMessage());
        $this->assertSame(3003, $exception->getCode());
    }
    
    public function testCannotHandleMessage()
    {
        $method = 'BINDING';
        $reason = 'invalid format';
        $exception = ProtocolException::cannotHandleMessage($method, $reason);
        
        $this->assertSame("无法处理消息: $method - $reason", $exception->getMessage());
        $this->assertSame(3004, $exception->getCode());
    }
    
    public function testMessageHandlingFailed()
    {
        $method = 'BINDING';
        $error = 'timeout';
        $exception = ProtocolException::messageHandlingFailed($method, $error);
        
        $this->assertSame("消息处理失败: $method - $error", $exception->getMessage());
        $this->assertSame(3005, $exception->getCode());
    }
    
    public function testAuthenticationFailed()
    {
        $reason = 'invalid credentials';
        $exception = ProtocolException::authenticationFailed($reason);
        
        $this->assertSame("认证失败: $reason", $exception->getMessage());
        $this->assertSame(3006, $exception->getCode());
    }
    
    public function testServerConfigurationError()
    {
        $error = 'missing config';
        $exception = ProtocolException::serverConfigurationError($error);
        
        $this->assertSame("服务器配置错误: $error", $exception->getMessage());
        $this->assertSame(3007, $exception->getCode());
    }
    
    public function testUnsupportedOperation()
    {
        $operation = 'UNKNOWN_OP';
        $exception = ProtocolException::unsupportedOperation($operation);
        
        $this->assertSame("不支持的操作: $operation", $exception->getMessage());
        $this->assertSame(3008, $exception->getCode());
    }
}