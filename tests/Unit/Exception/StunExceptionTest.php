<?php

namespace Tourze\Workerman\RFC3489\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Exception\StunException;

/**
 * StunException 测试类
 */
class StunExceptionTest extends TestCase
{
    public function testInheritance()
    {
        $exception = new StunException();
        
        $this->assertInstanceOf(\Exception::class, $exception);
    }
    
    public function testDefaultMessage()
    {
        $exception = new StunException();
        
        $this->assertSame('', $exception->getMessage());
    }
    
    public function testCustomMessage()
    {
        $message = 'Custom STUN exception message';
        $exception = new StunException($message);
        
        $this->assertSame($message, $exception->getMessage());
    }
    
    public function testCode()
    {
        $code = 500;
        $exception = new StunException('test', $code);
        
        $this->assertSame($code, $exception->getCode());
    }
    
    public function testPrevious()
    {
        $previous = new \Exception('Previous exception');
        $exception = new StunException('test', 0, $previous);
        
        $this->assertSame($previous, $exception->getPrevious());
    }
    
    public function testDefaultValues()
    {
        $exception = new StunException();
        
        $this->assertSame('', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }
}