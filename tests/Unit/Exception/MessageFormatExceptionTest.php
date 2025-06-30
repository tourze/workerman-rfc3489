<?php

namespace Tourze\Workerman\RFC3489\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Exception\MessageFormatException;
use Tourze\Workerman\RFC3489\Exception\StunException;

/**
 * MessageFormatException 测试类
 */
class MessageFormatExceptionTest extends TestCase
{
    public function testInheritance()
    {
        $exception = new MessageFormatException();
        
        $this->assertInstanceOf(StunException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }
    
    public function testDefaultMessage()
    {
        $exception = new MessageFormatException();
        
        $this->assertSame('STUN消息格式错误', $exception->getMessage());
    }
    
    public function testCustomMessage()
    {
        $message = 'Custom format error message';
        $exception = new MessageFormatException($message);
        
        $this->assertSame($message, $exception->getMessage());
    }
    
    public function testCode()
    {
        $code = 400;
        $exception = new MessageFormatException('test', $code);
        
        $this->assertSame($code, $exception->getCode());
    }
    
    public function testPrevious()
    {
        $previous = new \Exception('Previous exception');
        $exception = new MessageFormatException('test', 0, $previous);
        
        $this->assertSame($previous, $exception->getPrevious());
    }
}